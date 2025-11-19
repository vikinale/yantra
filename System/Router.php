<?php

namespace System;

class Router
{
    protected array $routes = [];
    protected array $fileRoutes = [];

    /**
     * Convenience: GET route.
     *
     * @param string              $path
     * @param array<string,mixed> $handler [ControllerClass::class, 'method']
     */
    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Convenience: POST route.
     */
    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Optional: PUT, DELETE, PATCH helpers (use only if you need them)
     */
    public function put(string $path, array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function patch(string $path, array $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Core method: supports both
     *  - addRoute('GET', '/x', Controller::class, 'method')
     *  - addRoute('GET', '/x', [Controller::class, 'method'])
     */
    public function addRoute($method, $path, $controller, $action = null): void
    {
        // If handler is passed as [Controller::class, 'method']
        if (is_array($controller) && $action === null) {
            [$controller, $action] = $controller;
        }

        $pattern = $this->convertPathToPattern($path);

        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    public function addFileRoute($method, $path, $filepath, $controller = null, $action = null): void
    {
        $pattern = $this->convertPathToPattern($path);

        $this->fileRoutes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'filepath'   => $filepath,
            'controller' => $controller,
            'action'     => $action,
        ];
    }

    protected function convertPathToPattern($path): string
    {
        $pattern = preg_replace(
            pattern: '/\{([a-zA-Z0-9_]+)\}/',
            replacement: '(?P<$1>[a-zA-Z0-9_-]+)',
            subject: $path
        );

        return '/^' . str_replace('/', '\/', $pattern) . '$/';
    }

    public function dispatch(Request $request, Response $response)
    {
        $method = $request->getMethod();
        $path   = $request->getPath();

        // Normal controller routes
        foreach ($this->routes as $route) {
            if ($method === $route['method'] && preg_match($route['pattern'], $path, $matches)) {
                return $this->invokeController(
                    $route['controller'],
                    $route['action'],
                    $request,
                    $response,
                    $matches
                );
            }
        }

        // File routes
        foreach ($this->fileRoutes as $route) {
            if ($method === $route['method'] && preg_match($route['pattern'], $path, $matches)) {
                if ($route['controller'] && $route['action']) {
                    if (file_exists($route['filepath'])) {
                        $fileContent = file_get_contents($route['filepath']);
                        $request->set('file_contents', $fileContent);

                        return $this->invokeController(
                            $route['controller'],
                            $route['action'],
                            $request,
                            $response,
                            $matches
                        );
                    }

                    return $response->not_found();
                }

                if (file_exists($route['filepath'])) {
                    $response->file_download(
                        $route['filepath'],
                        basename($route['filepath'])
                    );
                    return;
                }

                return $response->not_found();
            }
        }

        $response->not_found();
    }

    protected function invokeController($controller, $action, Request $request, Response $response, $matches)
    {
        $controller = new $controller($request, $response);

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $controller->$action($params);
    }
}