<?php

namespace Plugins\admin\Managers;

use Exception;
use Plugins\admin\Models\BranchModels\BranchModel;
use Plugins\admin\Models\BranchModels\BranchSettingsModel;
use Plugins\admin\Models\BranchModels\ClassroomModel;
use Plugins\admin\Models\BranchModels\StudentGroupMembersModel;
use Plugins\admin\Models\BranchModels\StudentGroupModel;
use Plugins\admin\Models\BranchModels\StudentModel;
use System\QueryBuilder;

class BranchManager
{
    // Static method to get the BranchTable query
    public static function BranchTable(): QueryBuilder|BranchModel
    {
        $m = new BranchModel();
        return $m->select('ID as id, branch_name as name, status, location, address, city, pincode as pin, contact as phone, email, created as stamp');
    }
    public static function StudentGroupTable(): QueryBuilder|BranchModel
    {
        $m = new StudentGroupModel();
        return $m->select('student_group.ID as id,group_name as name, b.branch_name as branch, COALESCE(a.year_name, "") as year_name')
            ->join('branch as b', 'b.ID','=','student_group.branch_id')
            ->join('academicyear as a', 'a.ID','=','student_group.academic_year','LEFT');
    }

    public static function ClassroomTable(): QueryBuilder|BranchModel
    {
        $m = new ClassroomModel();
        return $m->select('classroom.ID as id, shortname, b.branch_name as branch, c.class_name as class, d.division_name as division, a.year_name as year_name')
            ->join('branch as b', 'b.ID','=','classroom.branch_id')
            ->join('classes as c', 'c.ID','=','classroom.class_id')
            ->join('division as d', 'd.ID','=','classroom.division_id')
            ->join('academicyear as a', 'a.ID','=','classroom.academic_year')
            ->where('b.status','=','1');
    }

    // Branch-related operations

