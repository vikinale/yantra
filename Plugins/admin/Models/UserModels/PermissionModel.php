<?php

namespace Plugins\admin\Models\UserModels;

use Exception;
use System\Model;

class PermissionModel extends Model
{
    public function __construct()
    {
        parent::__construct('ya_permissions', 'id');
    }

    /**
     * Create a new permission with validation.
     *
     * @param string $permissionName
     * @param string $permissionDescription
     * @return mixed
     * @throws Exception
     */
    public function createPermission(string $permissionName, string $permissionDescription = ''): mixed
    {
        // Validate required fields
        if (empty($permissionName)) {
            throw new Exception("Permission name is required.");
        }

        // Prepare permission data
        $permissionData = [
            'permission_name' => $permissionName,
            'permission_description' => $permissionDescription,
        ];

        try {
            // Insert permission into database
            return $this->insert($permissionData);
        } catch (Exception $e) {
            // Handle exception, log error, etc.
            throw new Exception("Failed to create permission: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getRolePermissions(int $roleId): array
    {
        return (new Model('ya_role_permissions'))
            ->where('role_id','=',$roleId)
            ->getResults();
    }

}