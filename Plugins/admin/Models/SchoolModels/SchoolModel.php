<?php

namespace Plugins\admin\Models;

use Exception;
use System\FormException;
use System\Model;

class SchoolModel extends Model
{
    public function __construct()
    {
        // Call the parent constructor and specify the table and primary key
        parent::__construct('school', 'ID');
    }

    /**
     * Create a new school entry with validation.
     *
     * @param string $schoolName
     * @param string $location
     * @param string $address
     * @param string $city
     * @param string $pincode
     * @param string $contact
     * @param string $email
     * @param string|null $logo (base64 or binary)
     * @return Model|int
     * @throws Exception
     */
    public function createSchool(
        string $schoolName,
        string $location,
        string $address,
        string $city,
        int $pincode,
        string $contact,
        string $email,
        ?string $logo = null
    ): Model|int {
        // Validate required fields
        if (empty($schoolName) || empty($location) || empty($address) || empty($city) || empty($pincode) || empty($contact) || empty($email)) {
            throw new FormException("All required fields must be provided.");
        }

        // Prepare school data
        $schoolData = [
            'SchoolName' => $schoolName,
            'Location' => $location,
            'Address' => $address,
            'City' => $city,
            'Pincode' => $pincode,
            'Contact' => $contact,
            'Email' => $email,
            'logo' => $logo,
        ];

        try {
            return $this->insert($schoolData);
        } catch (Exception $e) {
            throw new FormException("Failed to create school: ");
        }
    }

    /**
     * Update a school entry by ID.
     *
     * @param int $id
     * @param array $data
     * @return int
     * @throws Exception
     */
    public function updateSchool(int $id, array $data): int
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

    /**
     * @throws Exception
     */
    public function updateLogo(int $id, ?string $blob_logo): Model|int
    {
        try {
            return $this->reset('update')
                ->set('logo',$blob_logo)
                ->where($this->primaryKey,'=',$id)
                ->update();

        } catch (Exception $e) {
            throw new FormException("Failed to update school: ");
        }
    }

}
