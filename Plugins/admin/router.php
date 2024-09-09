<?php
global  $router;

/* Admin Pages */
$router->addRoute('GET', '/admin', 'Plugins\\admin\\Controllers\\HomeController', 'index');
$router->addRoute('GET', '/admin/profile', 'Plugins\\admin\\Controllers\\HomeController', 'profile');
$router->addRoute('GET', '/admin/login', 'Plugins\\admin\\Controllers\\HomeController', 'login');
$router->addRoute('GET', '/admin/signup', 'Plugins\\admin\\Controllers\\HomeController', 'signup');
$router->addRoute('POST', '/admin/login', 'Plugins\\admin\\Controllers\\HomeController', 'login');
$router->addRoute('POST', '/admin/signup', 'Plugins\\admin\\Controllers\\HomeController', 'signup');

$router->addRoute('GET', '/admin/school', 'Plugins\admin\Controllers\SchoolController', 'index');
$router->addRoute('GET', '/admin/school/academic-years', 'Plugins\admin\Controllers\SchoolController', 'academic_years');


$router->addRoute('GET', '/admin/posts', 'Plugins\\admin\\Controllers\\Posts', 'index');
$router->addRoute('GET', '/admin/posts/add', 'Plugins\\admin\\Controllers\\Posts', 'add');
$router->addRoute('GET', '/admin/posts/tags', 'Plugins\\admin\\Controllers\\Posts', 'tags');
$router->addRoute('GET', '/admin/posts/categories', 'Plugins\\admin\\Controllers\\Posts', 'categories');

$router->addRoute('GET', '/admin/media', 'Plugins\\admin\\Controllers\\Media', 'index');
$router->addRoute('GET', '/admin/media/add', 'Plugins\\admin\\Controllers\\Media', 'add');

$router->addRoute('GET', '/admin/pages', 'Plugins\\admin\\Controllers\\Pages', 'index');
$router->addRoute('GET', '/admin/pages/add', 'Plugins\\admin\\Controllers\\Pages', 'add');

$router->addRoute('GET', '/admin/users', 'Plugins\admin\Controllers\UserController', 'index');
$router->addRoute('GET', '/admin/users/create', 'Plugins\\admin\\Controllers\\UserController', 'create');
$router->addRoute('POST', '/admin/users/create', 'Plugins\\admin\\Controllers\\UserController', 'create');

$router->addRoute('GET', '/admin/users/roles', 'Plugins\\admin\\Controllers\\UserController', 'roles');
$router->addRoute('GET', '/admin/users/roles/{id}', 'Plugins\\admin\\Controllers\\UserController', 'roles');
$router->addRoute('POST', '/admin/users/roles/create', 'Plugins\\admin\\Controllers\\UserController', 'create_role');

$router->addRoute('GET', '/admin/users/permissions', 'Plugins\\admin\\Controllers\\UserController', 'permissions');
$router->addRoute('GET', '/admin/users/permissions/{id}', 'Plugins\\admin\\Controllers\\UserController', 'permissions');
$router->addRoute('POST', '/admin/users/permissions-table', 'Plugins\\admin\\Controllers\\UserController', 'permissions_list');


$router->addRoute('GET', '/admin/tools', 'Plugins\\admin\\Controllers\\Tools', 'index');
$router->addRoute('GET', '/admin/tools/import', 'Plugins\\admin\\Controllers\\Tools', 'import');
$router->addRoute('GET', '/admin/tools/export', 'Plugins\\admin\\Controllers\\Tools', 'export');
$router->addRoute('GET', '/admin/tools/site-health', 'Plugins\\admin\\Controllers\\Tools', 'site_health');

$router->addRoute('GET', '/admin/settings', 'Plugins\\admin\\Controllers\\Settings', 'index');
$router->addRoute('GET', '/admin/settings/media', 'Plugins\\admin\\Controllers\\Settings', 'media');
$router->addRoute('GET', '/admin/settings/privacy', 'Plugins\\admin\\Controllers\\Settings', 'privacy');
$router->addRoute('GET', '/admin/settings/writing', 'Plugins\\admin\\Controllers\\Settings', 'writing');
$router->addRoute('GET', '/admin/settings/reading', 'Plugins\\admin\\Controllers\\Settings', 'reading');
$router->addRoute('GET', '/admin/settings/discussion', 'Plugins\\admin\\Controllers\\Settings', 'discussion');
$router->addRoute('GET', '/admin/settings/permalinks', 'Plugins\\admin\\Controllers\\Settings', 'permalinks');

