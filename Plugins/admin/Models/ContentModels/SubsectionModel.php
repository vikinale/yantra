<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class SubsectionModel extends Model
{
    public function __construct()
    {
        parent::__construct('subsections', 'ID');
    }

    /**
     * Create a new subsection.
     *
     * @throws Exception
     */
    public function create(string $subsectionTitle, int $sectionId, int $subsectionNumber, string $content = null): int
    {
        $data = [
            'subsection_title' => $subsectionTitle,
            'section_id' => $sectionId,
            'subsection_number' => $subsectionNumber,
            'content' => $content,
        ];

        return $this->insert($data);
    }
}
