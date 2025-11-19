<?php

namespace System;

use Core\Page;
use Core\PageParser;

class Theme {
    private string $theme;
    private string $theme_path;
    
    public function __construct($thisName)
    {
        $this->theme = $thisName;
        $this->theme_path =  __DIR__ . '/../themes/' . $this->theme . '/';
    }

    public function __get(string $name)
    {
        if ($name=='name'){
            return $this->theme;
        }
        else if ($name=='path'){
            return $this->theme_path;
        }
        else if ($name=='url'){
            return site_url("themes/{$this->theme}/");
        }
        else{
            return null;
        }
    }

    /**
     * Include a template file directly (no buffering).
     * Path building is cross-platform.
     *
     * @param string $template  Template folder or name (no trailing slash)
     * @param string $file      File name without .php extension
     * @param array  $data      Variables to extract into template scope
     * @return void
     */
    public function load(string $template, string $file, array $data = []): void
    {
        $template = rtrim($template, '/\\');
        $templateFile = $this->theme_path . $template . DIRECTORY_SEPARATOR . $file . '.php';

        if (file_exists($templateFile)) {
            extract($data, EXTR_SKIP);
            include $templateFile;
            return;
        }

        // Fail gracefully in HTML output (useful for debugging)
        echo "<!-- Theme::load: template file not found: " . htmlspecialchars($templateFile, ENT_QUOTES, 'UTF-8') . " -->";
    }

    /**
     * Load a template and return the generated HTML as a string (buffered).
     * Useful when controllers or filters need the HTML rather than direct echo.
     *
     * @param string $template  Template folder or name (no trailing slash)
     * @param array  $data      Variables to extract into template scope
     * @return string
     */
    public function load_html(string $template, array $data = []): string
    {
        $template = rtrim($template, '/\\');
        // default file is index.php in the template folder
        $templateFile = $this->theme_path . $template . DIRECTORY_SEPARATOR . 'index.php';

        if (!file_exists($templateFile)) {
            return "<!-- Theme::load_html: template file not found: " . htmlspecialchars($templateFile, ENT_QUOTES, 'UTF-8') . " -->";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            return "<!-- Theme::load_html: error in template: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . " -->";
        }

        return (string) ob_get_clean();
    }

    /**
     * Render the specified layout file. If $layout is null or 'index' it will use index.php.
     * This method echoes output (mirror of load()) for template rendering.
     *
     * @param string|null $layout
     * @param object      $page
     * @return void
     */
    public function render(?string $layout, object $page): void
    {
        $file = 'index';
        if (!empty($layout) && $layout !== 'index') {
            // sanitize layout name
            $file = basename($layout);
        }

        $templateFile = $this->theme_path . $file . '.php';
        $data = ['page' => $page];

        if (file_exists($templateFile)) {
            extract($data, EXTR_SKIP);
            include $templateFile;
            return;
        }

        echo "<!-- Theme::render: layout file not found: " . htmlspecialchars($templateFile, ENT_QUOTES, 'UTF-8') . " -->";
    }

    /**
     * Choose the first existing layout file from the provided list.
     * Returns the layout name (without extension). If none found returns "index".
     *
     * @param array<int,string> $layouts
     * @return string
     */
    public function getLayout(...$layouts): string
    {
        foreach ($layouts as $layout) {
            if(is_string($layout)===false || trim($layout)===''){
                continue;
            }
            $candidate = rtrim($layout, '/\\');
            $templateFile = $this->theme_path . $candidate . '.php';
            if (file_exists($templateFile)) {
                return $candidate;
            }
        }

        return 'index';
    }

    // public function load($template, $file, $data=array()): void
    // {
    //     $templateFile = $this->theme_path.$template."\\" . $file . '.php';
    //     if (file_exists($templateFile)) {
    //         extract($data);
    //         include $templateFile;
    //     } else {
    //         echo "<!-- Template file '$templateFile' not found -->";
    //     }
    // }

    public function init(): void
    {
        /*Load theme functions*/
        if(file_exists($this->theme_path .  'functions.php')){
            include $this->theme_path .  'functions.php';
        }
    }

    // public function render(string $layout,object $page): void
    // {
    //     $file = 'index';
    //     if ($layout && $layout !== 'index') {
    //         $file = $layout;
    //     }
    //     $data = array();
    //     $data['page'] = $page;
    //     $templateFile = $this->theme_path . $file . '.php';
    //     if (file_exists($templateFile)) {
    //         extract($data);
    //         include $templateFile;
    //     } else {
    //         echo "<!-- Template file '$templateFile' not found -->";
    //     }
    // }

    public function renderUIBlock(string $slug, array $data): string
    {
        $file = $this->theme_path.'ui-blocks/' . $slug . '.php';

        if (!file_exists($file)) {
            return "<!-- UI Block '{$file}' not found -->";
        }

        // Start output buffering so we can return the included file's output as a string
        ob_start();

        try {
            // Extract data to variables for the template, but don't overwrite existing variables
            extract($data, EXTR_SKIP);

            // Include the file — it can use $page and the extracted variables
            include $file;

            // Get buffer and clean
            $html = (string) ob_get_clean();
            return $html;
        } catch (\Throwable $e) {
            // Clean buffer if an exception/error occurred
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return "<!-- Error rendering UI Block '{$slug}': {$msg} -->";
        }
    }

}