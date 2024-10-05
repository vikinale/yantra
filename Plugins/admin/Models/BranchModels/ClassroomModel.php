<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use PDO;
use System\Model;

class ClassroomModel extends Model
{
    public function __construct()
    {
        parent::__construct('classroom', 'ID');
    }

    public function createClassroom(int $branchId, int $classId, int $divisionId, string $academicYear, string $shortname): int
    {
        $classroomData = [
            'branch_id' => $branchId,
            'class_id' => $classId,
            'division_id' => $divisionId,
            'academic_year' => $academicYear,
            'shortname' => $shortname,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            return $this->insert($classroomData);
        } catch (Exception $e) {
            throw new Exception("Failed to create classroom: " . $e->getMessage());
        }
    }

    public function updateClassroom(int $id, int $branchId, int $classId, int $divisionId, string $academicYear, string $shortname): int
    {
        return $this->reset('update')
            ->set('branch_id', $branchId)
            ->set('class_id', $classId)
            ->set('division_id', $divisionId)
            ->set('academic_year', $academicYear)
            ->set('shortname', $shortname)
            ->where($this->primaryKey, '=', $id)
            ->update();
    }

    public function deleteClassroom(int $id): int
    {
        return $this->where($this->primaryKey, '=', $id)->delete();
    }

    /**
     * @throws Exception
     */
    public function getClassroom(int $id): mixed
    {
        return $this->where($this->primaryKey, '=', $id)->getResult(PDO::FETCH_OBJ);
    }
}