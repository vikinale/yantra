<?php

// Core/sh/TestShortcode.php
namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;
use Core\LowCode\ShortcodeManager;

class Item extends Shortcode
{
    public static string|null $shortcode_name = "item";
    public function __construct()
    {
        parent::__construct("item");
    }

    public function parse(string $content, array $config, LowCodeTemplate|LowCodeModule|Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): array|string
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        $attr = $config['attributes']??[];
        $action = $config['sections'][0] ?? '';
        $selfClosing = $config['selfClosing']??false;
        $main = $attr['main']??"";
        if ($selfClosing){
           return ShortcodeManager::parse($main,$parent);
        }
        elseif($action == 'array'){
            return ShortcodeManager::parseArray($content,$parent);
        }
        else{
            return ShortcodeManager::parse($content,$parent);
        }
    }
}