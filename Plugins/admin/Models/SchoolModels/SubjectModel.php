<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class SubjectModel extends Model
{
    public function __construct()
    {
        parent::__construct('subject', 'ID');
    }

    /**
     * Create a new subject.
     *
     * @param string $subjectName
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public function createSubject(
        string $subjectName,
        string $code,
        ?int $schoolId = null
    ): Model|int {
        if (empty($subjectName) || empty($code)) {
            throw new FormException("Subject name and code are required.");
        }

        $data = [
            'subject_name' => $subjectName,
            'code' => $code,
            'school_id' => $schoolId,
        ];

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create subject: ");
        }
    }

    /**
     * Update a subject by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateSubject(int $id, array $data): int
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