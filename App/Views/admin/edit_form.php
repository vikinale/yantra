<?php
if (!defined('YANTRA')) exit;
$entity = $entity ?? []; // associative - prefilled values
?>
<section class="admin-form">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','Edit')); ?></h1>
  </header>

  <?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <form method="post" action="<?php echo site_url($form_action ?? ''); ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="">

    <div class="card">
      <div class="form-row">
        <label>Title</label>
        <input name="title" type="text" value="<?php echo htmlspecialchars($entity['title'] ?? ''); ?>" required>
      </div>

      <div class="form-row">
        <label>Content</label>
        <textarea name="content" rows="8"><?php echo htmlspecialchars($entity['content'] ?? ''); ?></textarea>
      </div>

      <div class="form-row split">
        <div>
          <label>Status</label>
          <select name="status">
            <option value="draft" <?php if (($entity['status'] ?? '') === 'draft') echo 'selected'; ?>>Draft</option>
            <option value="published" <?php if (($entity['status'] ?? '') === 'published') echo 'selected'; ?>>Published</option>
          </select>
        </div>
        <div>
          <label>Author</label>
          <input name="author" type="text" value="<?php echo htmlspecialchars($entity['author'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-row actions">
        <button class="btn" type="submit">Save</button>
        <a class="btn ghost" href="<?php echo site_url($cancel_url ?? 'admin'); ?>">Cancel</a>
      </div>
    </div>
  </form>
</section>
