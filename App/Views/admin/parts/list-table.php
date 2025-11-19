<?php
// Generic list table
if (!defined('YANTRA')) exit;
$colLabels = $columns ?? ['Title','Author','Date'];
?>
<section class="admin-list">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','List')); ?></h1>
    <div class="actions">
      <a href="<?php echo site_url($add_url ?? 'admin/add'); ?>" class="btn small">Add New</a>
    </div>
  </header>

  <div class="card">
    <div class="table-row headers">
      <?php foreach ($colLabels as $label): ?><div><?php echo htmlspecialchars($label); ?></div><?php endforeach; ?>
      <div class="col-actions">Actions</div>
    </div>

    <?php if (!empty($items)): foreach ($items as $item): ?>
      <div class="table-row">
        <?php foreach ($item['cols'] as $c): ?><div><?php echo htmlspecialchars($c); ?></div><?php endforeach; ?>
        <div class="col-actions">
          <a href="<?php echo site_url($item['edit_url']); ?>">Edit</a>
          <a href="<?php echo site_url($item['delete_url']); ?>" class="danger" onclick="return confirm('Delete?')">Delete</a>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="muted">No items found.</div>
    <?php endif; ?>

    <div class="card-footer">
      <?php echo $pagination_html ?? ''; ?>
    </div>
  </div>
</section>
