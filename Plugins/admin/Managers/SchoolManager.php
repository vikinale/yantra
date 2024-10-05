<?php

namespace Plugins\admin\managers;

use Exception;
use Plugins\admin\Models\AcademicYearModel;
use Plugins\admin\Models\ClassesModel;
use Plugins\admin\Models\DivisionModel;
use Plugins\admin\Models\EducationalBoardModel;
use Plugins\admin\Models\MasterDataModel;
use Plugins\admin\Models\SubjectModel;
use Plugins\admin\Models\UnitModel;
use System\Model;

class SchoolManager
{
    /**
     * Create a new academic year.
     *
     * @param string $yearName
     * @param string $startDate
     * @param string $endDate
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public static function createAcademicYear(string $yearName, string $startDate, string $endDate, ?int $schoolId = null): Model|int
    {
        return (new AcademicYearModel())->createAcademicYear($yearName, $startDate, $endDate, $schoolId);
    }

    /**
     * @throws Exception
     */
    public static function getAcademicYearByID(int $id): bool|array
    {
        return (new AcademicYearModel())->get($id);
    }

    /**
     * Update an academic year by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateAcademicYear(int $id, array $data): int
    {
        return (new AcademicYearModel())->updateAcademicYear($id, $data);
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
    public static function createClass(string $className, string $code, ?int $schoolId = null): Model|int
    {
        return (new ClassesModel())->createClass($className, $code, $schoolId);
    }

    /**
     * Update a class by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateClass(int $id, array $data): int
    {
        return (new ClassesModel())->updateClass($id, $data);
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
    public static function createDivision(string $divisionName, string $code, ?int $schoolId = null): Model|int
    {
        return (new DivisionModel())->createDivision($divisionName, $code, $schoolId);
    }

    /**
     * Update a division by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateDivision(int $id, array $data): int
    {
        return (new DivisionModel())->updateDivision($id, $data);
    }

    /**
     * Create a new educational board.
     *
     * @param string $boardName
     * @param string $code
     * @param int|null $schoolId
     * @return Model|int
     * @throws Exception
     */
    public static function createEducationalBoard(string $boardName, string $code, ?int $schoolId = null): Model|int
    {
        return (new EducationalBoardModel())->createEducationalBoard($boardName, $code, $schoolId);
    }

    /**
     * Update an educational board by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateEducationalBoard(int $id, array $data): int
    {
        return (new EducationalBoardModel())->updateEducationalBoard($id, $data);
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
    public static function createMasterData(string $dataType, string $code, string $name): Model|int
    {
        return (new MasterDataModel())->createMasterData($dataType, $code, $name);
    }

    /**
     * Update master data by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateMasterData(int $id, array $data): int
    {
        return (new MasterDataModel())->updateMasterData($id, $data);
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
    public static function createSubject(string $subjectName, string $code, ?int $schoolId = null): Model|int
    {
        return (new SubjectModel())->createSubject($subjectName, $code, $schoolId);
    }

    /**
     * @throws Exception
     */
    public static function createUnit(string $unitName, string $code, ?int $schoolId = null): Model|int
    {
        return (new UnitModel())->createUnit($unitName, $code, $schoolId);
    }

    /**
     * Update a subject by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function updateSubject(int $id, array $data): int
    {
        return (new SubjectModel())->updateSubject($id, $data);
    }

    /**
     * @throws Exception
     */
    public static function updateUnit(int $id, array $data): int
    {
        return (new UnitModel())->updateUnit($id, $data);
    }

    /**
     * @throws Exception
     */
    public static function getEducationalBoardByID(int $id): bool|array
    {
        return (new EducationalBoardModel())->get($id);
    }

    /**
     * @throws Exception
     */
    public static function getClassByID(int $id): bool|array
    {
        return (new ClassesModel())->get($id);
    }

    /**
     * @throws Exception
     */
    public static function getDivisionByID(int $id): bool|array
    {
        return (new DivisionModel())->get($id);
    }

    /**
     * @throws Exception
     */
    public static function getSubjectByID(int $id): bool|array
    {
        return (new SubjectModel())->get($id);
    }

    /**
     * @throws Exception
     */
    public static function getUnitByID(int $id): bool|array
    {
        return (new UnitModel())->get($id);
    }


    public static function getDivisionSelectList($selected = null):array{
        try {
            $branchModel = new DivisionModel();
            return $branchModel->getSelectList('ID','division_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
    public static function getClassSelectList($selected = null):array{
        try {
            $branchModel = new ClassesModel();
            return $branchModel->getSelectList('ID','class_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
    public static function getBoardSelectList($selected = null):array{
        try {
            $branchModel = new EducationalBoardModel();
            return $branchModel->getSelectList('ID','board_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
    public static function getSubjectSelectList($selected = null):array{
        try {
            $branchModel = new SubjectModel();
            return $branchModel->getSelectList('ID','subject_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
    public static function getUnitSelectList($selected = null):array{
        try {
            $branchModel = new UnitModel();
            return $branchModel->getSelectList('ID','unit_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
    public static function getAcademicYearSelectList($selected = null):array{
        try {
            $branchModel = new AcademicYearModel();
            return $branchModel->getSelectList('ID','year_name',$selected);
        } catch (Exception $e) {
            return [];
        }
    }
}