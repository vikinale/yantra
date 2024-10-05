<?php
namespace Plugins\admin;
use System\Plugin;

class Admin extends Plugin {
    protected string $plugin_name = 'Yanta Admin a default CMS';
    protected string $plugin_description = 'This is an example plugin for demonstration purposes.';
    protected string $plugin_version = '1.0.0';
    protected string $plugin_author = 'araaiv';

    public function activate(): bool
    {
        return true;
    }

    public function deactivate(): bool
    {
        return false;
    }

    public function path(): string
    {
        return __DIR__;
    }
}