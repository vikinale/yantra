<?php

namespace Plugins\admin\Controllers;

use Core\Controllers\BaseController;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use System\Request;
use System\Response;

class SchoolController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "School";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('footer','footer');
            $response->add('content','school/index');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    public function academic_years(Request $request, Response $response): void{
        $response->page->slug = $request->getPath();
        if($request->isGet()){
            $response->page->title = "School";
            try {
                $response->add('pagetop','preloader');
                $response->add('pagetop','notifications');
                $response->add('topnav','topnav');
                $response->add('sidebar','sidebar');
                $response->add('footer','footer');
                $response->add('content','school/academic-years');
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }
        else{
            $response->not_found();
        }
    }
}