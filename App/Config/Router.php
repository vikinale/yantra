<?php
global  $router;
$router->addRoute('GET', '/blogs', 'Controllers\Blogs', 'index');
$router->addRoute('GET', '/blogs/{slug}', 'Controllers\Blogs', 'single');
$router->get('/admin/yantra-ai', [\Controllers\admin\YantraAI::class, 'index']);
$router->post('/admin/yantra-ai/chat', [\Controllers\admin\YantraAI::class, 'chat']);
