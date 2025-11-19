// scripts.js — resilient admin theme interactions (Yantra)
(function () {
  'use strict';

  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const userBtn = document.getElementById('userBtn');
  const userDropdown = document.getElementById('userDropdown');
  const searchInput = document.getElementById('topSearch');

  // Abort if there is no sidebarToggle — avoids console errors on minimal pages
  if (!sidebarToggle) return;

  // helpers
  const qs = (sel, ctx = document) => ctx.querySelector(sel);
  const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  function closeAllSubmenus() {
    qsa('.submenu').forEach(s => {
      s.style.display = 'none';
      s.setAttribute('aria-hidden', 'true');
    });
    qsa('.nav-item-toggle').forEach(t => t.textContent = '▸');
  }

  function setSidebarCollapsed(collapsed) {
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed', collapsed);
    sidebar.style.width = collapsed
      ? getComputedStyle(document.documentElement).getPropertyValue('--sidebar-collapsed') || '64px'
      : getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width') || '240px';
    try { localStorage.setItem('yantra_sidebar_collapsed', String(collapsed)); } catch(e) {}
  }

  // initialize collapsed state from storage
  try {
    const saved = localStorage.getItem('yantra_sidebar_collapsed');
    if (saved === 'true' && sidebar) setSidebarCollapsed(true);
  } catch (e) {}

  // Toggle behaviour (mobile vs desktop)
  function onSidebarToggleClick(e) {
    if (!sidebar) return;
    if (window.innerWidth <= 800) {
      sidebar.classList.toggle('open');
      sidebar.setAttribute('aria-hidden', sidebar.classList.contains('open') ? 'false' : 'true');
    } else {
      const willCollapse = !sidebar.classList.contains('collapsed');
      setSidebarCollapsed(willCollapse);
    }
  }
  sidebarToggle.addEventListener('click', onSidebarToggleClick);

  // Nav items toggles
  qsa('.nav-item').forEach(item => {
    item.setAttribute('tabindex', '0');
    item.addEventListener('click', (e) => {
      const submenu = qs('.submenu', item);
      const toggle = qs('.nav-item-toggle', item);
      if (submenu) {
        const isOpen = submenu.style.display === 'block';
        closeAllSubmenus();
        if (!isOpen) {
          submenu.style.display = 'block';
          submenu.setAttribute('aria-hidden', 'false');
          if (toggle) toggle.textContent = '▾';
        } else {
          submenu.style.display = 'none';
          submenu.setAttribute('aria-hidden', 'true');
          if (toggle) toggle.textContent = '▸';
        }
      } else {
        qsa('.nav-item').forEach(n => n.classList.remove('active'));
        item.classList.add('active');
        closeAllSubmenus();
      }
    });
    // keyboard
    item.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); item.click(); }
    });
  });

  // Subitem behaviour (mark active and close overlay on mobile)
  qsa('.subitem').forEach(si => {
    si.addEventListener('click', (e) => {
      qsa('.nav-item, .subitem').forEach(x => x.classList.remove('active'));
      si.classList.add('active');
      if (window.innerWidth <= 800 && sidebar) sidebar.classList.remove('open');
    });
  });

  // User menu
  if (userBtn && userDropdown) {
    userBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = userDropdown.style.display !== 'block';
      userDropdown.style.display = open ? 'block' : 'none';
      userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      userDropdown.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (open) userDropdown.querySelector('a, button, [tabindex]')?.focus();
    });
    document.addEventListener('click', (ev) => {
      if (!userBtn.contains(ev.target) && !userDropdown.contains(ev.target)) {
        userDropdown.style.display = 'none';
        userDropdown.setAttribute('aria-hidden', 'true');
        userBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Slash focuses search; escape closes overlays
  document.addEventListener('keydown', (ev) => {
    if (ev.key === '/' && searchInput && document.activeElement !== searchInput) { ev.preventDefault(); searchInput.focus(); }
    if (ev.key === 'Escape') {
      if (sidebar) sidebar.classList.remove('open');
      if (userDropdown) { userDropdown.style.display = 'none'; }
      closeAllSubmenus();
    }
  });

  // Responsive cleanup on resize (debounced)
  let rt = null;
  window.addEventListener('resize', () => {
    if (rt) clearTimeout(rt);
    rt = setTimeout(() => {
      if (window.innerWidth > 800 && sidebar) sidebar.classList.remove('open');
    }, 150);
  });

})();


(function () {
  document.querySelectorAll('.nav-item').forEach(function (item) {
    item.addEventListener('click', function (e) {
      var has = item.classList.contains('expanded');
      // collapse all siblings if desired:
      // document.querySelectorAll('.nav-item.expanded').forEach(s => s.classList.remove('expanded'));
      if (item.classList.contains('expanded')) item.classList.remove('expanded');
      else item.classList.add('expanded');
    });

    item.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        item.click();
      }
    });
  });
})();
