<?php
global  $router;
$router->addRoute('GET', '/', 'Controllers\\HomeController', 'index');
$router->addRoute('GET', '/about', 'Controllers\\AboutController', 'index');

$router->addRoute('GET', '/blogs', 'Controllers\Blogs', 'index');
$router->addRoute('GET', '/blogs/{slug}', 'Controllers\Blogs', 'single');
