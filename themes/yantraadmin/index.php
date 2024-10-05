<?php
global $router, $request, $response, $env;
require_once 'functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title ?? 'Kaveri'; ?></title>
    <link rel="stylesheet" href="<?= theme_url(); ?>style.css">
    <link rel="stylesheet" href="<?= theme_url(); ?>layout.css">
    <link rel="stylesheet" href="<?= theme_url(); ?>elements.css">
    <link rel="stylesheet" href="<?= theme_url(); ?>utility.css">
    <link rel="stylesheet" href="<?= theme_url(); ?>sidebar.css">
    <?php do_action('page-head'); ?>
</head>
<body class="<?= $body_class??''; ?>">
<?php do_action('page-top'); ?>
 <?= $topbar??''; ?>
 <main>
     <?= $sidebar??''; ?>
     <div class="content" id="content">
         <?= $content??''; ?>
         <?= $footer??''; ?>
     </div>
 </main>
 <?php  the_section('','scripts' ); ?>
 <?php do_action('page-bottom'); ?>
</body>
</html>