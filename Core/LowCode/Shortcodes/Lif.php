<?php

namespace Core\LowCode\Shortcodes;

use Core\LowCode\LowCodeModule;
use Core\LowCode\LowCodeTemplate;
use Core\LowCode\Shortcode;
use Core\LowCode\ShortcodeManager;

class Lif extends Shortcode
{
    public static string|null $shortcode_name = "if";
    private ?bool $conditionMet = false;

    public function __construct()
    {
        parent::__construct("if");
    }

    public function parse(string $content, array $config, LowCodeTemplate | LowCodeModule | Shortcode $parent = null, LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): string
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;
        $action = $config['sections'][0] ??"";

        $attributes = $config['attributes'] ?? [];
        $condition = $attributes['main'] ?? null;

        if ($condition === null) {
            throw new \Exception("Condition not provided in [if] shortcode.");
        }

        switch ($action){
            case 'is_array':{
                //$condition = $this->replaceVariables($condition,'null');
                $this->conditionMet = is_array($this->get($condition));
                break;
            }
            case 'is_string':{
                $x = $this->get($condition);
                $this->conditionMet = is_string($x);
                break;
            }
            case 'is_empty':{
                $x = $this->get($condition);
                $this->conditionMet = empty($x);
                break;
            }
            case 'not_empty':{
                $x = $this->get($condition);
                $this->conditionMet = !empty($x);
                break;
            }
            case 'is_null':{
                $x = $this->get($condition);
                $this->conditionMet = is_null($x);
                break;
            }
            case 'not_null':{
                $x = $this->get($condition);
                $this->conditionMet = !is_null($x);
                break;
            }
            default:{
                // Evaluate the condition
                $this->conditionMet = $this->evaluateCondition("$condition");
            }
        }
        if ($this->conditionMet) {
            return ShortcodeManager::parse($content, $this);
        }

        return ''; // Return empty string if no condition is met
    }



    public function getConditionMet(): bool
    {
        return $this->conditionMet??false;
    }
}
