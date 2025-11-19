<?php if (!defined('YANTRA')) exit; ?>
<section class="admin-form">
  <header class="page-head"><h1><?php echo htmlspecialchars($page->getMeta('title','Edit User')); ?></h1></header>

  <form method="post" action="<?php echo site_url('admin/users/save'); ?>">
    <input type="hidden" name="csrf_token" value="">
    <div class="card">
      <div class="form-row">
        <label>Name</label><input name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
      </div>
      <div class="form-row">
        <label>Email</label><input name="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
      </div>
      <div class="form-row">
        <label>Role</label>
        <select name="role">
          <option value="admin" <?php if (($user['role'] ?? '') === 'admin') echo 'selected'; ?>>Administrator</option>
          <option value="editor" <?php if (($user['role'] ?? '') === 'editor') echo 'selected'; ?>>Editor</option>
        </select>
      </div>
      <div class="form-row actions"><button class="btn" type="submit">Save user</button></div>
    </div>
  </form>
</section>
