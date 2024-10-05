<?php

namespace Plugins\admin\Controllers;

use Core\Controllers\BaseController;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->registerUIModule('pc','Plugins/admin/UIModules/');
    }
}