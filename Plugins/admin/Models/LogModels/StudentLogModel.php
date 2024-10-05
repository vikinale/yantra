<?php
namespace Plugins\admin\Models\LogModels;

use System\Model;
use Exception;

class StudentLogModel extends Model
{
    public function __construct()
    {
        parent::__construct('student_logs', 'ID');
    }

    /**
     * Log a new student action.
     *
     * @param int $studentId
     * @param string $action
     * @param int $schoolId
     * @param int $branchId
     * @return int
     * @throws Exception
     */
    public function logStudentAction(int $studentId, string $action, int $schoolId, int $branchId): int
    {
        $data = [
            'student_id' => $studentId,
            'action' => $action,
            'school_id' => $schoolId,
            'branch_id' => $branchId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }
}
