<?php

namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class QuizScheduleModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_quiz_schedules', 'ID');
    }

    /**
     * Create a new quiz schedule.
     *
     * @throws Exception
     */
    public function create(int $quizId, int $branchId, string $startDateTime, string $endDateTime, string $resultPublishDateTime): int
    {
        $data = [
            'quiz_id' => $quizId,
            'branch_id' => $branchId,
            'start_date_time' => $startDateTime,
            'end_date_time' => $endDateTime,
            'result_publish_date_time' => $resultPublishDateTime,
        ];

        return $this->insert($data);
    }


    /**
     * Get all schedules for a specific quiz.
     *
     * @throws Exception
     */
    public function getByQuizId(int $quizId): array
    {
        return $this->where('quiz_id', '=', $quizId)->getResults();
    }

    /**
     * Get all schedules for a specific branch.
     *
     * @throws Exception
     */
    public function getByBranchId(int $branchId): array
    {
        return $this->where('branch_id', '=', $branchId)->getResults();
    }

}
