<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class MasterDataModel extends Model
{
    public function __construct()
    {
        parent::__construct('masterdata', 'ID');
    }

    /**
     * Create new master data.
     *
     * @param string $dataType
     * @param string $code
     * @param string $name
     * @return Model|int
     * @throws Exception
     */
    public function createMasterData(
        string $dataType,
        string $code,
        string $name
    ): Model|int {
        if (empty($dataType) || empty($code) || empty($name)) {
            throw new FormException("Data type, code, and name are required.");
        }

        $data = [
            'data_type' => $dataType,
            'code' => $code,
            'name' => $name,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create master data: ");
        }
    }

    /**
     * Update master data by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateMasterData(int $id, array $data): int
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