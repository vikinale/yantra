<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use System\Model;

class BranchModel extends Model
{
    public function __construct()
    {
        parent::__construct('branch', 'ID');
    }

    /**
     * Create a new branch.
     *
     * @param string $branchName
     * @param string $status
     * @param string $location
     * @param string $address
     * @param string $city
     * @param string $pincode
     * @param string $contact
     * @param string $email
     * @param int|null $schoolId
     * @param string|null $logo
     * @return int|Model
     * @throws Exception
     */
    public function createBranch(string $branchName, string $status, string $location, string $address, string $city, string $pincode, string $contact, string $email, ?int $schoolId = null, ?string $logo = null): int|Model
    {
        // Prepare branch data
        $branchData = [
            'branch_name' => $branchName,
            'status' => $status,
            'location' => $location,
            'address' => $address,
            'city' => $city,
            'pincode' => $pincode,
            'contact' => $contact,
            'email' => $email,
            'logo' => $logo,
            'school_id' => $schoolId
        ];

        try {
            // Insert the branch record
            return $this->insert($branchData);
        } catch (Exception $e) {
            throw new Exception("Failed to create branch: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updateBranch(int $branchId, ?string $branchName, ?string $status, ?string $location, ?string $address, ?string $city, ?string $pincode, ?string $contact, ?string $email, ?string $logo): Model|int
    {
        try {
            // Insert the branch record
            $m= $this->reset('update');
            if($branchName != null)
                $m=$m->set('branch_name',$branchName);
            if($status != null)
                $m=$m->set('status',$status);
            if($location != null)
                $m=$m->set('status',$location);
            if($address != null)
                $m=$m->set('status',$address);
            if($city != null)
                $m=$m->set('status',$city);
            if($pincode != null)
                $m=$m->set('status',$pincode);
            if($contact != null)
                $m=$m->set('status',$contact);
            if($email != null)
                $m=$m->set('status',$email);
            if($logo != null)
                $m=$m->set('status',$logo);

            $m=$m->where($this->primaryKey,'=',$branchId);
            return $m->update();
        } catch (Exception $e) {
            throw new Exception("Failed to create branch: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function updateBranchLogo(int $branchId, ?string $logo): Model|int
    {
        try {
            // Insert the branch record
            $m= $this->reset('update');
            $m=$m->set('status',$logo);
            $m=$m->where($this->primaryKey,'=',$branchId);
            return $m->update();
        } catch (Exception $e) {
            throw new Exception("Failed to create branch: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getBranchesBySchool(int $schoolId): bool|array
    {

        try {
            return $this->select('*')
                ->where('school_id ', '=', $schoolId)
                ->getResults();
        } catch (Exception $e) {
            throw new Exception("Failed to get Branch list: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getAllBranches(): bool|array
    {
        try {
            return $this->select('*')
                ->getResults();
        } catch (Exception $e) {
            throw new Exception("Failed to get Branch list: " . $e->getMessage());
        }
    }
}