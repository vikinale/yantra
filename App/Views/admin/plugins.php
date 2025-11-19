<?php if (!defined('YANTRA')) exit; ?>
<section class="admin-list">
  <header class="page-head"><h1><?php echo htmlspecialchars($page->getMeta('title','Plugins')); ?></h1></header>
  <div class="card">
    <?php foreach ($plugins ?? [] as $p): ?>
      <div class="table-row">
        <div>
          <strong><?php echo htmlspecialchars($p['name']); ?></strong>
          <div class="muted small"><?php echo htmlspecialchars($p['desc'] ?? ''); ?></div>
        </div>
        <div class="col-actions">
          <?php if ($p['active']): ?>
            <a href="<?php echo site_url('admin/plugins/deactivate?slug='.$p['slug']); ?>">Deactivate</a>
          <?php else: ?>
            <a href="<?php echo site_url('admin/plugins/activate?slug='.$p['slug']); ?>">Activate</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
