<div class="sidebar" id="sidebar">
    <ul>
        <li><a href="#"><span class="icon">🏠</span><span class="label">Dashboard</span></a></li>
        <li class="parent-menu"><a href="#"><span class="icon">📝</span><span class="label">Posts</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/posts') ?>">All Posts</a></li>
                <li><a href="<?php echo site_url('admin/posts/add') ?>">Add New</a></li>
                <li><a href="<?php echo site_url('admin/posts/categories') ?>">Categories</a></li>
                <li><a href="<?php echo site_url('admin/posts/tags') ?>">Tags</a></li>
            </ul>
        </li>
        <li class="parent-menu"><a href="#"><span class="icon">📂</span><span class="label">Media</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/media') ?>">Library</a></li>
                <li><a href="<?php echo site_url('admin/media/add') ?>">Add New</a></li>
            </ul>
        </li>
        <li class="parent-menu"><a href="#"><span class="icon">🗃️</span><span class="label">Pages</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/pages') ?>">All Pages</a></li>
                <li><a href="<?php echo site_url('admin/add') ?>">Add New</a></li>
            </ul>
        </li>
        <li class="parent-menu"><a href="#"><span class="icon">👥</span><span class="label">Users</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/users') ?>">All Users</a></li>
                <li><a href="<?php echo site_url('admin/users/add') ?>">Add New</a></li>
                <li><a href="<?php echo site_url('admin/profile') ?>">Your Profile</a></li>
            </ul>
        </li>
        <li class="parent-menu"><a href="#"><span class="icon">🛠️</span><span class="label">Tools</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/tools') ?>">Available Tools</a></li>
                <li><a href="<?php echo site_url('admin/tools/import') ?>">Import</a></li>
                <li><a href="<?php echo site_url('admin/tools/export') ?>">Export</a></li>
                <li><a href="<?php echo site_url('admin/tools/site-health') ?>">Site Health</a></li>
            </ul>
        </li>
        <li class="parent-menu"><a href="#"><span class="icon">⚙️</span><span class="label">Settings</span></a>
            <ul class="submenu">
                <li><a href="<?php echo site_url('admin/settings') ?>">General</a></li>
                <li><a href="<?php echo site_url('admin/settings/writing') ?>">Writing</a></li>
                <li><a href="<?php echo site_url('admin/settings/reading') ?>">Reading</a></li>
                <li><a href="<?php echo site_url('admin/settings/discussion') ?>">Discussion</a></li>
                <li><a href="<?php echo site_url('admin/settings/media') ?>">Media</a></li>
                <li><a href="<?php echo site_url('admin/settings/permalinks') ?>">Permalinks</a></li>
                <li><a href="<?php echo site_url('admin/settings/privacy') ?>">Privacy</a></li>
            </ul>
        </li>
    </ul>
</div>