<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class DocumentModel extends Model
{
    public function __construct()
    {
        // Initialize with the cnt_documents table and primary key 'ID'
        parent::__construct('cnt_documents', 'ID');
    }

    /**
     * @throws Exception
     */
    public function create(
        int $schoolId,
        int $branchId,
        string $academicYear,
        string $subject,
        int $classId,
        int $classroomId,
        string $item,
        int $textbookId,
        int $chapterId,
        string $unit,
        string $itemName,
        string $itemGroup,
        string $documentLinks,
        string $realDate
    ): int
    {
        $data = [
            'school_id'      => $schoolId,
            'branch_id'      => $branchId,
            'academic_year'  => $academicYear,
            'subject'        => $subject,
            'class_id'       => $classId,
            'classroom_id'   => $classroomId,
            'item'           => $item,
            'textbook_id'    => $textbookId,
            'chapter_id'     => $chapterId,
            'unit'           => $unit,
            'item_name'      => $itemName,
            'item_group'     => $itemGroup,
            'document_links' => $documentLinks,
            'real_date'      => $realDate,
        ];

        return $this->insert($data);
    }
}
