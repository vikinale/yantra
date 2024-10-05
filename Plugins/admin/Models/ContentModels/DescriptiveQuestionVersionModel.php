<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class DescriptiveQuestionVersionModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_descriptive_question_versions', 'ID');
    }

    /**
     * Create a new version for a descriptive question.
     *
     * @throws Exception
     */
    public function create(int $descriptiveQuestionId, int $version, string $questionText): int
    {
        $data = [
            'question_id' => $descriptiveQuestionId,
            'version' => $version,
            'question_text' => $questionText
        ];

        return $this->insert($data);
    }
}
