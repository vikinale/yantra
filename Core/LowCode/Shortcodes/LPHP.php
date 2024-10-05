<?php

// Core/sh/TestShortcode.php
namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;
use Core\LowCode\ShortcodeManager;

class LPHP extends Shortcode
{
    public static string|null $shortcode_name = "php";
    public function __construct()
    {
        parent::__construct("php");
    }

    public function parse(string $content, array $config, LowCodeTemplate|LowCodeModule|Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): array|string
    {
        $this->parent = null;
        $this->elderSibling = null;

        $attr = $config['attributes']??[];
        $selfClosing = $config['selfClosing']??false;
        $main = $attr['main']??"";
        ob_clean();
        ob_start();
        if ($selfClosing){
           eval($main)??"";
        }
        eval(ShortcodeManager::parse($content,$parent))??"";
        return ob_get_clean();
    }
}