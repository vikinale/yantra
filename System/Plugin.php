<?php

namespace System;

abstract class Plugin
{

    private string $name;
    private string $description;
    private string $ver;
    private string $author;

    public function __construct($config=array()){
        $this->name = isset($config['name'])??'Sample Plugin';
        $this->description = isset($config['name'])??'';
        $this->ver = isset($config['name'])??'1.0.0';
        $this->author = isset($config['name'])??'Unknown';
    }

    abstract  public function activate();

    abstract public function deactivate();

    //** Default Functions for a plugin */
    public function getPluginName(): string
    {
        return $this->name;
    }

    public function getPluginDescription(): string
    {
        return $this->description;
    }

    public function getPluginVersion(): string
    {
        return $this->ver;
    }

    public function getPluginAuthor(): string
    {
        return $this->author;
    }

    abstract public function path();
}