<?php
global  $router;

/* Admin Pages */
$router->addRoute('GET', '/admin', 'Plugins\\admin\\Controllers\\HomeController', 'index');
$router->addRoute('GET', '/admin/login', 'Plugins\\admin\\Controllers\\HomeController', 'login');
$router->addRoute('GET', '/admin/signup', 'Plugins\\admin\\Controllers\\HomeController', 'signup');
$router->addRoute('GET', '/admin/profile', 'Plugins\\admin\\Controllers\\HomeController', 'profile');
$router->addRoute('POST', '/admin/login', 'Plugins\\admin\\Controllers\\HomeController', 'login');
$router->addRoute('POST', '/admin/signup', 'Plugins\\admin\\Controllers\\HomeController', 'signup');

$router->addRoute('GET', '/admin/school', 'Plugins\admin\Controllers\SchoolController', 'index');

$router->addRoute('GET', '/admin/school/academic-years', 'Plugins\admin\Controllers\SchoolController', 'academic_years');
$router->addRoute('POST', '/admin/school/academic-years/table', 'Plugins\admin\Controllers\SchoolController', 'academic_year_table');
$router->addRoute('POST', '/admin/school/academic-years/create', 'Plugins\admin\Controllers\SchoolController', 'academic_year_create');
$router->addRoute('GET', '/admin/school/academic-years/{id}', 'Plugins\admin\Controllers\SchoolController', 'academic_year_update');

$router->addRoute('GET', '/admin/school/educational-boards', 'Plugins\admin\Controllers\SchoolController', 'educational_boards');
$router->addRoute('POST', '/admin/school/educational-boards/table', 'Plugins\admin\Controllers\SchoolController', 'educational_boards_table');
$router->addRoute('POST', '/admin/school/educational-boards/create', 'Plugins\admin\Controllers\SchoolController', 'educational_boards_create');
$router->addRoute('GET', '/admin/school/educational-boards/{id}', 'Plugins\admin\Controllers\SchoolController', 'educational_boards_update');

$router->addRoute('GET', '/admin/school/classes', 'Plugins\admin\Controllers\SchoolController', 'classes');
$router->addRoute('POST', '/admin/school/classes/table', 'Plugins\admin\Controllers\SchoolController', 'classes_table');
$router->addRoute('POST', '/admin/school/classes/create', 'Plugins\admin\Controllers\SchoolController', 'classes_create');
$router->addRoute('GET', '/admin/school/classes/{id}', 'Plugins\admin\Controllers\SchoolController', 'classes_update');

$router->addRoute('GET', '/admin/school/divisions', 'Plugins\admin\Controllers\SchoolController', 'divisions');
$router->addRoute('POST', '/admin/school/divisions/table', 'Plugins\admin\Controllers\SchoolController', 'divisions_table');
$router->addRoute('POST', '/admin/school/divisions/create', 'Plugins\admin\Controllers\SchoolController', 'divisions_create');
$router->addRoute('GET', '/admin/school/divisions/{id}', 'Plugins\admin\Controllers\SchoolController', 'divisions_update');


$router->addRoute('GET', '/admin/school/subjects', 'Plugins\admin\Controllers\SchoolController', 'subjects');
$router->addRoute('POST', '/admin/school/subjects/table', 'Plugins\admin\Controllers\SchoolController', 'subjects_table');
$router->addRoute('POST', '/admin/school/subjects/create', 'Plugins\admin\Controllers\SchoolController', 'subjects_create');
$router->addRoute('GET', '/admin/school/subjects/{id}', 'Plugins\admin\Controllers\SchoolController', 'divisions_update');


$router->addRoute('GET', '/admin/school/units', 'Plugins\admin\Controllers\SchoolController', 'units');
$router->addRoute('POST', '/admin/school/units/table', 'Plugins\admin\Controllers\SchoolController', 'units_table');
$router->addRoute('POST', '/admin/school/units/create', 'Plugins\admin\Controllers\SchoolController', 'units_create');
$router->addRoute('GET', '/admin/school/units/{id}', 'Plugins\admin\Controllers\SchoolController', 'units_update');


$router->addRoute('GET', '/admin/branches', 'Plugins\admin\Controllers\BranchController', 'index');
$router->addRoute('GET', '/admin/branches/create', 'Plugins\admin\Controllers\BranchController', 'create');
$router->addRoute('GET', '/admin/branches/{id}', 'Plugins\admin\Controllers\BranchController', 'branch');
$router->addRoute('POST', '/admin/branches/branch-create', 'Plugins\admin\Controllers\BranchController', 'branch_create');
$router->addRoute('POST', '/admin/branches/branch-table', 'Plugins\admin\Controllers\BranchController', 'branch_table');

