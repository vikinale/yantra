<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class UnitModel extends Model
{
    public function __construct()
    {
        parent::__construct('units', 'ID');
    }

    /**
     * Create a new unit.
     *
     * @param string $unitName
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public function createUnit(
        string $unitName,
        string $code,
        ?int $schoolId = null
    ): Model|int {
        if (empty($unitName) || empty($code)) {
            throw new FormException("Unit name and code are required.");
        }

        $data = [
            'unit_name' => $unitName,
            'code' => $code,
            'school_id' => $schoolId,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create unit: ".$e->getMessage());
        }
    }

    /**
     * Update a unit by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateUnit(int $id, array $data): int
    {
        if (empty($data)) {
            throw new FormException("No data provided for update.");
        }
        try {
            return $this->where($this->primaryKey,'=',$id)->update($data);
        } catch (Exception $e) {
            throw new FormException("Failed to update: ");
        }
    }
}