<?php

namespace Controllers {

    use System\Controllers\Controller;
    use Exception;
    
    use System\Request;
    use System\Response;

    abstract class PublicController extends Controller
    {
        public function __construct(Request $request, Response $response)
        {
            parent::__construct($request, $response);
        }


        protected static function getSelectList($columns): string
        {
            $select = "";
            foreach ($columns as $column => $name) {
                $select .= "$name as $column, ";
            }
            return trim($select," ,");
        }
        
        protected function renderPage(string $title, string $view, array $data=[]): void
        {
            $this->response->init();
            $this->response->setPageProperty("slug", $this->request->getPath());
            $this->response->setPageProperty("title", $title);
            $this->response->setMeta('robots', 'noindex, nofollow');
            try {
                $this->response->add('header', 'header', $data);
                $this->response->setPageContent($view, $data);
                $this->response->add('footer', 'footer');
            } catch (Exception $e) {
                echo $e->getMessage();
                error_log("Error rendering page [$title]: " . $e->getMessage());
            }
            $this->response->render();
        }
            
    }
}