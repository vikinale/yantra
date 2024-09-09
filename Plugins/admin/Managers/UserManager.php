<?php
namespace Plugins\admin\UserManager;

use Exception;
use PDO;
use Plugins\admin\Models\UserModels\PermissionModel;
use Plugins\admin\Models\UserModels\RoleModel;
use Plugins\admin\Models\UserModels\UserPermissionModel;
use Plugins\admin\Models\UserModels\UserRoleModel;

class UserManager {


    /**
     * @throws Exception
     */
    public static function getRoles(int $userId = null): array
    {
        if($userId != null && $userId > 0){
            $m = new UserRoleModel();
            return $m->select('ya_roles.id as id, ya_roles.role_name as name,ya_roles.role_description as description')->join('ya_roles', 'ya_user_roles.role_id', '=', 'ya_roles.id')
                ->where('ya_user_roles.user_id', '=', $userId)
                ->getResults(PDO::FETCH_OBJ);
        }
        else{
            $m = new RoleModel();
            return $m->select('ya_roles.id as id, ya_roles.role_name as name,ya_roles.role_description as description')->getResults(PDO::FETCH_OBJ);
        }
    }

    /**
     * @throws Exception
     */
    public static function hasUserRole(string $name): bool
    {
        $m = new RoleModel();
        $count = $m->select('ya_roles.id')->where('ya_roles.role_name', '=', $name)->count();
        return $count>0;
    }

    /**
     * @throws Exception
     */
    public static function addUserRole(string $name, string $desc): void
    {
        $m = new RoleModel();
        $m->createRole($name,$desc);
    }

    /**
     * @throws Exception
     */
    public static function getRole(int $id): mixed
    {
        if($id<0)
            return null;
        $m = new RoleModel();
        return $m->select('ya_roles.id as id, ya_roles.role_name as name,ya_roles.role_description as description')->where('id', '=', $id)->getResult(PDO::FETCH_OBJ);
    }

    /**
     * @throws Exception
     */
    public static function updateRole(int $id, string $description): \System\Model|bool|int
    {
        if($id<0)
            return false;
        $m = new RoleModel();
        return  $m->updateRole($id,$description);
    }

    /**
     * @throws Exception
     */
    public static function getPermissions(int $id=0): bool|array
    {
        if($id>0){
            $list =  (new UserPermissionModel())->getUserPermissions($id);
            $new_list = [];
            foreach ($list as $l){
                $new_list[] = $l['permission_id'];
            }
            return $new_list;
        }
        else{
            return (new PermissionModel())->getResults();
        }
    }

    /**
     * @throws Exception
     */
    public static function getPermission(int $id): bool|array|null
    {
        if($id>0){
            return (new PermissionModel())->get($id);
        }
        else{
            return null;
        }
    }
}