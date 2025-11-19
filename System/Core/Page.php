<?php

namespace System\Core;

class Page extends WebPage
{
    private array $blocks =[];

    public function __construct(array $values = [])
    {
        parent::__construct($values);
    }

    public function block(string $block ,string $content): void
    {
        $this->blocks[$block] = ($this->blocks[$block]??"").$content;
    }

    public function get(string $block): ?string
    {
        return $this->blocks[$block]??null;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(),$this->blocks);
    }
}