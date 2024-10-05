<?php
namespace Plugins\admin\Controllers;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\Models\UserModels\PermissionModel;
use Plugins\admin\Models\UserModels\UserModel;
use Plugins\admin\managers\UserManager;
use System\Request;
use System\Response;

class UserController extends AdminController
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
            $response->page->title = "User Create";
            try {
                $response->add('pagetop','preloader');
                $response->add('pagetop','notifications');
                $response->add('topnav','topnav');
                $response->add('sidebar','sidebar');
                $roles= UserManager::getRoles();
                $response->add('content','users/create',array('roles'=>$roles));
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
                'username'=>"$username username is not available.",
                'user-role'=>" ddad username is not available."
            )));
        }
        else{
            $response->not_found();
        }
    }

    #[NoReturn] public function table(Request $request, Response $response): void{
        $m = new UserModel();
        try {
            $columns =['username'=>'user_login',"user_email"=> "user_email","first_name"=> "first_name","last_name"=> "last_name"];
            $select = "";
            foreach ($columns as $column => $name) {
                $select .= "$name as $column, ";
            }
            $select = trim($select," ,");
            $response->dataTable($request,$m->select($select),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
    #[NoReturn] public function permissions_list(Request $request, Response $response): void{
        $m = new PermissionModel();
        try {
            $columns =['ID'=>'id','name'=>'permission_name',"description"=> "permission_description"];
            $select = "";
            foreach ($columns as $column => $name) {
                $select .= "$name as $column, ";
            }
            $select = trim($select," ,");
            $response->dataTable($request,$m->select($select),$columns);
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
    #[NoReturn] public function permission_create(Request $request, Response $response): void
    {
        if($request->isPost())
        {
            $name = $request->input('permission_name');
            $description = $request->input('permission_description');
            $id = $request->input('permission_id');
            $errors = array();
            $message = "";
            //Update existing permission
            if(!empty($id)){
                try {
                    UserManager::updatePermission($id, $description);
                    $message= "Permission update successfully...";
                } catch (Exception $e) {
                    $errors[]=$e->getMessage();
                }
            }
            else{
                //Add new permission
                try {
                    if(UserManager::hasPermission($name)){
                        $errors['permission_name']="Duplicate name \"$name\"";
                    }
                    else{
                        UserManager::addPermission($name, $description);
                        $message= "Permission added successfully...";
                    }
                }
                catch (Exception $e) {
                    $errors[]=$e->getMessage();
                }
            }
            $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
        }
        else{
            $response->not_found();
        }
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
                $response->add('content','users/roles',['roles'=>$user_roles, 'role'=>$user_role,'permissions'=>$permissions]);
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
            $permissions = $request->input('permissions');
            $errors = array();
            $message = "";

            try {
                if(empty($name)){
                    $errors['name'] = "name is required.";
                }
                if($role_id > 0){
                    //Update user role
                    $r = UserManager::updateRole($role_id,$description,$permissions);
                    if(is_int($r) and $r>=0){
                        if(count($errors) == 0 ){
                            $message = "User role '$name' updated" ;
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