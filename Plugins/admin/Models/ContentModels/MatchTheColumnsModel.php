<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class MatchTheColumnsModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_match_the_columns', 'ID');
    }

    /**
     * Create a new match-the-columns entry.
     *
     * @param int $questionId
     * @param string $leftItem
     * @param string $rightItem
     * @param float|null $marks
     * @return int
     * @throws Exception
     */
    public function create(int $questionId, string $leftItem, string $rightItem, float $marks = null): int
    {
        $data = [
            'question_id' => $questionId,
            'left_item' => $leftItem,
            'right_item' => $rightItem,
            'marks' => $marks,
        ];

        return $this->insert($data);
    }


    /**
     * Get all pairs for a specific question.
     *
     * @param int $questionId
     * @return array
     * @throws Exception
     */
    public function getPairsByQuestionId(int $questionId): array
    {
        return $this->where('question_id', '=', $questionId)->getResults();
    }
}