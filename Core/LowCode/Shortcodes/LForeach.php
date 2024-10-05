<?php

namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCode;
use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;
use Core\LowCode\ShortcodeManager;

class LForeach extends Shortcode
{
    public static string|null $shortcode_name = "foreach";

    private int $loopStatus = 1;
    private array $variables = [];

    public function __construct()
    {
        parent::__construct("foreach");
    }

    public function parse(string $content, array $config, LowCodeTemplate | LowCodeModule | Shortcode $parent = null, LowCodeTemplate | LowCodeModule | Shortcode $elderSibling = null): bool|string
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        $attributes = $config['attributes'] ?? [];
        $array_name = $attributes['main'] ??null;

        if ($array_name === null) {
            throw new \Exception("Array not provided in [foreach] shortcode.");
        }
        // Replace variables in the array attribute
        //$array = $this->replaceVariables($array_name);
        $array = $this->replaceVariables($array_name);

        if (!is_array($array)) {
            throw new \Exception("Invalid array format provided in [foreach] shortcode. $array_name");
        }

        // Execute the loop
        $output = "";
        foreach ($array as $key=>$item) {
            $this->loopStatus = 1; // Reset loop status
            // Assign key and value as variables available in the content
            $this->unset('@item', $item);
            $this->unset('@key', $key);
            $this->set('@item', $item);
            $this->set('@key', $key);
            // Parse the content for the current iteration
            $output .= ShortcodeManager::parse($content, $this);

            if ($this->loopStatus == 0) {
                break;
            }
            elseif ($this->loopStatus == 2)
            {
                continue;
            }
        }

        return $output;
    }


    public function getVariable(string $varName): mixed
    {
        return $this->variables[$varName] ?? null;
    }

    public function setLoopStatus(int $status = 1): void
    {
        $this->loopStatus = $status;
    }

    public function getLoopStatus(): int
    {
        return $this->loopStatus;
    }
}
