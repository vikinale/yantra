<?php

// Core/sh/TestShortcode.php
namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;

class Filters extends Shortcode
{
    public function __construct()
    {
        parent::__construct("filters");
    }

    public function parse(string $content, array $config, LowCodeTemplate|LowCodeModule|Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): mixed
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        $attr = $config['attributes']??[];
        $filter = $config['sections'][0] ?? '';
        $priority = $attr['priority'] ?? 10;
        switch ($filter){
            case '':
                break;
            default:{
                add_action($filter,function () use($content){
                    echo $content;
                },$priority);
            }
        }
        return "";
    }
}