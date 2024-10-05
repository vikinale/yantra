<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use System\Model;

class StudentGroupModel extends Model
{
    public function __construct()
    {
        parent::__construct('student_group', 'ID');
    }

    /**
     * @throws Exception
     */
    public function createGroup(string $groupName, int $branchID,int $YearID): int
    {
        $groupData = [
            'group_name' => $groupName,
            'branch_id' => $branchID,
            'academic_year ' => $YearID,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            return $this->insert($groupData);
        } catch (Exception $e) {
            throw new Exception("Failed to create student group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updateGroup(int $id, string $groupName, int $branchID,int $YearID): int
    {
        return $this->reset('update')
            ->set('group_name', $groupName)
            ->set('branch_id', $branchID)
            ->set('academic_year', $YearID)
            ->where($this->primaryKey, '=', $id)
            ->update();
    }

    public function deleteGroup(int $id): int
    {
        return $this->where($this->primaryKey, '=', $id)->delete();
    }

    /**
     * @throws Exception
     */
    public function getGroup(int $id): mixed
    {
        return $this->getObject($id);
    }
}
