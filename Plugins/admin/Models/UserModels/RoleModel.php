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
     * @return Model|int
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
            'role_permissions' => json_encode($permissions),
        ];

        try {
            return $this->insert($roleData);
        } catch (Exception $e) {
            // Handle exception, log error, etc.
            throw new Exception("Failed to create role: " . $e->getMessage());
        }
    }



    /**
     * @throws Exception
     */
    public function updateRole(int $id, string $description = null, array $permissions = null): int
    {
        if(is_null($description) && is_null($permissions)){
            return 0;
        }
        $x = $this->reset('update');
        if(!is_null($description)){
            $x= $x->set('role_description',$description);
        }
        if(!is_null($permissions)){
            $x= $x->set('role_permissions',json_encode($permissions));
        }
        return $x->where($this->primaryKey,'=',$id)->update();
    }
}