    /**
     * @throws Exception
     */
    public static function createBranch(string $branchName, string $status, string $location, string $address, string $city, string $pincode, string $contact, string $email, ?int $schoolId = null, ?string $logo = null): int
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->createBranch($branchName, $status, $location, $address, $city, $pincode, $contact, $email, $schoolId, $logo);
        } catch (Exception $e) {
            throw new Exception("Error creating branch: " . $e->getMessage());
        }
    }

    /**
     * @param string $studentId
     * @param string $firstName
     * @param string $lastName
     * @param string $username
     * @param string $studentEmail
     * @param int $schoolId
     * @param int|null $branchId
     * @param string|null $division
     * @param string|null $class
     * @param string $status
     * @param string|null $motherName
     * @param string|null $fatherName
     * @param string|null $fatherEmail
     * @param string|null $fatherMobile
     * @param string|null $motherEmail
     * @param string|null $motherMobile
     * @param string|null $address
     * @param string|null $location
     * @param string|null $birthDate
     * @return int
     * @throws Exception
     */
    public static function createStudent(
        string $studentId, string $firstName, string $lastName, string $username,
        string $studentEmail, int $schoolId, string $year, ?int $branchId = null,
        ?string $division = null, ?string $class = null, string $status = 'new',
        ?string $motherName = null, ?string $fatherName = null,
        ?string $fatherEmail = null, ?string $fatherMobile = null,
        ?string $motherEmail = null, ?string $motherMobile = null,
        ?string $address = null, ?string $location = null,
        ?string $birthDate = null,
        ?string $middleName =null, ?string $classroom =null,
        ?string $rollNo = null, ?string $pin = null
    ): int {
        try {
            $studentModel = new StudentModel();
            return $studentModel->createStudent(
                $studentId, $firstName, $lastName, $username, $studentEmail,
                $schoolId, $year,$branchId, $division, $class, $status,
                $motherName, $fatherName, $fatherEmail, $fatherMobile,
                $motherEmail, $motherMobile, $address, $location, $birthDate,$middleName,$classroom,$rollNo,$pin
            );
        } catch (Exception $e) {
            throw new Exception("BranchManager failed to create student: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function updateBranchLogo(int $branchId, ?string $logo = null): int
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->updateBranchLogo($branchId, $logo);
        } catch (Exception $e) {
            throw new Exception("Error updating branch Logo: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function updateBranch(int $branchId, ?string $branchName, ?string $status, ?string $location, ?string $address, ?string $city, ?string $pincode, ?string $contact, ?string $email, ?string $logo = null): bool
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->updateBranch($branchId, $branchName, $status, $location, $address, $city, $pincode, $contact, $email, $logo);
        } catch (Exception $e) {
            throw new Exception("Error updating branch: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function deleteBranch(int $branchId): bool
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->delete($branchId);
        } catch (Exception $e) {
            throw new Exception("Error deleting branch: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getBranchById(int $branchId): ?array
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->get($branchId);
        } catch (Exception $e) {
            throw new Exception("Error fetching branch: " . $e->getMessage());
        }
    }

    public static function getBranchesBySchool(int $schoolId): bool|array
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->getBranchesBySchool($schoolId);
        } catch (Exception $e) {
            throw new Exception("Error fetching branches for school: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getAllBranches(): array
    {
        try {
            $branchModel = new BranchModel();
            return $branchModel->getAllBranches();
        } catch (Exception $e) {
            throw new Exception("Error fetching all branches: " . $e->getMessage());
        }
    }

    public static function getBranchSelectList($selected = null):array{
        try {
            $branchModel = new BranchModel();
            return $branchModel->where('status','=','1')->getSelectList('ID','branch_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @throws Exception
     */
    public static function createBranchSetting(int $branchId, string $settingKey, string $settingValue): int
    {
        try {
            $branchSettingsModel = new BranchSettingsModel();
            return $branchSettingsModel->createBranchSetting($settingKey, $settingValue, $branchId);
        } catch (Exception $e) {
            throw new Exception("Error creating branch setting: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function updateBranchSetting(int $settingId, string $settingValue): int
    {
        try {
            $branchSettingsModel = new BranchSettingsModel();
            return $branchSettingsModel->updateBranchSetting($settingId, $settingValue);
        } catch (Exception $e) {
            throw new Exception("Error updating branch setting: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getSettingsByBranch(int $branchId): array
    {
        try {
            $branchSettingsModel = new BranchSettingsModel();
            return $branchSettingsModel->getSettingsByBranch($branchId);
        } catch (Exception $e) {
            throw new Exception("Error fetching branch settings: " . $e->getMessage());
        }
    }

    // Classroom-related operations

    /**
     * @throws Exception
     */
    public static function createClassroom(int $branchId, int $classId, int $divisionId, string $academicYear, string $shortname): int
    {
        try {
            $classroomModel = new ClassroomModel();
            return $classroomModel->createClassroom($branchId, $classId, $divisionId, $academicYear, $shortname);
        } catch (Exception $e) {
            throw new Exception("Error creating classroom: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function updateClassroom(int $id, int $branchId, int $classId, int $divisionId, string $academicYear, string $shortname): int
    {
        try {
            $classroomModel = new ClassroomModel();
            return $classroomModel->updateClassroom($id, $branchId, $classId, $divisionId, $academicYear, $shortname);
        } catch (Exception $e) {
            throw new Exception("Error updating classroom: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function deleteClassroom(int $id): int
    {
        try {
            $classroomModel = new ClassroomModel();
            return $classroomModel->deleteClassroom($id);
        } catch (Exception $e) {
            throw new Exception("Error deleting classroom: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getClassroom(int $id): mixed
    {
        try {
            $classroomModel = new ClassroomModel();
            return $classroomModel->getClassroom($id);
        } catch (Exception $e) {
            throw new Exception("Error fetching classroom: " . $e->getMessage());
        }
    }

    // Student group-related operations

    /**
     * @throws Exception
     */
    public static function createStudentGroup(string $groupName,int $branchID,int $yearID): int
    {
        try {
            $studentGroupModel = new StudentGroupModel();
            return $studentGroupModel->createGroup($groupName,$branchID,$yearID);
        } catch (Exception $e) {
            throw new Exception("Error creating student group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function updateStudentGroup(int $groupId, string $groupName,int $branchID,int $yearID): int
    {
        try {
            $studentGroupModel = new StudentGroupModel();
            return $studentGroupModel->updateGroup($groupId, $groupName,$branchID,$yearID);
        } catch (Exception $e) {
            throw new Exception("Error updating student group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function deleteStudentGroup(int $groupId): int
    {
        try {
            $studentGroupModel = new StudentGroupModel();
            return $studentGroupModel->deleteGroup($groupId);
        } catch (Exception $e) {
            throw new Exception("Error deleting student group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getStudentGroup(int $groupId): mixed
    {
        try {
            $studentGroupModel = new StudentGroupModel();
            return $studentGroupModel->getGroup($groupId);
        } catch (Exception $e) {
            throw new Exception("Error fetching student group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function addStudentToGroup(int $groupId, int $studentId): int
    {
        try {
            $studentGroupMembersModel = new StudentGroupMembersModel();
            return $studentGroupMembersModel->addMember($groupId, $studentId);
        } catch (Exception $e) {
            throw new Exception("Error adding student to group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function removeStudentFromGroup(int $memberId): int
    {
        try {
            $studentGroupMembersModel = new StudentGroupMembersModel();
            return $studentGroupMembersModel->removeMember($memberId);
        } catch (Exception $e) {
            throw new Exception("Error removing student from group: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getGroupMembers(int $groupId): array
    {
        try {
            $studentGroupMembersModel = new StudentGroupMembersModel();
            return $studentGroupMembersModel->getGroupMembers($groupId);
        } catch (Exception $e) {
            throw new Exception("Error fetching group members: " . $e->getMessage());
        }
    }

    // Student-related operations

    /**
     * @throws Exception
     */
    public static function getStudent(int $studentId): array
    {
        try {
            $studentModel = new StudentModel();
            return $studentModel->get($studentId);
        } catch (Exception $e) {
            throw new Exception("Error fetching student: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function getAllStudents(): array
    {
        try {
            $studentModel = new StudentModel();
            return $studentModel->getResults();
        } catch (Exception $e) {
            throw new Exception("Error fetching students: " . $e->getMessage());
        }
    }
}
