<?php

namespace Core;

use System\FormException;
use System\Model;

abstract class Request
{
    protected string $basePath;
    private array $attributes;

    public function __construct()
    {
        $this->basePath = $this->getBasePath();
        $this->attributes = array();
    }

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function set($attribute, $value): void
    {
        $this->attributes[$attribute] = $value;
    }

    public function attr($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function getPath(int $index = -1): string|null
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position); // Fixed index usage here
        }
        $path = $this->stripBasePath($path);
        if ($index >= 0) {
            $parts = explode("/", trim($path, "/"));
            return $parts[$index] ?? null;
        }
        return $path;
    }

    protected function stripBasePath($path): string
    {
        if (str_starts_with($path, $this->basePath)) {
            return substr($path, strlen($this->basePath));
        }
        return $path;
    }

    protected function getBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/') {
            $basePath = '';
        }
        return $basePath;
    }

    public function getQuery(string $key = null, $default = null)
    {
        $query = $_GET;
        if ($key === null) {
            return $query;
        }
        return $query[$key] ?? $default;
    }

    public function input(string $key, $default = null)
    {
        $data = $_REQUEST;
        if (!$data) {
            return $default;
        }
        $keys = explode('.', $key);
        return $this->getValueFromNestedArray($data, $keys, $default);
    }

    public function jsonInput(string $key, $default = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return $default;
        }
        $keys = explode('.', $key);
        return $this->getValueFromNestedArray($data, $keys, $default);
    }

    public function allFiles(): array
    {
        $files = [];
        foreach ($_FILES as $key => $file) {
            $files[$key] = $this->formatFileArray($file);
        }
        return $files;
    }

    /**
     * Handle multiple files with the same name and format them into a structured array.
     * For example, $_FILES['input_name'] where multiple files are uploaded will be
     * transformed from a multi-dimensional array to a structured array for easy access.
     */
    private function formatFileArray($file): array|null
    {
        if (is_null($file)) {
            return null;
        }

        if (is_array($file['name'])) {
            $files = [];
            foreach ($file['name'] as $index => $name) {
                $files[$index] = [
                    'name'     => $name,
                    'type'     => $file['type'][$index],
                    'tmp_name' => $file['tmp_name'][$index],
                    'error'    => $file['error'][$index],
                    'size'     => $file['size'][$index],
                ];
            }
            return $files;
        }

        // Single file upload
        return [
            'name'     => $file['name'],
            'type'     => $file['type'],
            'tmp_name' => $file['tmp_name'],
            'error'    => $file['error'],
            'size'     => $file['size'],
        ];
    }
    /**
     * Converts the uploaded file into a Base64 string.
     */
    public function inputFileBase64(string $name, $default = null)
    {
        $file = $this->file($name);
        if (is_null($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return $default;
        }

        $fileContent = file_get_contents($file['tmp_name']);
        return base64_encode($fileContent);
    }

    /**
     * Returns the uploaded file as a Blob.
     */
    public function inputFileBlob(string $name, $default = null)
    {
        $file = $this->file($name);
        if (is_null($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return $default;
        }

        return file_get_contents($file['tmp_name']);
    }

    /**
     * Returns the file extension of the uploaded file.
     */
    public function inputFileExt(string $name, $default = null)
    {
        $file = $this->file($name);
        if (is_null($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return $default;
        }

        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    public function inputMedia(string $name, $default = null)
    {
        // Define the base media path
        $basePath = 'media';

        // Get the uploaded file
        $file = $this->file($name);
        if (is_null($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return $default;
        }

        // Sanitize the file name
        $originalFileName = $this->sanitizeFileName($file['name']);
        $fileName = $originalFileName;

        // Create a date-specific folder path
        $dateFolder = date('Ymd'); // Format: YYYYMMDD
        $path = $basePath . '/' . $dateFolder;

        // Ensure the destination directory exists
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        // Generate the destination file path
        $destination = rtrim($path, '/') . '/' . $fileName;

        // Check if the file already exists and modify the file name if necessary
        $fileIndex = 1;
        while (file_exists($destination)) {
            $fileName = $this->appendNumberToFileName($originalFileName, $fileIndex);
            $destination = rtrim($path, '/') . '/' . $fileName;
            $fileIndex++;
        }

        // Move the file to the specified path
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Insert file details into the database
            $this->insertFileDetails($file, $destination);

            // Return the URL or path of the uploaded file
            return $destination;
        }

        return $default;
    }

    /* END OF Handling files */

    public function getHeader(string $name)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return $_SERVER['HTTP_' . $name] ?? null;
    }

    public function isAjax(): bool
    {
        return $this->getHeader('X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function has(string $key): bool
    {
        return isset($_REQUEST[$key]);
    }

    private function getValueFromNestedArray(array $data, array $keys, $default)
    {
        if (empty($keys)) {
            return $default;
        }

        $key = array_shift($keys);

        if (isset($data[$key]) && is_array($data[$key])) {
            return empty($keys) ? $data[$key] : $this->getValueFromNestedArray($data[$key], $keys, $default);
        } elseif (isset($data[$key])) {
            return empty($keys) ? $data[$key] : $default;
        }

        return $default;
    }

    /**
     * Sanitizes a file name by removing unwanted spaces and special characters.
     *
     * @param string $fileName The original file name.
     * @return string The sanitized file name.
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Remove leading and trailing spaces
        $fileName = trim($fileName);

        // Replace spaces with underscores or hyphens
        $fileName = preg_replace('/\s+/', '_', $fileName);

        // Remove special characters except for dots and underscores
        $fileName = preg_replace('/[^A-Za-z0-9_\.\-]/', '', $fileName);

        // Ensure the file name is not empty
        return $fileName ?: 'default_filename';
    }
    /**
     * Appends a number to the file name to create a unique file name.
     *
     * @param string $fileName The original file name.
     * @param int $index The index to append.
     * @return string The unique file name.
     */
    private function appendNumberToFileName(string $fileName, int $index): string
    {
        // Get file extension
        $fileParts = pathinfo($fileName);
        $nameWithoutExtension = $fileParts['filename'];
        $extension = isset($fileParts['extension']) ? '.' . $fileParts['extension'] : '';

        // Append index to the file name
        return $nameWithoutExtension . '-' . $index . $extension;
    }

    /**
     * Inserts file details into the media table.
     *
     * @param array $file The uploaded file information from $_FILES.
     * @param string $destination The path where the file was saved.
     * @return void
     * @throws FormException
     * @throws \Exception
     */
    private function insertFileDetails(array $file, string $destination): void
    {
        $m = new Model('media','ID');
        $m->insert(['file_name' => basename($file['name']),
            'file_path' => $destination,
            'file_size' => $file['size'],
            'mime_type' =>$file['type']
        ]);
    }

    /* Handling Files */
    private function file(string $key): ?array
    {
        return $this->formatFileArray($_FILES[$key] ?? null);
    }
}