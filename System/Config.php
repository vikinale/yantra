<?php

namespace System;

class Config
{
    private static array $settings = [];

    /**
     * Load a config file and merge it into the main settings.
     * Usage: Config::read('security');
     */
    public static function read(string $name): array
    {
        $file = BASEPATH . '/App/Config/' . $name . '.php';

        if (!file_exists($file)) {
            return [];
        }

        $config = require $file;

        if (!is_array($config)) {
            throw new \RuntimeException("Config file [$name] must return an array.");
        }

        // Merge into settings
        self::$settings[$name] = $config;

        return $config;
    }

    /**
     * Get a config value by dot-notation.
     * Examples:
     *   Config::get('security.token_secret');
     *   Config::get('redis.host');
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);

        $root = array_shift($parts);

        // Lazy load config file if not loaded yet
        if (!array_key_exists($root, self::$settings)) {
            self::read($root);
        }

        // If still missing, return default
        if (!isset(self::$settings[$root])) {
            return $default;
        }

        $value = self::$settings[$root];

        // Traverse nested keys
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Set config dynamically (optional).
     */
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $root = array_shift($parts);

        // Ensure root exists
        if (!isset(self::$settings[$root])) {
            self::$settings[$root] = [];
        }

        $ref =& self::$settings[$root];

        // Traverse and create nested arrays
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref =& $ref[$part];
        }

        $ref = $value;
    }
}
