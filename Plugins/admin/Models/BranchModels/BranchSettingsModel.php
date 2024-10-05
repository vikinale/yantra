<?php

namespace Plugins\admin\Models\BranchModels;

use Exception;
use System\Model;

class BranchSettingsModel extends Model
{
    public function __construct()
    {
        parent::__construct('branch_settings', 'ID');
    }

    /**
     * Create a new branch setting.
     *
     * @param string $settingKey
     * @param string $settingValue
     * @param int $branchId
     * @return int|Model
     * @throws Exception
     */
    public function createBranchSetting(string $settingKey, string $settingValue, int $branchId): int|Model
    {
        // Prepare setting data
        $settingData = [
            'setting_key' => $settingKey,
            'setting_value' => $settingValue,
            'branch_id' => $branchId
        ];

        try {
            // Insert the setting
            return $this->insert($settingData);
        } catch (Exception $e) {
            throw new Exception("Failed to create branch setting: " . $e->getMessage());
        }
    }

    /**
     * Update an existing branch setting.
     *
     * @param int $settingId
     * @param string $settingValue
     * @return int
     * @throws Exception
     */
    public function updateBranchSetting(int $settingId, string $settingValue): int
    {
        try {
            return $this->reset('update')
                ->set('setting_value', $settingValue)
                ->where($this->primaryKey, '=', $settingId)
                ->update();
        } catch (Exception $e) {
            throw new Exception("Failed to update branch setting: " . $e->getMessage());
        }
    }

    /**
     * Get all settings for a specific branch.
     *
     * @param int $branchId
     * @return array
     * @throws Exception
     */
    public function getSettingsByBranch(int $branchId): array
    {
        try {
            return $this->reset()
                ->where('branch_id', '=', $branchId)
                ->getResults();
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve settings: " . $e->getMessage());
        }
    }
}
