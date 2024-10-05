<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use System\Model;

class StudentGroupMembersModel extends Model
{
    public function __construct()
    {
        parent::__construct('student_group_members', 'id');
    }

    public function addMember(int $groupId, int $studentId): int
    {
        $memberData = [
            'group_id' => $groupId,
            'student_id' => $studentId,
            'added_at' => date('Y-m-d H:i:s')
        ];

        try {
            return $this->insert($memberData);
        } catch (Exception $e) {
            throw new Exception("Failed to add member to group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function removeMember(int $id): int
    {
        return $this->where($this->primaryKey, '=', $id)->delete();
    }

    /**
     * @throws Exception
     */
    public function getGroupMembers(int $groupId): array
    {
        return $this->where('group_id', '=', $groupId)->getResults();
    }

    /**
     * @throws Exception
     */
    public function getMember(int $id): array
    {
        return $this->where($this->primaryKey, '=', $id)->getResults();
    }

    /**
     * @throws Exception
     */
    public function updateMember(int $id, int $groupId, int $studentId): int
    {
        return $this->reset('update')
            ->set('group_id', $groupId)
            ->set('student_id', $studentId)
            ->where($this->primaryKey, '=', $id)
            ->update();
    }
}