<?php
namespace System;

use System\Core\Response as CoreResponse;
use System\Core\Page;
use Exception; 
use System\Config;

use function apply_filter;
use function do_action;

/**
 * System Response wrapper (composition) for compatibility with the new immutable Core\Response.
 *
 * Use this class exactly where you previously extended Core\Response.
 * It wraps a Core\Response instance and exposes the old System\Response API.
 */
class Response
{
    private CoreResponse $res;   // underlying immutable response
    private Page $page;

    public function __construct()
    {
        // create a fresh Core\Response (status 200 by default)
        $this->res = new CoreResponse();
        $this->page = new Page();
    }

    public function init(): void
    {
        global $env;
        $env->theme->init();
    }

    public function setPageLayout(string $layout): void
    {
        $this->page->setLayout($layout);
    }

    public function getUIBlock(string $block, array $data): string
    {
        global $env;
        $data['page'] = $this->page;
        return $env->theme->renderUIBlock($block, $data);
    }
 
    public function render(): void
    {
        global $env;

        // allow filters to swap theme
        $env->theme = apply_filter('get_theme', $env->theme, Config::get('app.theme'));
        $layout = $env->theme->getLayout($this->page->getType(), $this->page->getLayout());

        // Capture output from theme render into a string — prevents immediate echoing
        ob_start();
        try {
            // theme->render may echo; capture it
            $env->theme->render($layout, $this->page);
            $content = ob_get_clean();
        } catch (\Throwable $e) {
            // ensure buffer cleaned on exception
            ob_end_clean();
            throw $e;
        }

        // Put captured content into PSR-7 response body
        // use file download? here content is HTML
        $this->res = $this->res->withHeader('Content-Type', 'text/html; charset=utf-8');
        $streamFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $stream = $streamFactory->createStream($content);
        $this->res = $this->res->withBody($stream);
        $this->res = $this->res->withHeader('Content-Length', (string) $stream->getSize());

        // Merge page headers into response
        $pageHeaders = $this->page->getHeaders();
        foreach ($pageHeaders as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $val) {
                    $this->res = $this->res->withAddedHeader($k, (string)$val);
                }
            } else {
                $this->res = $this->res->withHeader($k, (string)$v);
            }
        }

        // Legacy hook: call after status/headers prepared
        do_action('http_response_html', $this->res->getStatusCode(), $this->page);

        // Emit and exit
        $this->res->emitAndExit();
    }


    /**
     * Add a block from a view
     *
     * @throws Exception
     */
    public function add(string $block, string $view, array $data = []): void
    {
        $content = $this->view($view, $data);
        $this->page->block($block, $content);
    }

    /**
     * Set a UI block content directly
     *
     * @throws Exception
     */
    public function set(string $block, string $content): void
    {
        $this->page->block($block, $content);
    }

    public function setPageContent(string $view, array $data = []): void
    {
        $content = $this->view($view, $data);
        $this->page->setContent($content);
    }

    /**
     * Set page metadata dynamically
     */
    public function setMeta(string $key, mixed $value): void
    {
        $this->page->setMeta($key, $value);
    }

    public function setPageProperty(string $key, mixed $value): void
    {
        $this->page->setPageProperty($key, $value);
    }

    /**
     * Render and return a view (no emit)
     *
     * @throws Exception
     */
    private function view(string $name = "index", array $data = []): false|string
    {
        $view_path = apply_filter('view_path', Config::get('app.views_path'));
        $file = "{$view_path}/{$name}.php";

        if (!file_exists($file)) {
            // keep previous behavior: echo and exit (you can change to exception)
            echo "View not found: $file";
            exit();
        }

        $data['page'] = $this->page;

        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function redirect(string $url, int $code){
        $this->res = $this->res->redirect($url,$code);
        $this->res->emitAndExit();
    }

    /**
     * Set HTTP status code on the underlying immutable response.
     */
    public function setStatus(int $int): void
    {
        // Core\Response::withStatus is immutable — replace underlying instance
        $this->res = $this->res->withStatus($int);
        do_action('http_response_html', $this->res->getStatusCode(), $this->page);
    }

    /**
     * Send JSON and exit (compat with previous sendJson).
     *
     * @param array $array
     */
    public function sendJson(array $array): void
    {
        // produce a PSR-7 response from the underlying immutable Core\Response
        $psr = $this->res->json($array, 200);

        // wrap the PSR-7 response back into a Core\Response wrapper
        $wrapped = new CoreResponse($psr);

        // send + exit
        $wrapped->emitAndExit();
    }


    /**
     * Provide access to underlying Core\Response when needed.
     */
    public function getCoreResponse(): CoreResponse
    {
        return $this->res;
    }

    /**
     * Backwards compatibility: some old code may inspect $response->headers or $response->statusCode
     * Provide read-only magic getters to reflect the current PSR-7 state.
     */
    public function __get(string $name)
    {
        if ($name === 'headers') {
            return $this->res->getPsr7()->getHeaders();
        }
        if ($name === 'statusCode') {
            return $this->res->getStatusCode();
        }
        return null;
    }

    /**
     * Convenience: allow emitting current Core\Response and exit.
     * Useful when controllers do not want to call sendJson() etc.
     */
    public function emitAndExit()
    {
        $this->res->emitAndExit();
    }

    /**
     * Convenience: emit without exiting.
     */
    public function emit(): void
    {
        $this->res->emit();
    }

    public function not_found(): void
    {
        $this->res = $this->res->withStatus(404);
        do_action('http_response_html', $this->res->getStatusCode(), $this->page);
        $this->res->emitAndExit();
    }

    public function exit($content = ''): void
    {
        $streamFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $stream = $streamFactory->createStream($content);
        $this->res = $this->res->withBody($stream);
        $this->res = $this->res->withHeader('Content-Length', (string) $stream->getSize());
        $this->res->emitAndExit();
    }

    public function file_download(string $filepath, string $filename = null): void
    {
        $this->res = $this->res->file($filepath, $filename);
        do_action('http_response_file_download', $this->res->getStatusCode(), $filepath);
        $this->res->emitAndExit();
    }   
}
