<?php ?>
<!doctype html>
<html lang="<?php $page->renderLang(); ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php $page->renderTitle(); ?></title>
  <?php do_action('page_meta', $page); ?>

  <link rel="stylesheet" href="<?php echo theme_url('style.css'); ?>">
  <style>
    /* local vars (no global root changes) */
    .container .auth-page { --login-accent: #0073aa; --card-bg:#fff; --muted:#6b6f74; }

    /* Center the login card vertically and horizontally within container area */
    .container .auth-page {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 0;               /* space from top like WP login */
      box-sizing: border-box;
    }

    /* card wrapper */
    .container .auth-page .auth-card,
    .auth-card {
      width: 100%;
      max-width: 420px;              /* WP-login-ish width */
      background: var(--card-bg);
      padding: 28px 32px;
      border-radius: 6px;
      box-shadow: 0 6px 24px rgba(11,18,25,0.08);
      border: 1px solid rgba(0,0,0,0.04);
    }

    /* small site logo area above card content (optional) */
    .auth-logo {
      display: block;
      margin: 0 auto 18px;
      width: 84px;
      height: 84px;
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
    }

    /* Title — small and centered like WP */
    .auth-header {
      text-align: center;
      margin-bottom: 10px;
    }
    .auth-header h1 {
      font-size: 20px;
      margin: 0;
      font-weight: 600;
      color: #111;
    }

    /* Helper text under title */
    .auth-subtitle {
      font-size: 13px;
      color: var(--muted);
      text-align: center;
      margin-bottom: 16px;
    }

    /* Form: stacked, full width inputs */
    .auth-form {
      display: block;
    }

    /* Label slightly above input (compact) */
    .auth-form .form-row {
      margin-bottom: 12px;
    }
    .auth-form label {
      display: block;
      font-size: 13px;
      color: #333;
      margin-bottom: 6px;
    }

    /* Inputs */
    .auth-form input[type="text"],
    .auth-form input[type="password"],
    .auth-form input[type="email"] {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: #fafafa;
      box-sizing: border-box;
      font-size: 14px;
    }

    /* Focus */
    .auth-form input:focus {
      outline: none;
      border-color: rgba(0,115,170,0.95);
      box-shadow: 0 0 0 3px rgba(0,115,170,0.08);
      background: #fff;
    }

    /* Full width primary button styled like WP */
    .auth-form .btn {
      display: block;
      width: 100%;
      padding: 11px 14px;
      margin-top: 6px;
      background: var(--login-accent);
      color: #fff;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      text-align: center;
      box-shadow: inset 0 -2px rgba(0,0,0,0.06);
    }

    /* hover/focus */
    .auth-form .btn:hover,
    .auth-form .btn:focus {
      filter: brightness(0.96);
      transform: translateY(-1px);
    }

    /* small footer text (copyright) */
    .auth-footer {
      margin-top: 14px;
      text-align: center;
      color: var(--muted);
      font-size: 13px;
    }

    /* compact error / notice (same visual weight as WP) */
    .alert.alert-error {
      background: #fff5f5;
      border: 1px solid #f1c0c0;
      color: #8a1f1f;
      padding: 10px 12px;
      border-radius: 4px;
      margin-bottom: 12px;
      font-size: 14px;
    }

    /* Responsive: small screens keep card full width */
    @media (max-width: 520px) {
      .container .auth-page { padding: 28px 0; }
      .auth-card { padding: 20px; margin: 0 12px; max-width: calc(100% - 24px); }
    }
  </style>
  <?php do_action('head_links', $page); ?>
  <?php do_action('head_scripts', $page); ?>
</head>
<body>
  <!-- App layout -->
  <div class="app">
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