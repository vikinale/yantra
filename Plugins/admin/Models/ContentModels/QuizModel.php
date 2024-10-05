<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class QuizModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_quizzes', 'ID');
    }

    /**
     * Create a new quiz.
     *
     * @throws Exception
     */
    public function create(string $quizTitle, string $quizType, int $textbookId = null, int $chapterId = null, int $sectionId = null, int $schoolId = null, float $totalMarks, int $totalDuration, string $questionIds = null): int
    {
        $data = [
            'quiz_title' => $quizTitle,
            'quiz_type' => $quizType,
            'textbook_id' => $textbookId,
            'chapter_id' => $chapterId,
            'section_id' => $sectionId,
            'school_id' => $schoolId,
            'total_marks' => $totalMarks,
            'total_duration' => $totalDuration,
            'question_ids' => $questionIds,
        ];

        return $this->insert($data);
    }
}
