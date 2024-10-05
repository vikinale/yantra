<?php
namespace Core;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use PDOException;
use System\FormException;
use System\Model;
use System\Request;

abstract class Response
{
    protected array $headers;
    protected int $statusCode;

    public function __construct($statusCode, $defaultHeaders = [])
    {
        $this->headers=$defaultHeaders??[];
        $this->statusCode = $statusCode??200;
    }
    public function __set(string $name, mixed $value): void
    {
        if ($name === 'statusCode') $this->statusCode = $value;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    #[NoReturn] public function exit(string $message=""): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        if(count_chars($message)>0){
            //if (ob_get_length()) {
            //    ob_end_clean();
            //}
            echo $message;
            //flush();
        }
        exit();
    }
    #[NoReturn] public function redirect(string $url,$code=302): void
    {
        $this->statusCode = $code;
        $this->setHeader('Location', $url);
        $this->exit();
    }
    public function setContentType(string $type): void
    {
        $this->setHeader('Content-Type', $type);
    }
    public function setCharset(string $charset): void
    {
        $this->setHeader('Content-Type', 'text/html; charset=' . $charset);
    }
    public function setCache(int $seconds): void
    {
        $this->setHeader('Cache-Control', 'max-age=' . $seconds);
    }
    public function setCookie(string $name, string $value, int $expire = 0,string $path = '',string $domain = '',bool $secure = false,bool $httponly = false ): void
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @throws Exception
     */
    public function download(string $filePath, string $filename = null): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }
        $filename = $filename ?? basename($filePath);
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $msg = file_get_contents($filePath);
        $this->exit($msg);
    }
    public function sendFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }
        $this->setHeader('Content-Type', mime_content_type($filePath));
        $this->setHeader('Content-Length', filesize($filePath));
        $msg =  file_get_contents($filePath);
        $this->exit($msg);
    }

    #[NoReturn] public function json($data,$statusCode=200): void
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json');
        $msg =  json_encode($data);
        $this->exit($msg);
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function dataTable(Request $request, Model $m,array $columns): void
    {
        $start = $request->jsonInput('start', 0);
        $length = $request->jsonInput('length', 10);
        $order = $request->jsonInput('order', []);
        //$columns = $request->jsonInput('columns', []);
        $search = $request->jsonInput('search', []);
        $list = $m->getDataTableResults($columns,$start, $length, $order, $search);
        $this->json($list );
    }

    /**
     * Sends a Base64-encoded file to the browser for download, resolving the file extension from the MIME type.
     *
     * @param string $base64Data The Base64 encoded file content.
     * @param string $filename The base filename (without extension) to be downloaded.
     * @throws Exception If the Base64 data is invalid or extension cannot be resolved.
     */
    public function base64ToFileDownload(string $base64Data, string $filename): void
    {
        // Regular expression to capture MIME type if present
        if (preg_match('/^data:(.*?);base64,/', $base64Data, $matches)) {
            $mimeType = $matches[1]; // Extract MIME type
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1); // Remove the Base64 header
        } else {
            throw new Exception("Invalid Base64 data format.");
        }

        // Decode Base64 data
        $fileData = base64_decode($base64Data);
        if ($fileData === false) {
            throw new Exception("Base64 decoding failed.");
        }

        // Get file extension from the MIME type
        $extension = $this->getExtensionFromMimeType($mimeType);
        if ($extension === null) {
            throw new Exception("Unsupported MIME type: $mimeType");
        }

        // Append resolved extension to the filename
        $filenameWithExtension = "$filename.$extension";

        // Set headers to force download
        $this->setHeader('Content-Type', $mimeType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filenameWithExtension . '"');
        $this->setHeader('Content-Length', strlen($fileData));

        // Output the file content and trigger the download
        $this->exit($fileData);
    }

    /**
     * Sends binary Blob data to the browser for download, resolving the file extension from the MIME type.
     *
     * @param string $blobData The binary (Blob) file content.
     * @param string $filename The base filename (without extension) to be downloaded.
     * @param string $mimeType The MIME type of the file (optional).
     * @throws Exception If the Blob data is invalid or extension cannot be resolved.
     */
    public function blobToFileDownload(string $blobData, string $filename, string $mimeType): void
    {
        // Validate the Blob data
        if (empty($blobData)) {
            throw new Exception("Invalid Blob data provided.");
        }

        // Resolve file extension from the MIME type
        $extension = $this->getExtensionFromMimeType($mimeType);
        if ($extension === null) {
            throw new Exception("Unsupported MIME type: $mimeType");
        }

        // Append the resolved extension to the filename
        $filenameWithExtension = "$filename.$extension";

        // Set headers to force download
        $this->setHeader('Content-Type', $mimeType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filenameWithExtension . '"');
        $this->setHeader('Content-Length', strlen($blobData));

        // Output the Blob data and trigger the download
        $this->exit($blobData);
    }

    /**
     * @throws Exception
     */
    public function blobToFileDisplay(string $blobData, string $mimeType): void
    {
        // Validate the Blob data
        if (empty($blobData)) {
            throw new Exception("Invalid Blob data provided.");
        }

        // Resolve file extension from the MIME type (if needed)
        $extension = $this->getExtensionFromMimeType($mimeType);
        if ($extension === null) {
            throw new Exception("Unsupported MIME type: $mimeType");
        }

        // Set headers to display the file in the browser
        $this->setHeader('Content-Type', $mimeType); // Correct MIME type
        $this->setHeader('Content-Length', strlen($blobData)); // Content length for the binary data
        $this->setHeader('Content-Disposition', 'inline');
        $this->setHeader('Transfer-Encoding', 'none');
        $this->exit($blobData);
    }



    /**
     * Resolves file extension from MIME type.
     *
     * @param string $mimeType The MIME type (e.g., 'image/png', 'application/pdf').
     * @return string|null The corresponding file extension or null if not found.
     */
    private function getExtensionFromMimeType(string $mimeType): ?string
    {
        // Common MIME types to file extension mapping
        $mimeTypes = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            // Add more MIME types as needed...
        ];

        return $mimeTypes[$mimeType] ?? null; // Return extension or null if unsupported
    }

    private function getMimeTypeFromExtension(string $ext): ?string
    {
        // Common file extension to MIME type mapping
        $extensions = [
            'webp' => 'image/webp',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'json' => 'application/json',
            'xml' => 'application/xml',
            // Add more extensions as needed...
        ];

        // Normalize extension to lowercase and return the corresponding MIME type
        return $extensions[strtolower($ext)] ?? null; // Return MIME type or null if unsupported
    }

    #[NoReturn] public function not_found(): void
    {
        $this->statusCode=404;
        $this->exit("Error 404:Page not found");
    }
    #[NoReturn] public function show_error($error): void
    {
        $this->statusCode=500;
        $this->exit("<p>$error</p>");
    }

    /**
     * Retrieves file details from the media table by file ID.
     *
     * @param int $fileId The ID of the file to retrieve.
     * @param PDO|null $pdo Optional PDO instance for database connection.
     * @return array The file details.
     * @throws FormException
     * @throws Exception
     */
    private function getFileDetails(int $fileId): array
    {
        $m = new Model('media','ID');
        return $m->select('file_name, file_path, file_size, mime_type')->where('ID','=',$fileId)->getResult(PDO::FETCH_OBJ);
    }

}