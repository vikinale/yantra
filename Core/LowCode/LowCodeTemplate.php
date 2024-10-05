<?php

namespace Core\LowCode;

class LowCodeTemplate extends Shortcode
{
    public string $name;
    private ?LowCodeModule $module;
    public array $attributes = [];
    public array $variables = [];

    private  string $content;

    public function __construct(string $name,string $content,LowCodeModule $module)
    {
        parent::__construct($name);
        $this->module = $module;
        $this->content = $content;
    }

    public function setParent(LowCodeModule|Shortcode|null $parent): void {
        $this->parent = $parent;
    }

    public function getParent(): LowCodeModule|Shortcode|null {
        return $this->parent;
    }

    /**
     * Set a variable for the module, supporting deep array access using dot notation.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        if(str_starts_with($key,'env.'))
        {
            LowCode::set($key, $value);
        }
        elseif(str_starts_with($key,'module.'))
        {
            $key = substr($key, strlen("module."));
            $this->module?->set($key, $value);
        }
        else {
            if (str_starts_with($key, "template.")) {
                $key = substr($key, strlen("template."));
            }
            $keys = explode('.', $key);
            LowCode::setByPath($keys, $value,$this->variables);
        }
    }

    /**
     * Get a variable from the module, supporting deep array access using dot notation.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        if(empty($key)){
            return $this->variables;
        }
        if(str_starts_with(trim($key),'env.')){
         return LowCode::get($key);
        }
        elseif(str_starts_with(trim($key),'module.'))
        {
            $key = substr($key, strlen("module."));
            return $this->module?->get($key);
        }
        else
        {
            if (str_starts_with($key, "template.")) {
                $key = substr($key, strlen("template."));
            }

            $x = LowCode::getByPath( $key,$this->variables);
            if($x == null){
                if($this->parent==null){
                    return LowCode::getByPath($key);
                }
                return $this->parent?->get($key);
            }
            return $x;
        }
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function parse(string $content, array $config, ?Shortcode $parent = null,  LowCodeTemplate | LowCodeModule | Shortcode  $elderSibling = null): string
    {
        $this->parent = $parent;
        $this->elderSibling = $elderSibling;

        return parent::parse($this->content,$config,$parent);
    }
}
//