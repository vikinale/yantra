<?php

namespace System;

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

    public function load($template, $file, $data=array()): void
    {
        $templateFile = $this->theme_path.$template."\\" . $file . '.php';
        if (file_exists($templateFile)) {
            extract($data);
            include $templateFile;
        } else {
            echo "<!-- Template file '$templateFile' not found -->";
        }
    }

    public function render(string $layout, array $data): void
    {
        $file = 'index';
        if ($layout && $layout !== 'index') {
            $file = 'layout-' . $layout;
        }
        $templateFile = $this->theme_path . $file . '.php';
        if (file_exists($templateFile)) {
            extract($data);
            include $templateFile;
        } else {
            echo "<!-- Template file '$templateFile' not found -->";
        }
    }
}