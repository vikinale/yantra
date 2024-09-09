<?php

namespace Controllers {

    use Core\Controllers\BaseController;
    use Core\LowCode\ModuleNotFoundException;
    use JetBrains\PhpStorm\NoReturn;
    use System\Request;
    use System\Response;

    class Blogs extends BaseController
    {

        public function __construct()
        {
            parent::__construct();
        }

        /**
         * @throws ModuleNotFoundException
         */
        #[NoReturn] public function index(Request $request, Response $response): void
        {
            $this->registerBlock('common');
            $response->page->title = "Blogs Archive";
            try {
                $response->add('content', 'blogs');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }

        #[NoReturn] public function single(Request $request, Response $response): void
        {
            $slug = $request->getPath(1);
            $response->page->title = "Single Blog Sample";
            try {
                $response->add('content', 'blog');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }
    }
}