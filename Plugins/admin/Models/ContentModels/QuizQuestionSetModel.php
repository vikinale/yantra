<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class QuizQuestionSetModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_quiz_question_sets', 'ID');
    }

    /**
     * Create a new quiz question set.
     *
     * @throws Exception
     */
    public function create(int $quizId, int $level, string $referenceType, int $referenceId, int $numQuestions): int
    {
        $data = [
            'quiz_id' => $quizId,
            'level' => $level,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'num_questions' => $numQuestions,
        ];

        return $this->insert($data);
    }
}
