<?php
namespace Plugins\AngleSmartApi;

use Plugins\AngleSmartApi\AngleAPIManager;

class AngleSmartApi
{
    private string $basePath;
    private string $settingsFile;
    private static ?AngleAPIManager $apiInstance = null;

    public function __construct()
    {
        $this->basePath = __DIR__ . DIRECTORY_SEPARATOR;
        $this->settingsFile = $this->basePath . 'settings.json';
    }

    /**
     * ✅ Static method to get or create the API instance.
     *    Usage: AngleSmartApi::api();
     */
    public static function api(): AngleAPIManager
    {
        if (self::$apiInstance instanceof AngleAPIManager) {
            return self::$apiInstance;
        }

        // Compute paths dynamically (we cannot use $this inside static)
        $basePath = __DIR__ . DIRECTORY_SEPARATOR;
        $settingsFile = $basePath . 'settings.json';

        // Load configuration
        $cfg = [];
        if (file_exists($settingsFile)) {
            $cfg = json_decode(file_get_contents($settingsFile), true) ?: [];
        }

        // Add token cache path inside plugin directory
        $cfg['token_cache_file'] = $basePath . '.angle_token.json';

        self::$apiInstance = new AngleAPIManager($cfg);
        return self::$apiInstance;
    }

    // Optionally non-static variant if you prefer $this->api()
    public function getApi(): AngleAPIManager
    {
        return self::api();
    }

    // Called by PluginManager->activatePlugins()
    public function activate(): void
    {
        // Create default settings.json if not exists
        if (!file_exists($this->settingsFile)) {
            file_put_contents($this->settingsFile, json_encode([
                'client_id'     => 'RkCWn9nI',
                'client_secret' => '7c9b4678-ffd5-4390-b2c7-0b2e22b3ec36',
                'username'      => '',
                'password'      => '',
                'base_url'      => 'https://smartapi.angelbroking.com'
            ], JSON_PRETTY_PRINT));
        }
    }

    // Called by PluginManager->deactivatePlugins()
    public function deactivate(): void
    {
        // Keep settings, but remove cached tokens
        $tokenFile = $this->basePath . '.angle_token.json';
        if (file_exists($tokenFile)) {
            @unlink($tokenFile);
        }
    }

}