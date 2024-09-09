<?php

// Core/sh/TestShortcode.php
namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;

class Actions extends Shortcode
{
    public function __construct()
    {
        parent::__construct("actions");
    }

    public function parse(string $content, array $config, LowCodeTemplate|LowCodeModule|Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): mixed
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        $attr = $config['attributes']??[];
        $action = $config['sections'][0] ?? '';
        $priority = $attr['priority'] ?? 10;
        switch ($action){
            case 'footer':{
                add_action('page-bottom',function () use($content){
                    echo $content;
                },$priority);
                break;
            }
            case 'header':{
                add_action('page-head',function () use($content){
                    echo $content;
                },$priority);
                break;
            }
            case 'top':{
                add_action('page-top',function () use($content){
                    echo $content;
                },$priority);
                break;
            }
            case '':
                break;
            default:{
                add_action($action,function () use($content){
                    echo $content;
                },$priority);
            }
        }
        return "";
    }
}