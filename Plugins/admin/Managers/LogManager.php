<?php
namespace Plugins\admin\Managers;

use Exception;
use Plugins\admin\Models\LogModels\StudentLogModel;
use Plugins\admin\Models\LogModels\SystemLogModel;
use Plugins\admin\Models\LogModels\UserLogModel;

class LogManager
{
    /**
     * Log a student action.
     *
     * @param int $studentId
     * @param string $action
     * @param int $schoolId
     * @param int $branchId
     * @return int
     * @throws Exception
     */
    public static function logStudentAction(int $studentId, string $action, int $schoolId, int $branchId): int
    {
        $studentLogModel = new StudentLogModel();
        return $studentLogModel->logStudentAction($studentId, $action, $schoolId, $branchId);
    }

    /**
     * @throws Exception
     */
    public static function logSystemAction(string $logLevel, string $message, ?int $userId = null): int
    {
        $systemLogModel = new SystemLogModel();
        return $systemLogModel->logAction($logLevel, $message, $userId);
    }


    /**
     * Log a user action.
     *
     * @param int $userId
     * @param string $action
     * @param int $schoolId
     * @param int $branchId
     * @return int
     * @throws Exception
     */
    public static function logUserAction(int $userId, string $action, int $schoolId, int $branchId): int
    {
        $userLogModel = new UserLogModel();
        return $userLogModel->logUserAction($userId, $action, $schoolId, $branchId);
    }
}