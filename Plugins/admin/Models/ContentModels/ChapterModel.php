<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class ChapterModel extends Model
{
    public function __construct()
    {
        parent::__construct('chapters', 'ID');
    }

    /**
     * Create a new chapter.
     *
     * @throws Exception
     */
    public function create(string $chapterTitle, int $textbookId, int $chapterNumber, string $summary = null): int
    {
        $data = [
            'chapter_title' => $chapterTitle,
            'textbook_id' => $textbookId,
            'chapter_number' => $chapterNumber,
            'summary' => $summary,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new Exception("DB error while inserting Chapter Model. Error:".$e->getMessage());
        }
    }
}
