<?php if (!defined('YANTRA')) exit; ?>
<section class="admin-list">
  <header class="page-head">
    <h1><?php echo htmlspecialchars($page->getMeta('title','Users')); ?></h1>
    <div class="actions"><a class="btn small" href="<?php echo site_url('admin/users/add'); ?>">Add New</a></div>
  </header>

  <div class="card">
    <div class="table-row headers"><div>User</div><div class="col-role">Role</div><div class="col-actions">Actions</div></div>
    <?php foreach ($users ?? [] as $u): ?>
      <div class="table-row">
        <div><?php echo htmlspecialchars($u['name']); ?><div class="muted small"><?php echo htmlspecialchars($u['email']); ?></div></div>
        <div class="col-role"><?php echo htmlspecialchars($u['role']); ?></div>
        <div class="col-actions"><a href="<?php echo site_url('admin/users/edit?id='.$u['id']); ?>">Edit</a></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
