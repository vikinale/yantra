<?php
if (!defined('YANTRA')) exit;
?>
<section class="admin-form">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','Settings')); ?></h1>
  </header>

  <?php if (!empty($success_message)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
  <?php if (!empty($error_message)): ?><div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

  <form method="post" action="<?php echo site_url('admin/settings'); ?>">
    <input type="hidden" name="csrf_token" value="">
    <div class="card">
      <div class="form-row">
        <label>Site title</label>
        <input name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>">
      </div>

      <div class="form-row">
        <label>Admin email</label>
        <input name="admin_email" type="email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
      </div>

      <div class="form-row actions">
        <button class="btn" type="submit">Save changes</button>
      </div>
    </div>
  </form>
</section>
