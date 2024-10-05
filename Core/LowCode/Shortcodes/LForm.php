<?php

// Core/sh/TestShortcode.php
namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;
use Core\LowCode\ShortcodeManager;

class LForm extends Shortcode
{
    public static string|null $shortcode_name = "form";
    public function __construct()
    {
        parent::__construct("form");
    }

    public function parse(string $content, array $config, LowCodeTemplate|LowCodeModule|Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): array|string
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        $attr = $config['attributes']??[];
        $id = $config['sections'][0] ??uniqid("form-");
        $method = $attr['main']??"post";
        $action = $attr['action']??site_url();

        $action = $this->replaceVariables($action);
        $method = $this->replaceVariables($method);

        $new_content = ShortcodeManager::parse($content,$parent);
        return "<form id='$id' action='$action' method='$method'>\n\r$new_content\n\r</form>";
    }
}