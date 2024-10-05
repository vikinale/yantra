<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class SectionModel extends Model
{
    public function __construct()
    {
        parent::__construct('sections', 'ID');
    }

    /**
     * Create a new section.
     *
     * @throws Exception
     */
    public function create(string $sectionTitle, int $chapterId, int $sectionNumber, string $content = null): int
    {
        $data = [
            'section_title' => $sectionTitle,
            'chapter_id' => $chapterId,
            'section_number' => $sectionNumber,
            'content' => $content,
        ];

        return $this->insert($data);
    }
}