$router->addRoute('GET', '/admin/classrooms', 'Plugins\admin\Controllers\ClassroomController', 'index');
$router->addRoute('GET', '/admin/classrooms/{id}', 'Plugins\admin\Controllers\ClassroomController', 'index');
$router->addRoute('POST', '/admin/classrooms/table', 'Plugins\admin\Controllers\ClassroomController', 'table');
$router->addRoute('POST', '/admin/classrooms/create', 'Plugins\admin\Controllers\ClassroomController', 'create');


$router->addRoute('GET', '/admin/student-group', 'Plugins\admin\Controllers\StudentGroupController', 'index');
$router->addRoute('GET', '/admin/student-group/{id}', 'Plugins\admin\Controllers\StudentGroupController', 'index');
$router->addRoute('POST', '/admin/student-group/table', 'Plugins\admin\Controllers\StudentGroupController', 'table');
$router->addRoute('POST', '/admin/student-group/create', 'Plugins\admin\Controllers\StudentGroupController', 'create');

$router->addRoute('GET', '/admin/group', 'Plugins\admin\Controllers\BranchController', 'group');
$router->addRoute('GET', '/admin/group/content', 'Plugins\admin\Controllers\BranchController', 'group_content');
$router->addRoute('GET', '/admin/group/files', 'Plugins\admin\Controllers\BranchController', 'study_files');

$router->addRoute('GET', '/admin/users', 'Plugins\admin\Controllers\UserController', 'index');
$router->addRoute('GET', '/admin/users/create', 'Plugins\\admin\\Controllers\\UserController', 'create');
$router->addRoute('POST', '/admin/users/table', 'Plugins\admin\Controllers\UserController', 'table');
$router->addRoute('POST', '/admin/users/create', 'Plugins\\admin\\Controllers\\UserController', 'create');

$router->addRoute('GET', '/admin/users/roles', 'Plugins\\admin\\Controllers\\UserController', 'roles');
$router->addRoute('GET', '/admin/users/roles/{id}', 'Plugins\\admin\\Controllers\\UserController', 'roles');
$router->addRoute('POST', '/admin/users/roles/create', 'Plugins\\admin\\Controllers\\UserController', 'create_role');

$router->addRoute('GET', '/admin/users/permissions', 'Plugins\\admin\\Controllers\\UserController', 'permissions');
$router->addRoute('GET', '/admin/users/permissions/{id}', 'Plugins\\admin\\Controllers\\UserController', 'permissions');
$router->addRoute('POST', '/admin/users/permissions/create', 'Plugins\\admin\\Controllers\\UserController', 'permission_create');
$router->addRoute('POST', '/admin/users/permissions-table', 'Plugins\\admin\\Controllers\\UserController', 'permissions_list');



$router->addRoute('GET', '/admin/students', 'Plugins\admin\Controllers\StudentController', 'index');
$router->addRoute('GET', '/admin/students/create', 'Plugins\admin\Controllers\StudentController', 'create');
$router->addRoute('GET', '/admin/students/{id}', 'Plugins\admin\Controllers\StudentController', 'single');
$router->addRoute('POST', '/admin/students/save', 'Plugins\admin\Controllers\StudentController', 'save');
$router->addRoute('POST', '/admin/students/table', 'Plugins\admin\Controllers\StudentController', 'table');

/*
$router->addRoute('GET', '/admin/posts', 'Plugins\\admin\\Controllers\\Posts', 'index');
$router->addRoute('GET', '/admin/posts/add', 'Plugins\\admin\\Controllers\\Posts', 'add');
$router->addRoute('GET', '/admin/posts/tags', 'Plugins\\admin\\Controllers\\Posts', 'tags');
$router->addRoute('GET', '/admin/posts/categories', 'Plugins\\admin\\Controllers\\Posts', 'categories');

$router->addRoute('GET', '/admin/media', 'Plugins\\admin\\Controllers\\Media', 'index');
$router->addRoute('GET', '/admin/media/add', 'Plugins\\admin\\Controllers\\Media', 'add');

$router->addRoute('GET', '/admin/pages', 'Plugins\\admin\\Controllers\\Pages', 'index');
$router->addRoute('GET', '/admin/pages/add', 'Plugins\\admin\\Controllers\\Pages', 'add');*/

/*
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
$router->addRoute('GET', '/admin/settings/permalinks', 'Plugins\\admin\\Controllers\\Settings', 'permalinks');*/
