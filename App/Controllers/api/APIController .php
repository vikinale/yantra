<?php
namespace App\Controllers;

use Exception;
use System\Request;
use System\Response;
use Core\Controllers\APIController;

abstract class APIController extends APIController
{
    /**
     * Automatically authenticate requests if required.
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response,false);
    }

    /* -------------------------
     * Route registration
    ------------------------- */
    public static function runOnce(): void
    {
        global $router;
        // GET pages
        $router->addRoute('POST', '/api', 'Controllers\admin\YTAdmin', 'index');
        $router->addRoute('POST', '/admin/login', 'Controllers\admin\YTAdmin', 'loginPage');
    }

}