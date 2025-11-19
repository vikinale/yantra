<?php

namespace System;

class PluginManager {
    private static $plugins = [];
 

    public static function loadPlugins(array $ActivePluginsList): void
    {
        $pluginDir = __DIR__ . '/../Plugins/';

        foreach ($ActivePluginsList as $folder) {
            $pluginName = basename($folder);
            $pluginPath = $pluginDir . $pluginName . '/';

            // Skip if plugin directory doesn't exist
            if (!is_dir($pluginPath)) {
                continue;
            }

            // 1️⃣ Load index.php if it exists
            $indexFile = $pluginPath . 'index.php';
            if (file_exists($indexFile)) {
                include_once $indexFile;
            }

            // 2️⃣ Load main plugin class file if it exists (same name as folder)
            $mainFile = $pluginPath . $pluginName . '.php';
            if (file_exists($mainFile)) {
                include_once $mainFile;

                // Determine expected namespaced class
                $pluginClass = "Plugins\\{$pluginName}\\{$pluginName}";

                // If class exists, instantiate and store it
                if (class_exists($pluginClass)) {
                    self::$plugins[] = new $pluginClass();
                } else {
                    error_log("Plugin class {$pluginClass} not found in {$mainFile}");
                }
            }
        }
    }


    public static function activatePlugins(): void
    {
        foreach (self::$plugins as $plugin) {
            if (method_exists($plugin, 'activate')) {
                $plugin->activate();
            }
        }
    }

    public static function deactivatePlugins(): void
    {
        foreach (self::$plugins as $plugin) {
            if (method_exists($plugin, 'deactivate')) {
                $plugin->deactivate();
            }
        }
    }

    public static function getPlugins(): array
    {
        return self::$plugins;
    }

    public static function autoloadDir($directory):void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                require_once $file->getRealPath();
            }
        }
    }
}