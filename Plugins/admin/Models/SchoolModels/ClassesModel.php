<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class ClassesModel extends Model
{
    public function __construct()
    {
        parent::__construct('classes', 'ID');
    }

    /**
     * Create a new class.
     *
     * @param string $className
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public function createClass(
        string $className,
        string $code,
        ?int $schoolId = null
    ): Model|int {
        if (empty($className) || empty($code)) {
            throw new FormException("Class name and code are required.");
        }

        $data = [
            'class_name' => $className,
            'code' => $code,
            'school_id' => $schoolId,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create class: ");
        }
    }

    /**
     * Update a class by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateClass(int $id, array $data): int
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
