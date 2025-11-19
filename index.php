<?php
const BASEPATH = __DIR__;
const YANTRA = true;
ini_set('display_errors',1);
ini_set('error_log', BASEPATH.'/storage/logs/error.log');
error_reporting(E_ALL & ~E_NOTICE);

use System\{Config,PluginManager, Request, Response, Router};
require __DIR__ . '/vendor/autoload.php';
global $router, $request, $response, $env;

$router = new Router();
$request = new Request();
$response = new Response();
$env = new stdClass();
$env->theme = null;
$env->errors = array();

require_once 'System/functions.php'; 
require_once 'App/Config/Router.php';
autoload_controllers();

Config::read('App');
PluginManager::loadPlugins(Config::get('app.plugins'));
PluginManager::activatePlugins();
$router->dispatch($request, $response);
exit();