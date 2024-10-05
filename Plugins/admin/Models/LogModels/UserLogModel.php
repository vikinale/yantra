<?php
namespace Plugins\admin\Models\LogModels;

use System\Model;
use Exception;

class UserLogModel extends Model
{
    public function __construct()
    {
        parent::__construct('user_logs', 'ID');
    }

    /**
     * Log a new user action.
     *
     * @param int $userId
     * @param string $action
     * @param int $schoolId
     * @param int $branchId
     * @return int
     * @throws Exception
     */
    public function logUserAction(int $userId, string $action, int $schoolId, int $branchId): int
    {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'school_id' => $schoolId,
            'branch_id' => $branchId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }
}
