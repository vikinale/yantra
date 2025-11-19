<?php if (!defined('YANTRA')) exit; ?>
<section class="admin-list">
  <header class="page-head"><h1><?php echo htmlspecialchars($page->getMeta('title','Comments')); ?></h1></header>

  <div class="card">
    <?php if (!empty($comments)): foreach ($comments as $c): ?>
      <div class="comment-row">
        <div class="comment-meta">
          <strong><?php echo htmlspecialchars($c['author']); ?></strong> on <em><?php echo htmlspecialchars($c['post_title']); ?></em>
          <div class="muted small"><?php echo htmlspecialchars($c['date']); ?></div>
        </div>
        <div class="comment-body"><?php echo nl2br(htmlspecialchars($c['content'])); ?></div>
        <div class="comment-actions">
          <a href="<?php echo site_url('admin/comments/approve?id='.$c['id']); ?>">Approve</a>
          <a href="<?php echo site_url('admin/comments/delete?id='.$c['id']); ?>" class="danger">Delete</a>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="muted">No comments.</div>
    <?php endif; ?>
  </div>
</section>
