<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class OptionModel extends Model
{
    public function __construct()
    {
        parent::__construct('cnt_options', 'ID');
    }

    /**
     * @throws Exception
     */
    public function create(int $questionId, int $optionNumber, string $optionText, ?string $mediaType = null, ?string $mediaUrl = null, float $marks = null): int
    {
        if ($optionNumber < 1 || $optionNumber > 8) {
            throw new Exception('Option number must be between 1 and 8.');
        }

        $data = [
            'question_id' => $questionId,
            'option_number' => $optionNumber,
            'option_text' => $optionText,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'marks' => $marks,
        ];

        return $this->insert($data);
    }
}