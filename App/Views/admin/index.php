<?php
// Dashboard summary + widgets
if (!defined('YANTRA')) exit;
?>
<section class="admin-grid">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','Dashboard')); ?></h1>
    <p class="lead">Professional, WP-like admin dashboard — fully responsive and accessible.</p>
  </header>

  <div class="widgets">
    <div class="card widget">
      <h3>Users</h3>
      <p class="muted">Active users: <strong><?php echo number_format($stats['active_users'] ?? 13482); ?></strong></p>
    </div>

    <div class="card widget">
      <h3>Revenue</h3>
      <p class="muted">This month: <strong><?php echo htmlspecialchars($stats['revenue'] ?? '₹254,820'); ?></strong></p>
    </div>

    <div class="card widget">
      <h3>Server</h3>
      <p class="muted">Status: <strong><?php echo htmlspecialchars($stats['server'] ?? 'OK'); ?></strong></p>
    </div>
  </div>

  <div class="card wide">
    <h3>Recent posts</h3>
    <div class="table-row headers">
      <div>Title</div><div class="col-author">Author</div><div class="col-date">Date</div>
    </div>
    <?php foreach ($recent_posts ?? [] as $post): ?>
      <div class="table-row">
        <div><?php echo htmlspecialchars($post['title']); ?></div>
        <div class="col-author"><?php echo htmlspecialchars($post['author']); ?></div>
        <div class="col-date"><?php echo htmlspecialchars($post['date']); ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($recent_posts ?? [])): ?>
      <div class="muted">No recent posts</div>
    <?php endif; ?>
  </div>
</section>
