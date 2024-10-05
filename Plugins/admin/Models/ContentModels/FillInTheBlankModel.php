<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class FillInTheBlankModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_fill_in_the_blank', 'ID');
    }

    /**
     * @throws Exception
     */
    public function create(int $questionId, int $optionNo, string $correctAnswers,float $marks = null): int
    {
        $data = [
            'question_id' => $questionId,
            'option_no' => $optionNo,
            'correct_answers' => $correctAnswers, // Assuming blanks are stored as JSON
            'marks' => $marks,
        ];

        return $this->insert($data);
    }
}