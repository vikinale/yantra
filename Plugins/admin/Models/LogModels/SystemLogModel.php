<?php
namespace Plugins\admin\Models\LogModels;

use System\Model;
use Exception;

class SystemLogModel extends Model
{
    public function __construct()
    {
        parent::__construct('logs_system', 'ID');
    }

    /**
     * Log a new system action.
     *
     * @param string $logLevel
     * @param string $message
     * @param int|null $userId
     * @return int
     * @throws Exception
     */
    public function logAction(string $logLevel, string $message, ?int $userId = null): int
    {
        if (!in_array($logLevel, ['INFO', 'WARNING', 'ERROR'])) {
            throw new Exception('Invalid log level provided.');
        }

        $data = [
            'log_level' => $logLevel,
            'message' => $message,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }
}
