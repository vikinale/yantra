<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class AcademicYearModel extends Model
{
    public function __construct()
    {
        parent::__construct('academicyear', 'ID');
    }

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
    public function createAcademicYear(
        string $yearName,
        string $startDate,
        string $endDate,
        ?int $schoolId = null
    ): Model|int {
        if (empty($yearName) || empty($startDate) || empty($endDate)) {
            throw new FormException("Year name, start date, and end date are required.");
        }

        $data = [
            'year_name' => $yearName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'school_id' => $schoolId,
        ];

        if($this->where('year_name','=',$yearName)->count()>0){
            throw new FormException("$yearName already exist.");
        }

        try {
            return $this->insert($data);
        } catch (Exception $e) {
            throw new FormException("Failed to create academic year: ".$e->getMessage() );
        }
    }

    /**
     * Update an academic year by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateAcademicYear(int $id, array $data): int
    {
        if (empty($data)) {
            throw new FormException("No data provided for update.");
        }
        try {
            return $this->where($this->primaryKey,'=',$id)->update($data);
        } catch (Exception $e) {
            throw new FormException("Failed to update school: " );
        }
    }
}