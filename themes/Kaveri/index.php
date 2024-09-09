<?php
use System\Theme;
global $router, $request, $response, $env;
require_once 'functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title><?php echo $title ?? 'Kaveri'; ?></title>
    <link href="<?= theme_url(); ?>assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="https://fonts.googleapis.com/css?family=Playfair&#43;Display:700,900&amp;display=swap" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="<?= theme_url(); ?>style.css" rel="stylesheet">
    <?php do_action('site-header'); ?>
</head>
<body class="<?= $body_class??''; ?>">
<?php do_action('page-top'); ?>
    <main class="container">
        <?php the_section('sections','nav'); ?>
        <div>
            <?= $content??''; ?>
        </div>
    </main>
<script src="<?= theme_url(); ?>assets/dist/js/bootstrap.bundle.min.js"></script>
<?php do_action('page-bottom'); ?>
</body>
</html>