/*$router->addRoute('GET', '/admin/test', 'Plugins\\admin\\Controllers\\HomeController', 'test');
$router->addRoute('GET', '/admin/dashboard', 'Plugins\\admin\\Controllers\\HomeController', 'dashboard');
$router->addRoute('GET', '/admin/logout', 'Plugins\\admin\\Controllers\\HomeController', 'logout');
$router->addRoute('GET', '/admin/update', 'Plugins\\admin\\Controllers\\HomeController', 'update');
$router->addRoute('GET', '/admin/comments', 'Plugins\\admin\\Controllers\\HomeController', 'comments');
$router->addRoute('GET', '/admin/about', 'Plugins\\admin\\Controllers\\HomeController', 'about');

$router->addRoute('GET', '/admin/appearance', 'Plugins\\admin\\Controllers\\AppearanceController', 'index');

$router->addRoute('GET', '/admin/plugins', 'Plugins\\admin\\Controllers\\PluginsController', 'index');

$router->addRoute('GET', '/admin/tools', 'Plugins\\admin\\Controllers\\ToolsController', 'index');

$router->addRoute('GET', '/admin/settings', 'Plugins\\admin\\Controllers\\SettingsController', 'index');
$router->addRoute('GET', '/admin/profile', 'Plugins\\admin\\Controllers\\UsersController', 'profile');

$router->addRoute('GET', '/admin/users', 'Plugins\\admin\\Controllers\\UsersController', 'index');
$router->addRoute('POST', '/admin/user-list', 'Plugins\\admin\\Controllers\\UsersController', 'user_list');
$router->addRoute('GET', '/admin/users/user', 'Plugins\\admin\\Controllers\\UsersController', 'user');
$router->addRoute('POST', '/admin/users/user', 'Plugins\\admin\\Controllers\\UsersController', 'user');

$router->addRoute('GET', '/admin/users/roles', 'Plugins\\admin\\Controllers\\UsersController', 'roles');
$router->addRoute('GET', '/admin/users/role', 'Plugins\\admin\\Controllers\\UsersController', 'role');
$router->addRoute('POST', '/admin/users/role', 'Plugins\\admin\\Controllers\\UsersController', 'role');

$router->addRoute('GET', '/admin/users/permissions', 'Plugins\\admin\\Controllers\\UsersController', 'permissions');
$router->addRoute('GET', '/admin/users/permission', 'Plugins\\admin\\Controllers\\UsersController', 'permission');
$router->addRoute('POST', '/admin/users/permission', 'Plugins\\admin\\Controllers\\UsersController', 'permission');


$router->addRoute('GET', '/admin/media', 'Plugins\\admin\\Controllers\\MediaController', 'index');
$router->addRoute('GET', '/admin/media/create', 'Plugins\\admin\\Controllers\\MediaController', 'create');

$router->addRoute('GET', '/admin/posts', 'Plugins\\admin\\Controllers\\PostsController', 'index');
$router->addRoute('GET', '/admin/posts/create', 'Plugins\\admin\\Controllers\\PostsController', 'create');
$router->addRoute('GET', '/admin/posts/categories', 'Plugins\\admin\\Controllers\\PostsController', 'categories');
$router->addRoute('GET', '/admin/posts/tags', 'Plugins\\admin\\Controllers\\PostsController', 'tags');

$router->addRoute('GET', '/admin/pages', 'Plugins\\admin\\Controllers\\PagesController', 'index');
$router->addRoute('GET', '/admin/pages/create', 'Plugins\\admin\\Controllers\\PagesController', 'create');


$router->addRoute('POST', '/admin/api/check-session', 'Plugins\\admin\\Controllers\\SessionController', 'index');*/
