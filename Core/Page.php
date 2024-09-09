<?php

namespace Core;

class Page
{
    public string $layout;

    public string $title;
    public string $slug;
    protected array $meta;
    private array $blocks;


    public function __construct()
    {
        $this->layout = "";
        $this->meta = [];
        $this->blocks = [];
    }

    public function meta(string $key = null ,mixed $value = null): mixed
    {
        if($key == null)
            return  $this->meta;

        if($value == null)
            return $this->meta[$key];

        $this->meta[$key] = $value;
        return true;
    }

    public function block(string $block ,string $content): void
    {
        $this->blocks[$block] = ($this->blocks[$block]??"").$content;
    }


    public function data(): array
    {
        return array_merge(['meta'=>$this->meta, 'title'=>$this->title, 'slug'=>$this->slug],$this->blocks);
    }

}