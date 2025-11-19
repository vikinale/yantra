/**
 * attachResponsive(table, options)
 * Adds responsive helpers + a small toggle to switch between table/card view on small screens.
 * Persists user choice in localStorage per table (key derived from table container selector or element id).
 *
 * Returns an object with .destroy()
 */
function attachResponsive(table, options) {
  const opts = Object.assign({
    cardBelow: 560,
    collapseBelow: 1024,
    priorities: {}, // { id:1, name:1, status:2, amount:1, created:3 }
    hideIfPriorityGreaterOrEqual: 3,
    stickyHeader: false,
    storageKeyPrefix: 'yantra.table.responsive' // storage key prefix
  }, options || {});

  if (!table || !table.el) throw new Error('attachResponsive: invalid table instance');
  const wrapper = table.el.querySelector('.yantra-table-wrapper') || table.el;
  wrapper.classList.add('responsive-enabled');
  if (opts.stickyHeader) wrapper.classList.add('sticky-header');

  // determine storage key unique per table DOM node
  let storageKey = null;
  if (wrapper.id) storageKey = opts.storageKeyPrefix + '.' + wrapper.id;
  else if (table.el && table.el.dataset && table.el.dataset.tableId) storageKey = opts.storageKeyPrefix + '.' + table.el.dataset.tableId;
  else {
    // fallback: compute a key from column names and container selector if possible
    try {
      const colNames = (table.opts && table.opts.columns || []).map(c => c.key).join(',');
      const selectorSnippet = (table.el && table.el.tagName ? table.el.tagName : 'el') + '|' + (table.el.id || '') + '|' + (table.el.className || '');
      storageKey = opts.storageKeyPrefix + '.' + btoa(colNames + '::' + selectorSnippet).slice(0, 24);
    } catch (e) {
      storageKey = opts.storageKeyPrefix + '.unknown';
    }
  }

  // card container (create/reuse)
  let cardsEl = wrapper.querySelector('.yt-cards');
  if (!cardsEl) {
    cardsEl = document.createElement('div');
    cardsEl.className = 'yt-cards';
    cardsEl.style.display = 'none';
    wrapper.appendChild(cardsEl);
  }

  // toggle UI container (create/reuse)
  let toggleEl = wrapper.querySelector('.yt-view-toggle');
  if (!toggleEl) {
    toggleEl = document.createElement('div');
    toggleEl.className = 'yt-view-toggle';
    toggleEl.innerHTML = `
      <button type="button" class="yt-toggle-btn" title="Toggle view">
        <span class="vt-table">Table</span>
        <span class="vt-card" style="display:none">Cards</span>
      </button>
    `;
    // place toggle in top-right of wrapper (inside controls if present)
    const ctrl = wrapper.querySelector('.yt-controls');
    if (ctrl) ctrl.appendChild(toggleEl);
    else wrapper.insertBefore(toggleEl, wrapper.firstChild);
  }

  // dynamic stylesheet for hiding columns by index
  const styleId = 'yt-responsive-hide-styles';
  let styleEl = document.getElementById(styleId);
  if (!styleEl) {
    styleEl = document.createElement('style'); styleEl.id = styleId;
    document.head.appendChild(styleEl);
  }

  // compute column index map for nth-child (1-based)
  function getColumnIndexMap() {
    const map = {};
    const cols = table.opts.columns || [];
    const selectableOffset = table.opts.selectable ? 1 : 0;
    for (let i = 0; i < cols.length; ++i) {
      // nth-child index for header/body = selectableOffset + (i+1)
      map[cols[i].key] = selectableOffset + i + 1;
    }
    return map;
  }

  // hide columns by keys using CSS rules in styleEl
  function setHiddenColsByKeys(keys) {
    const colIndexMap = getColumnIndexMap();
    const parts = [];
    keys.forEach(k => {
      const n = colIndexMap[k];
      if (!n) return;
      parts.push(`.yantra-table-wrapper.responsive-hide .yt-table thead th:nth-child(${n})`);
      parts.push(`.yantra-table-wrapper.responsive-hide .yt-table tbody td:nth-child(${n})`);
    });
    if (parts.length === 0) styleEl.textContent = '';
    else styleEl.textContent = parts.join(',') + ' { display: none !important; }';
  }

  // render cards for current pageRows
  function renderCards() {
    cardsEl.innerHTML = '';
    const state = table.state || {};
    const start = (state.page - 1) * state.pageSize;
    const rows = (state.data || []).slice(start, start + state.pageSize);
    const columns = table.opts.columns || [];

    rows.forEach(row => {
      const card = document.createElement('div');
      card.className = 'yt-card';

      // header with actions if exist
      const actionsIndex = columns.findIndex(c => c.key === '_actions');
      if (actionsIndex !== -1) {
        const actionsCol = columns[actionsIndex];
        const actionNode = (typeof actionsCol.render === 'function') ? actionsCol.render(row) : null;
        if (actionNode) {
          const ha = document.createElement('div'); ha.style.display='flex'; ha.style.justifyContent='flex-end';
          // If render returned a Node inside table, it may be already used; clone it to avoid moving it out
          ha.appendChild(actionNode instanceof Node ? actionNode.cloneNode(true) : actionNode);
          card.appendChild(ha);
        }
      }

      columns.forEach(col => {
        if (col.key === '_actions') return;
        const rowEl = document.createElement('div'); rowEl.className = 'yt-card-row';
        const label = document.createElement('div'); label.className = 'label'; label.textContent = col.label || col.key;
        const value = document.createElement('div'); value.className = 'value';
        try {
          const rendered = (typeof col.render === 'function') ? col.render(row, col) : (row[col.key] == null ? '' : String(row[col.key]));
          if (rendered instanceof Node) value.appendChild(rendered.cloneNode(true));
          else value.innerHTML = String(rendered == null ? '' : rendered);
        } catch (e) { value.textContent = row[col.key] == null ? '' : String(row[col.key]); }
        rowEl.appendChild(label); rowEl.appendChild(value); card.appendChild(rowEl);
      });

      cardsEl.appendChild(card);
    });
  }

  // compute keys to hide using provided priorities
  function computeHideKeys() {
    const priorities = opts.priorities || {};
    const keysToHide = [];
    for (const k in priorities) {
      const p = priorities[k];
      if (p >= opts.hideIfPriorityGreaterOrEqual) keysToHide.push(k);
    }
    return keysToHide;
  }

  // load stored preference for this table (values: 'auto' | 'table' | 'cards')
  function loadStoredPref() {
    try {
      const v = localStorage.getItem(storageKey);
      return v || 'auto';
    } catch (e) { return 'auto'; }
  }
  function saveStoredPref(v) {
    try { localStorage.setItem(storageKey, v); } catch (e) {}
  }

  // update toggle UI labels
  function updateToggleUI(mode) {
    const btn = toggleEl.querySelector('.yt-toggle-btn');
    if (!btn) return;
    const spanTable = btn.querySelector('.vt-table');
    const spanCard = btn.querySelector('.vt-card');
    if (!spanTable || !spanCard) return;
    if (mode === 'cards') { spanCard.style.display = ''; spanTable.style.display = 'none'; btn.title = 'Switch to table view'; }
    else { spanCard.style.display = 'none'; spanTable.style.display = ''; btn.title = 'Switch to card view'; }
  }

  // main responsive update
  let currentMode = 'auto'; // 'auto'|'table'|'cards'
  function onResize() {
    const w = window.innerWidth;
    const stored = loadStoredPref(); // user preference
    let effective = stored === 'auto' ? 'auto' : stored;

    if (effective === 'auto') {
      // auto decide by width
      if (w <= opts.cardBelow) effective = 'cards';
      else if (w <= opts.collapseBelow) effective = 'collapse';
      else effective = 'table';
    }

    // keep currentMode to avoid unnecessary DOM ops
    if (effective === 'cards') {
      wrapper.classList.add('cards-view');
      wrapper.classList.remove('responsive-hide');
      cardsEl.style.display = 'block';
      renderCards();
      updateToggleUI('cards');
    } else if (effective === 'collapse') {
      wrapper.classList.remove('cards-view');
      const hideKeys = computeHideKeys();
      setHiddenColsByKeys(hideKeys);
      if (hideKeys.length) wrapper.classList.add('responsive-hide'); else wrapper.classList.remove('responsive-hide');
      cardsEl.style.display = 'none';
      updateToggleUI('table');
    } else { // table
      wrapper.classList.remove('cards-view');
      wrapper.classList.remove('responsive-hide');
      styleEl.textContent = '';
      cardsEl.style.display = 'none';
      updateToggleUI('table');
    }

    currentMode = effective;
  }

  // wire toggle button behavior
  (function wireToggle() {
    const btn = toggleEl.querySelector('.yt-toggle-btn');
    if (!btn) return;
    // initialize from storage
    const pref = loadStoredPref();
    if (pref === 'cards') {
      // show cards initially only if width small enough (cards below) OR user forced
      // we'll call onResize() to apply after wiring
    }

    btn.addEventListener('click', (ev) => {
      ev.preventDefault();
      const cur = loadStoredPref(); // 'auto'|'table'|'cards'
      // toggle sequence: auto -> cards -> table -> auto
      let next;
      if (cur === 'auto') next = 'cards';
      else if (cur === 'cards') next = 'table';
      else if (cur === 'table') next = 'auto';
      else next = 'auto';
      saveStoredPref(next);
      onResize();
    });
  })();

  // initial call and event wiring
  onResize();
  const resizeHandler = debounce(onResize, 120);
  window.addEventListener('resize', resizeHandler);

  const refreshListener = function () { if (wrapper.classList.contains('cards-view')) renderCards(); };
  table.on && table.on('refresh', refreshListener);

  // return API
  return {
    destroy: function () {
      window.removeEventListener('resize', resizeHandler);
      table.off && table.off('refresh', refreshListener);
      try { styleEl.textContent = ''; } catch (e) {}
      wrapper.classList.remove('cards-view', 'responsive-hide', 'sticky-header');
      try { cardsEl.remove(); } catch (e) {}
      try { toggleEl.remove(); } catch (e) {}
    }
  };

  // tiny debounce helper used inside (re-defined here to avoid extra deps)
  function debounce(fn, wait) {
    let timer = null;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), wait);
    };
  }
}
