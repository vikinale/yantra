<?php

namespace Plugins\admin\Controllers;

use Core\Controllers\BaseController;
use Core\LowCode\LowCode;
use Core\LowCode\ShortcodeManager;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use System\Request;
use System\Response;

class HomeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Home Page";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->set('main_div_class',"introduction-farm");
            $response->add('content','dashboard');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function login(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        if($request->isGet()){
            $response->page->title  = "Yantra Login";
            $response->page->layout = "blank";
            try {
                $response->add('content', 'login');
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }
        else if($request->isPost()){
            $response->json(array('status'=>True));
        }
        else{
            $response->not_found();
        }
    }

    #[NoReturn] public function signup(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        if($request->isGet()){
            $response->page->title  = "Yantra Sign Up";
            $response->page->layout = "blank";
            try {
                $response->add('content', 'signup');
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }
        else if($request->isPost()){
            $response->json(array('status'=>True));
        }
        else{
            $response->not_found();
        }
    }
}