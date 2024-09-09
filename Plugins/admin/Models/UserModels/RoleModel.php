<?php

namespace Plugins\admin\Models\UserModels;

use Exception;
use System\Model;

class RoleModel extends Model
{
    public function __construct()
    {
        parent::__construct('ya_roles', 'id');
    }

    /**
     * Create a new role with validation.
     *
     * @param string $roleName
     * @param string $roleDescription
     * @param array $permissions
     * @return \Model|int
     * @throws Exception
     */
    public function createRole(string $roleName, string $roleDescription = '', array $permissions = []): Model|int
    {
        // Validate required fields
        if (empty($roleName)) {
            throw new Exception("Role name is required.");
        }

        // Prepare role data
        $roleData = [
            'role_name' => $roleName,
            'role_description' => $roleDescription,
        ];

        try {
            // Insert role into database
            $roleId = $this->insert($roleData);
            // Assign permissions to the role
            foreach ($permissions as $permissionId) {
                $this->assignPermission($roleId, $permissionId);
            }

            return $roleId;
        } catch (Exception $e) {
            // Handle exception, log error, etc.
            throw new Exception("Failed to create role: " . $e->getMessage());
        }
    }


    /**
     * Assign a permission to a role.
     *
     * @param int $roleId
     * @param int $permissionId
     * @return int
     * @throws Exception
     */
    public function assignPermission(int $roleId, int $permissionId):int
    {
        $pm = new Model('ya_role_permissions');
        return $pm->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    /**
     * @throws Exception
     */
    public function updateRole(int $id, string $description): int
    {
        return $this->reset('update')
            ->set('role_description',$description)
            ->where($this->primaryKey,'=',$id)
            ->update();
    }
}
