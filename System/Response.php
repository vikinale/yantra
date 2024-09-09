<?php

namespace System;

use Core\LowCode\ShortcodeManager;
use Core\Page;
use Core\UIComponent;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;

class Response extends \Core\Response
{
    public Page $page;

    public function __construct(array $config = [])
    {
        parent::__construct(200,[]);
        $this->page = new Page();
    }

    #[NoReturn] public function render(): string|false
    {
        global $env;
        $env->theme = apply_filter('get_theme', $env->theme, Config::get('theme'));
        $env->theme->render('index', $this->page->data());
        $this->exit();
    }

    /**
     * @throws Exception
     */
    public function add(string $block, string $view, array $data = []): void
    {
        $content = $this->view($view,$data);
        $this->page->block($block,ShortcodeManager::parse( $content));
    }
    /**
     * @throws Exception
     */
    public function set(string $block, string $content): void
    {
        $this->page->block($block,ShortcodeManager::parse( $content));
    }

    private function view($name="index",array $data=[]): false|string
    {
        $view_path = apply_filter('view_path','');
        $file = "$view_path/{$name}.php";
        if (!file_exists($file)) {
            return '';
        }
        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}