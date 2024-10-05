<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class QuestionModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_questions', 'ID');
    }

    /**
     * @throws Exception
     */
    public function create(
        string $questionText,
        string $questionType,
        string $correctOptions = null,
               $mediaType = null,
               $mediaUrl = null,
               $textbookId = null,
               $chapterId = null,
               $sectionId = null,
        float $marks = null,
        string $hint = null,
        int $level = 1, // Default level
        bool $important = false // Default to false
    ): int {
        $data = [
            'question_text' => $questionText,
            'question_type' => $questionType,
            'correct_options' => $correctOptions,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'textbook_id' => $textbookId,
            'chapter_id' => $chapterId,
            'section_id' => $sectionId,
            'marks' => $marks,
            'hint' => $hint,
            'level' => $level,
            'important' => $important,
        ];

        return $this->insert($data);
    }

}
