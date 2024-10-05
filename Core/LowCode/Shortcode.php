<?php

namespace Core\LowCode;

use Exception;

abstract class Shortcode
{
    public string $name;
    public static string |null $shortcode_name = null;

    public ?Shortcode $parent = null;
    public ?Shortcode $elderSibling = null;
    public ?Shortcode $youngerSibling = null;
    //public array $children = [];
    //protected string $rawContent; // Property to store the raw content
    public function __construct(string $name)
    {
        $this->name = $name;
    }

   /* public function setRawContent(string $content): void
    {
        $this->rawContent = $content;
    }

    public function getRawContent(): string
    {
        return $this->rawContent;
    }*/

    /**
     * @throws Exception
     */
    public function parse(string $content, array $config, ?Shortcode $parent = null, ?Shortcode $elderSibling=null): mixed
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;
        if(isset($config['attributes']) && is_array($config['attributes']))
        {
            foreach ($config['attributes'] as $key=>$value){
                    if(is_string($value))
                        $this->set($key, $this->replaceVariables($value));
                    else
                        $this->set($key,$value);
            }
        }
        //Find child shortcodes into content
        $shortcodeNames = implode('|', array_map('preg_quote', array_keys(LowCode::$shortcodes)));
        $pattern = ShortcodeManager::getPattern($shortcodeNames);

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        $previous = null;
        foreach ($matches[0] as $index => $fullMatch)
        {
            $current = null;
            $child_name = trim(strtolower($matches[1][$index][0]));
            $child_selfClosing = false;
            $sections = explode('.',trim(trim(trim($matches[2][$index][0]??'main'),'.')));

            //echo json_encode($matches[1]);
            if(!empty($matches[4][$index][0]))
                $child_selfClosing = true;

            $child_content = $matches[5][$index][0] ?? '';
            $child_attributes = ShortcodeManager::parseAttributes(trim($matches[3][$index][0]));
            $current = ShortcodeManager::get($child_name,$sections);
            $new_content = $current->parse($child_content,
                array(
                    'parentConfig'=>$config,
                    'attributes'=>$child_attributes,
                    'selfClosing'=>$child_selfClosing,
                    'sections'=>$sections
                ),$this,$previous);
            $content =  ShortcodeManager::replaceFirst($child_name, $new_content, $content);
            $previous = $current;
        }
        return $content;
    }

    public function set(string $key, mixed $value): void
    {
        if($this->parent === null)
        {
            $keys = explode('.', $key);
            LowCode::setByPath( $keys, $value);
        }
        else{
            $this->parent->set($key, $value);
        }
    }
    public function unset(string $key, mixed $value): void
    {
        if($this->parent === null)
        {
            $keys = explode('.', $key);
            LowCode::unsetByPath( $keys);
        }
        else{
            $this->parent->unset($key, $value);
        }
    }

    public function get(string $key): mixed
    {
        if($this->parent===null){
            return LowCode::get(trim($key,"{,}"));
        }
        return $this->parent->get(trim($key,"{,}"));
    }

    public function hasVariable(string $key): bool
    {
        if ($this->parent === null) {
            $keys = explode('.', $key);
            return LowCode::hasByPath($keys);
        }
        return $this->parent->hasVariable($key);
    }

    public function removeVariable(string $key): void
    {
        if ($this->parent === null) {
            $keys = explode('.', $key);
            LowCode::removeByPath($keys);
        } else {
            $this->parent->removeVariable($key);
        }
    }

    protected function replaceVariables(mixed $condition,?string $null_val = ""): mixed
    {
        if(!is_string($condition)){
            return $condition;
        }
        if(strlen($condition)<2){
            return $condition;
        }
        if (preg_match('/^\{[a-zA-Z0-9_\-@]+\}$/', $condition)) {
            if(isset($this->parent)){
                $x = $this->parent->get(trim(trim($condition,"{}")));
            }
            else{
                $x = $this->get(trim(trim($condition,"{}")));
            }
            return $x;
        }
        else {
            return preg_replace_callback('/\{([^\{\}]+)\}/', function ($matches)use ($null_val) {
                $x = $this->get($matches[1])?? $null_val;
                if(is_string($x))
                {
                    return $x;
                }
                else{
                    return  $matches[0];
                }
            },$condition);
        }
    }

    protected function evaluate(string $statement): bool
    {
        $output =  preg_replace_callback('/\{\{([^\{\}]*)\}\}/', function ($matches) {
            return $this->evaluate($matches[1]??'');
        }, $statement);
        echo $output;
        return $output;
    }

    protected function evaluateCondition(string $condition): mixed
    {
        try {
            $condition1 = $this->replaceVariables($condition,'null');
            if(empty($condition1)){
                return false;
            }
            return eval("return ($condition1);");
        } catch (\Throwable $e) {
            echo ("Invalid condition: ($condition)");
            return false;
        }
    }


}