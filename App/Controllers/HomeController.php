<?php

namespace Controllers {

    use Core\Controllers\BaseController;
    use JetBrains\PhpStorm\NoReturn;
    use System\Controller;
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
            $response->page->title = "Home Page";
            $response->add('content','home');
            $response->render();
        }
    }
}