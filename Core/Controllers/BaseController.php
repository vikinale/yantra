<?php
namespace Core\Controllers;

use Core\LowCode\LowCode;
use Core\LowCode\ModuleManager;
use Core\LowCode\ModuleNotFoundException;
use Exception;
use System\Config;

class BaseController
{
    private string $blockDIR ="App/Views/blocks/";
    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->init();
        LowCode::init();
    }

    private function init(): void
    {
        global $env;
        $env->theme = apply_filter('get_theme', $env->theme, Config::get('theme'));
    }

    /**
     * @throws ModuleNotFoundException
     */
    protected function registerBlock(string $name, string $dir = null): void
    {
        ModuleManager::register($name, $dir??$this->blockDIR);
    }
}
