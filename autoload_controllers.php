<?php

// Define the base namespace and directory for your Controllers
$baseNamespace = 'Controllers\\';
$controllersDirectory = __DIR__ . '/App/Controllers';

// Get all PHP files from the Controllers directory
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersDirectory));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        // Derive the fully qualified class name from the file path
        $relativePath = str_replace([$controllersDirectory, '/', '.php'], ['', '\\', ''], $file->getRealPath());
        $className = $baseNamespace . $relativePath;

        if (class_exists($className)) {
            // Use reflection to check if the class has the runOnce method
            $reflector = new ReflectionClass($className);
            if ($reflector->hasMethod('runOnce') && $reflector->getMethod('runOnce')->isStatic()) {
                // Call the static runOnce method
                $className::runOnce();
            }
        }
    }
}