<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class EducationalBoardModel extends Model
{
    public function __construct()
    {
        parent::__construct('educationalboard', 'ID');
    }

    /**
     * Create a new educational board.
     *
     * @param string $boardName
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public function createEducationalBoard(
        string $boardName,
        string $code,
        ?int $schoolId = null
    ): Model|int {
        if (empty($boardName) || empty($code)) {
            throw new FormException("Board name and code are required.");
        }

        $data = [
            'board_name' => $boardName,
            'code' => $code,
            'school_id' => $schoolId,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create educational board: ");
        }
    }

    /**
     * Update an educational board by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateEducationalBoard(int $id, array $data): int
    {
        if (empty($data)) {
            throw new FormException("No data provided for update.");
        }
        try {
            return $this->where($this->primaryKey,'=',$id)->update($data);
        } catch (Exception $e) {
            throw new FormException("Failed to update school: ");
        }
    }
}