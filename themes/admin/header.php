<!-- header.php (improved - Yantra) -->
<!-- Topbar -->
<header class="topbar" role="banner">
  <div class="inner">
    <div class="left">
      <button id="sidebarToggle" aria-label="Toggle sidebar" class="icon-btn" title="Toggle menu">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="#222" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>

      <div class="brand" aria-hidden="false">
        <div class="logo" aria-hidden="true">A</div>
        <div class="site-info">
          <div style="font-weight:700"><?php echo htmlspecialchars($page->getMeta('site_name', 'Atlas'), ENT_QUOTES, 'UTF-8'); ?></div>
          <div style="font-size:12px;color:var(--muted)"><?php echo htmlspecialchars($page->getMeta('site_subtitle', 'Admin'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>
    </div>

    <div class="top-actions" role="navigation" aria-label="Topbar actions">
      <div class="search-input" role="search">
        <input id="topSearch" placeholder="Search. (press /)" aria-label="Search">
      </div>

      <button class="icon-btn" id="notificationsBtn" title="Notifications" aria-haspopup="true" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a2 2 0 002-2H10a2 2 0 002 2zM18 16v-5a6 6 0 10-12 0v5l-2 2v1h16v-1l-2-2z" fill="#222"/></svg>
      </button>

      <div class="user-menu">
        <button id="userBtn" class="user-btn" aria-haspopup="true" aria-expanded="false">
            <img src="<?php echo $page->getMeta('user_avatar', "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='28' height='28'><rect width='28' height='28' rx='6' fill='%230073aa'/><text x='50%' y='58%' font-size='14' text-anchor='middle' fill='white' font-family='Arial'>VN</text></svg>"); ?>" alt="User avatar">
            <div style="text-align:left">
            <div style="font-weight:700"><?php echo htmlspecialchars($page->getMeta('user_name', 'Vikas'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="font-size:12px;color:var(--muted)"><?php echo htmlspecialchars($page->getMeta('user_role', 'Administrator'), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </button>

        <div id="userDropdown" class="dropdown" role="menu" aria-hidden="true">
          <a href="<?php echo htmlspecialchars($page->getMeta('profile_url', '#'), ENT_QUOTES, 'UTF-8'); ?>">Profile</a>
          <a href="<?php echo htmlspecialchars($page->getMeta('settings_url', '#'), ENT_QUOTES, 'UTF-8'); ?>">Profile Settings</a>
          <a href="<?php echo htmlspecialchars($page->getMeta('logout_url', '#'), ENT_QUOTES, 'UTF-8'); ?>">Log out</a>
        </div>
      </div>
    </div>
  </div>
</header>
