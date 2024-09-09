<?php
namespace Plugins\admin\Controllers;

use Core\Controllers\BaseController;
use Exception;
use http\Client\Curl\User;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\Models\UserModels\PermissionModel;
use Plugins\admin\Services\UserService;
use Plugins\admin\UserManager\UserManager;
use System\Request;
use System\Response;

class UserController extends BaseController
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
            $response->add('content','users/list');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function create(Request $request, Response $response): void
    {
        if($request->isGet()){
            $response->page->slug = $request->getPath();
            $response->page->title = "Home Page";
            $user_service = new UserService();
            try {
                $response->add('pagetop','preloader');
                $response->add('pagetop','notifications');
                $response->add('topnav','topnav');
                $response->add('sidebar','sidebar');
                $user_roles = $user_service->getRoles();
                $response->add('content','users/create',['roles'=>$user_roles]);
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
            $response->render();
        }
        elseif($request->isPost())
        {
            $username = $request->input('username');
            $email = $request->input('email');
            $password = $request->input('password');
            $role = $request->input('role');

            $response->json(array('status'=>false,'errors'=>array(
                'username'=>"$username username is not available."
            )));
        }
        else{
            $response->not_found();
        }
    }
    #[NoReturn] public function permissions_list(Request $request, Response $response): void{
        $m = new PermissionModel();
        try {
            $draw = $request->jsonInput('draw', 1);
            $start = $request->jsonInput('start', 0);
            $length = $request->jsonInput('length', 10);
            $order = $request->jsonInput('order', []);
            $columns = $request->jsonInput('columns', []);
            $search = $request->jsonInput('search', []);

            $userList = $m->getDataTableResults($columns, $start, $length, $order, $search);
            $response->json($userList);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    #[NoReturn] public function permissions(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Create User Role";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $permission = null;
            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $permission = UserManager::getPermission($id);
            }
            $response->add('content','users/permissions',['permission'=>$permission]);
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function roles(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Create User Role";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $user_roles = UserManager::getRoles();

            $user_role = null;
            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $user_role = UserManager::getRole($id);
                $permissions = UserManager::getPermissions();
                $userPermissions = UserManager::getPermissions($id);
                $response->add('content','users/roles',['roles'=>$user_roles, 'role'=>$user_role,'permissions'=>$permissions,'userPermissions'=>$userPermissions]);
            }
            else{
                $response->add('content','users/roles',['roles'=>$user_roles, 'role'=>$user_role]);
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function create_role(Request $request, Response $response): void
    {
        if($request->isPost())
        {
            $role_id = $request->input('role_id');
            $name = $request->input('name');
            $description = $request->input('description');
            $errors = array();
            $message = "";

            try {
                if(empty($name)){
                    $errors['name'] = "name is required.";
                }
                if($role_id > 0){
                    //Update user role
                    $r = UserManager::updateRole($role_id,$description);
                    if(is_int($r) and $r>0){
                        if(count($errors) == 0 ){
                            $message = "User role '$name' updated";
                        }
                    }
                    else{
                        $errors['db_error'] = "User role '$name' not updated";
                    }
                }
                else{
                    //Create user role
                    if (UserManager::hasUserRole($name)) {
                        $errors['name'] = "$name is already present.";
                    }
                    if(count($errors) == 0 ){
                        UserManager::addUserRole($name,$description);
                        $message = "User role '$name' created";
                    }
                }
            } catch (Exception $e) {
                $errors['exception'] = $e->getMessage();
            }

            $response->json(array('status'=>false,'errors'=> $errors,'message'=>$message), );
        }
        else{
            $response->not_found();
        }
    }
}