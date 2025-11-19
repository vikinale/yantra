<?php ?>
<!doctype html>
<html lang="<?php $page->renderLang(); ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php $page->renderTitle(); ?></title>
  <?php do_action('page_meta', $page); ?>
  <link rel="stylesheet" href="<?php echo theme_url('style.css'); ?>"/>
  <?php do_action('head_links', $page); ?>
  <?php do_action('head_scripts', $page); ?>
  <?php do_action('critical_css', $page); ?>
</head>
<body>
  <?php include_once "header.php"; ?>
  <!-- App layout -->
  <div class="app">
    <nav id="sidebar" class="sidebar" aria-label="Main navigation">
      <?php
        // Sidebar content: use page meta 'sidebar' or empty string, apply filters
        $sidebarHtml = apply_filter('page_sidebar', $page->get('sidebar'));
        echo (string)$sidebarHtml;
      ?>
    </nav>
    <!-- Main content -->
    <main class="content" role="main">
      <div class="container">
        <?php
          // Main content: apply filters to the raw content and echo
          $content = apply_filter('page_content', $page->getContent());
          // If the filter returned something non-string, cast to string to avoid warnings
          echo (string)$content;
        ?>
      </div>
    </main>
  </div>
  <?php include_once "footer.php"; ?>
</body>
</html>