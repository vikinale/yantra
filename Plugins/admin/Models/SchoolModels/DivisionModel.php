<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class DivisionModel extends Model
{
    public function __construct()
    {
        parent::__construct('division', 'ID');
    }

    /**
     * Create a new division.
     *
     * @param string $divisionName
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public function createDivision(
        string $divisionName,
        string $code,
        ?int $schoolId = null
    ): Model|int {
        if (empty($divisionName) || empty($code)) {
            throw new FormException("Division name and code are required.");
        }

        $data = [
            'division_name' => $divisionName,
            'code' => $code,
            'school_id' => $schoolId,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create division: ");
        }
    }

    /**
     * Update a division by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateDivision(int $id, array $data): int
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