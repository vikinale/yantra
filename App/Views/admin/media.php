<?php
if (!defined('YANTRA')) exit;
?>
<section class="admin-media">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','Media Library')); ?></h1>
    <div class="actions">
      <a class="btn small" href="<?php echo site_url('admin/media/upload'); ?>">Upload</a>
    </div>
  </header>

  <div class="card media-grid">
    <?php if (!empty($media_items)): foreach ($media_items as $m): ?>
      <div class="media-item">
        <img src="<?php echo htmlspecialchars($m['url']); ?>" alt="<?php echo htmlspecialchars($m['title'] ?? 'media'); ?>">
        <div class="meta">
          <div class="title"><?php echo htmlspecialchars($m['title'] ?? '—'); ?></div>
          <div class="actions">
            <a href="<?php echo site_url('admin/media/edit?id=' . urlencode($m['id'])); ?>">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="muted">No media uploaded yet.</div>
    <?php endif; ?>
  </div>
</section>
