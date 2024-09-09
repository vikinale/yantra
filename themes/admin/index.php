<?php
global $router, $request, $response, $env;
require_once 'functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $title ?? 'Kaveri'; ?></title>

    <link rel="icon" href="<?= theme_url(); ?>img/core-img/favicon.ico" />

    <link rel="stylesheet" href="<?= theme_url(); ?>css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= theme_url(); ?>css/animate.css" />
    <link rel="stylesheet" href="<?= theme_url(); ?>css/introjs.min.css" />

    <link rel="stylesheet" href="<?= theme_url(); ?>style.css" />
    <?php do_action('page-head'); ?>
</head>
<body class="<?= $body_class??''; ?>">
<?= $pagetop??''; ?>

<!-- ======================================
******* Page Wrapper Area Start **********
======================================= -->
 <div class="flapt-page-wrapper">
     <!-- Sidemenu Area -->

     <?= $sidebar??''; ?>

     <!-- Page Content -->
     <div class="flapt-page-content">
         <!-- Top Header Area -->
         <?= $topnav??''; ?>

         <!-- Main Content Area -->
         <div class="main-content <?= $main_div_class??''; ?>">
             <?= $content??''; ?>

             <!-- Footer Area -->
             <?= $footer??''; ?>
         </div>
     </div>
 </div>
<!-- ======================================
********* Page Wrapper Area End ***********
======================================= -->

<!-- Must needed plugins to the run this Template -->

<script src="<?= site_url('js/yantra.js'); ?>"></script>
<script src="<?= theme_url(); ?>js/jquery.min.js"></script>
<script src="<?= theme_url(); ?>js/bootstrap.bundle.min.js"></script>
<script src="<?= theme_url(); ?>js/default-assets/setting.js"></script>
<script src="<?= theme_url(); ?>js/default-assets/scrool-bar.js"></script>
<script src="<?= theme_url(); ?>js/todo-list.js"></script>

<!-- Active JS -->
<script src="<?= theme_url(); ?>js/default-assets/active.js"></script>

 <?php do_action('page-bottom'); ?>
</body>
</html>