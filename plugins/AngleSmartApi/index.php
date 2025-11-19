<?php
namespace Plugins\AngleSmartApi;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/AngleAPIManager.php';
use Plugins\AngleSmartApi\AngleAPIManager;

add_filter('angle_smart_api',function($api){
    if($api){
        if(get_class($api) === AngleAPIManager::class)
            return $api;
    }
    return AngleSmartApi::api();
});