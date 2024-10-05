<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use System\Model;

class StudentModel extends Model
{
    public function __construct()
    {
        parent::__construct('student', 'ID');
    }

    // Create a new student record

    /**
     * @throws Exception
     */
    public function createStudent(
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
        $studentData = [
            'student_id' => $studentId,
            'roll_no' => $rollNo,
            'year' => $year,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'student_email' => $studentEmail,
            'school_id' => $schoolId,
            'branch_id' => $branchId,
            'division' => $division,
            'student_class' => $class,
            'status' => $status,
            'classroom' => $classroom,
            'mother_name' => $motherName,
            'father_name' => $fatherName,
            'father_email' => $fatherEmail,
            'father_mobile' => $fatherMobile,
            'mother_email' => $motherEmail,
            'mother_mobile' => $motherMobile,
            'address' => $address,
            'location' => $location,
            'pin' => $pin,
            'birth_date' => $birthDate,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            return $this->insert($studentData);
        } catch (Exception $e) {
            throw new Exception("Failed to create student: " . $e->getMessage());
        }
    }

    // Update an existing student record
    public function updateStudent(
        int $id, string $firstName, string $lastName, string $username,
        string $studentEmail, string $status, ?string $motherName = null,
        ?string $fatherName = null, ?string $fatherEmail = null,
        ?string $fatherMobile = null, ?string $motherEmail = null,
        ?string $motherMobile = null, ?string $address = null,
        ?string $location = null, ?string $birthDate = null
    ): int {
        $this->reset('update')
            ->set('first_name', $firstName)
            ->set('last_name', $lastName)
            ->set('username', $username)
            ->set('student_email', $studentEmail)
            ->set('status', $status)
            ->set('mother_name', $motherName)
            ->set('father_name', $fatherName)
            ->set('father_email', $fatherEmail)
            ->set('father_mobile', $fatherMobile)
            ->set('mother_email', $motherEmail)
            ->set('mother_mobile', $motherMobile)
            ->set('address', $address)
            ->set('location', $location)
            ->set('birth_date', $birthDate)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where($this->primaryKey, '=', $id);

        try {
            return $this->update();
        } catch (Exception $e) {
            throw new Exception("Failed to update student: " . $e->getMessage());
        }
    }
}
