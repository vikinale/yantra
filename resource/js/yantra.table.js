/* ---------------------------
   yantra.table — data table with filters, sorting, pagination + inline edit support
   (updated with column-level editor factories)
   Load after yantra.js
   --------------------------- */
(function attachYantraTable(global) {
  if (!global) return;
  const yantraObj = global.yantra = global.yantra || {};

  function _escapeCsv(val) {
    if (val === null || val === undefined) return '';
    const s = String(val);
    if (s.indexOf('"') !== -1 || s.indexOf(',') !== -1 || s.indexOf('\n') !== -1) {
      return '"' + s.replace(/"/g, '""') + '"';
    }
    return s;
  }

  function _buildQuery(obj) {
    const parts = [];
    for (const k in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, k)) continue;
      const v = obj[k];
      if (v === undefined || v === null || v === '') continue;
      parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
    }
    return parts.join('&');
  }

  function defaultRenderCell(row, col) {
    const v = row[col.key];
    return (v === null || v === undefined) ? '' : String(v);
  }

  function _filterToParams(filter, val) {
    const out = {};
    if (filter.type === 'range') {
      if (val && typeof val === 'object') {
        if (val.min !== undefined && val.min !== '') out['filter_' + filter.key + '_min'] = val.min;
        if (val.max !== undefined && val.max !== '') out['filter_' + filter.key + '_max'] = val.max;
      }
    } else {
      if (val !== undefined && val !== '' && val !== null) out['filter_' + filter.key] = val;
    }
    return out;
  }

  // very small emitter for table instances
  function EvtMap() { this.map = Object.create(null); }
  EvtMap.prototype.on = function (n, fn) { (this.map[n] = this.map[n] || []).push(fn); return this; };
  EvtMap.prototype.emit = function (n, p) { const a = this.map[n] || []; for (let i=0;i<a.length;++i) try{ a[i](p); }catch(e){console.error(e);} };

  /* ---------------------------
     Editor factory registry
     --------------------------- */
  const editorFactories = Object.create(null);

  // built-in factories
  function factory_text(row, col, opts) {
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'inline-input';
    if (opts && opts.placeholder) inp.placeholder = opts.placeholder;
    inp.value = (row[col.key] == null) ? '' : row[col.key];
    return inp;
  }
  function factory_number(row, col, opts) {
    const inp = document.createElement('input');
    inp.type = 'number';
    inp.className = 'inline-input';
    if (opts && opts.min !== undefined) inp.min = opts.min;
    if (opts && opts.max !== undefined) inp.max = opts.max;
    inp.value = (row[col.key] == null) ? '' : row[col.key];
    return inp;
  }
  function factory_date(row, col, opts) {
    const inp = document.createElement('input');
    inp.type = 'date';
    inp.className = 'inline-input';
    const v = row[col.key];
    inp.value = (typeof v === 'string' && v.length >= 10) ? v.slice(0,10) : '';
    return inp;
  }
  function factory_textarea(row, col, opts) {
    const ta = document.createElement('textarea');
    ta.className = 'inline-input';
    ta.rows = (opts && opts.rows) || 3;
    ta.value = (row[col.key] == null) ? '' : row[col.key];
    return ta;
  }
  function factory_checkbox(row, col, opts) {
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.className = 'inline-input';
    cb.checked = !!row[col.key];
    return cb;
  }
  async function factory_select(row, col, opts) {
    const select = document.createElement('select');
    select.className = 'inline-input';
    // placeholder default option if provided
    if (opts && opts.placeholder) {
      const d = document.createElement('option'); d.value = ''; d.textContent = opts.placeholder; select.appendChild(d);
    }
    let options = opts && opts.options;
    if (typeof options === 'function') {
      try { options = await options(row, col); } catch (e) { options = []; }
    }
    options = options || [];
    // allow array of {value,label} or simple strings
    for (const opt of options) {
      const o = document.createElement('option');
      if (typeof opt === 'object') { o.value = opt.value; o.textContent = opt.label != null ? opt.label : opt.value; }
      else { o.value = opt; o.textContent = opt; }
      try {
        if (String(o.value) === String(row[col.key])) o.selected = true;
      } catch (e) {}
      select.appendChild(o);
    }
    return select;
  }

  // register builtins
  editorFactories['text'] = factory_text;
  editorFactories['number'] = factory_number;
  editorFactories['date'] = factory_date;
  editorFactories['textarea'] = factory_textarea;
  editorFactories['checkbox'] = factory_checkbox;
  editorFactories['select'] = factory_select;

  // public hook: allow adding custom factories
  yantraObj.registerEditorFactory = function (name, fn) {
    if (!name || typeof fn !== 'function') throw new Error('registerEditorFactory(name, fn)');
    editorFactories[name] = fn;
  };

  /* ---------------------------
     Table class
     --------------------------- */
  function Table(containerSelectorOrEl, options = {}) {
    this.el = (typeof containerSelectorOrEl === 'string') ? document.querySelector(containerSelectorOrEl) : containerSelectorOrEl;
    if (!this.el) throw new Error('Table: container not found');
    this.opts = Object.assign({
      columns: [],
      data: [],
      serverUrl: null,
      pageSize: 10,
      pageSizes: [10,25,50,100],
      searchable: true,
      defaultSort: null,
      selectable: false,
      classes: '',
      emptyText: 'No data',
      fetchOpts: {},
      filters: [],
      inlineEdit: false,
      inlineEditAutoActions: true,
      validationMap: {}
    }, options);

    this.state = {
      q: '',
      page: 1,
      pageSize: this.opts.pageSize,
      sortKey: (this.opts.defaultSort && this.opts.defaultSort.key) || null,
      sortDir: (this.opts.defaultSort && this.opts.defaultSort.dir) || 'asc',
      total: 0,
      data: Array.isArray(this.opts.data) ? this.opts.data.slice() : [],
      filters: {}
    };

    this._ev = new EvtMap();
    this._editing = null;
    this._renderShell();
    if (this.opts.inlineEdit) this.enableInlineEdit();
    this.refresh();
  }

  Table.prototype.on = function (name, fn) { this._ev.on(name, fn); return this; };
  Table.prototype.emit = function (name, payload) { this._ev.emit(name, payload); };

  Table.prototype._renderShell = function () {
    this.el.innerHTML = '';
    const wrapper = document.createElement('div');
    wrapper.className = 'yantra-table-wrapper';

    const filtersRow = document.createElement('div'); filtersRow.className = 'yt-filters';
    const controlRow = document.createElement('div'); controlRow.className = 'yt-controls';
    const left = document.createElement('div'); left.style.display='flex'; left.style.gap='8px'; left.style.alignItems='center';
    const right = document.createElement('div'); right.style.display='flex'; right.style.gap='8px'; right.style.alignItems='center';

    if (this.opts.searchable) {
      const input = document.createElement('input'); input.type='search'; input.placeholder='Search…';
      Object.assign(input.style, { padding: '6px 10px', borderRadius: '6px', border: '1px solid #ddd' });
      right.appendChild(input);
      this._searchInput = input;
      let t = null;
      input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => { this.state.q = input.value.trim(); this.state.page = 1; this.refresh(); }, 320);
      });
    }

    const ps = document.createElement('select');
    for (const s of this.opts.pageSizes) {
      const o = document.createElement('option'); o.value = s; o.textContent = s + ' / page';
      if (s === this.state.pageSize) o.selected = true;
      ps.appendChild(o);
    }
    ps.addEventListener('change', () => { this.state.pageSize = Number(ps.value); this.state.page = 1; this.emit('pageChanged', { pageSize: this.state.pageSize }); this.refresh(); });
    right.appendChild(ps);

    const exportBtn = document.createElement('button'); exportBtn.type='button'; exportBtn.className='yt-export small'; exportBtn.textContent='Export CSV';
    exportBtn.addEventListener('click', () => this.exportCsv());
    left.appendChild(exportBtn);

    const clearFiltersBtn = document.createElement('button'); clearFiltersBtn.type='button'; clearFiltersBtn.className='small'; clearFiltersBtn.textContent='Clear Filters';
    clearFiltersBtn.addEventListener('click', () => { this.clearFilters(); });
    left.appendChild(clearFiltersBtn);

    controlRow.appendChild(left); controlRow.appendChild(right);

    const tableOuter = document.createElement('div'); tableOuter.className = 'yt-table-outer';
    tableOuter.innerHTML = `<table class="yt-table ${this.opts.classes}"><thead></thead><tbody></tbody></table>`;

    const footer = document.createElement('div'); footer.className = 'yt-footer'; footer.style.marginTop='8px';
    footer.innerHTML = `<div class="yt-pagination" style="display:flex;align-items:center;gap:8px"></div>`;

    wrapper.appendChild(filtersRow); wrapper.appendChild(controlRow); wrapper.appendChild(tableOuter); wrapper.appendChild(footer);
    this.el.appendChild(wrapper);

    this._filtersRow = filtersRow; this._ctrlLeft = left; this._ctrlRight = right;
    this._thead = tableOuter.querySelector('thead'); this._tbody = tableOuter.querySelector('tbody'); this._pagination = footer.querySelector('.yt-pagination');

    this._buildFiltersUI();
    this._renderHeader();
  };

  Table.prototype._buildFiltersUI = function () {
    this._filtersRow.innerHTML = '';
    if (!Array.isArray(this.opts.filters) || this.opts.filters.length === 0) return;

    for (const filter of this.opts.filters) {
      const wrapper = document.createElement('div'); wrapper.className = 'yt-filter'; wrapper.style.display='flex'; wrapper.style.flexDirection='column'; wrapper.style.minWidth='150px';
      const label = document.createElement('label'); label.textContent = filter.label || filter.key; label.style.fontSize='.85rem'; label.style.marginBottom='4px';
      wrapper.appendChild(label);
      let input; const type = filter.type || 'text';
      if (type === 'select') {
        input = document.createElement('select');
        const def = document.createElement('option'); def.value=''; def.textContent = filter.placeholder || '-- select --'; input.appendChild(def);
        (filter.options || []).forEach(opt => { const o = document.createElement('option'); o.value = opt.value; o.textContent = opt.label || opt.value; input.appendChild(o); });
        input.addEventListener('change', () => { this._updateFilter(filter.key, input.value); });
      } else if (type === 'range') {
        const row = document.createElement('div'); row.style.display='flex'; row.style.gap='6px';
        const min = document.createElement('input'); min.type='number'; min.placeholder = filter.placeholderMin || 'min';
        const max = document.createElement('input'); max.type='number'; max.placeholder = filter.placeholderMax || 'max';
        min.style.flex='1'; max.style.flex='1';
        min.addEventListener('input', () => { this._updateFilter(filter.key, { min: min.value, max: max.value }); });
        max.addEventListener('input', () => { this._updateFilter(filter.key, { min: min.value, max: max.value }); });
        row.appendChild(min); row.appendChild(max); wrapper.appendChild(row); this._filtersRow.appendChild(wrapper); continue;
      } else if (type === 'date') {
        input = document.createElement('input'); input.type='date'; input.addEventListener('change', () => { this._updateFilter(filter.key, input.value); });
      } else {
        input = document.createElement('input'); input.type = (type === 'number') ? 'number' : 'text'; input.placeholder = filter.placeholder || '';
        let t = null;
        input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { this._updateFilter(filter.key, input.value); }, 300); });
      }
      if (input) { input.style.padding='6px 8px'; input.style.borderRadius='6px'; input.style.border='1px solid #ddd'; wrapper.appendChild(input); }
      this._filtersRow.appendChild(wrapper);
    }
    const applyBtn = document.createElement('button'); applyBtn.type='button'; applyBtn.className='small'; applyBtn.textContent='Apply'; applyBtn.style.alignSelf='flex-end';
    applyBtn.addEventListener('click', () => { this.state.page = 1; this.refresh(); });
    this._filtersRow.appendChild(applyBtn);
  };

  Table.prototype._updateFilter = function (key, val) {
    this.state.filters = this.state.filters || {};
    if (val === '' || val === null || (typeof val === 'object' && ((val.min === '' || val.min === null) && (val.max === '' || val.max === null)))) {
      delete this.state.filters[key];
    } else {
      this.state.filters[key] = val;
    }
    this.emit('filtersChanged', { filters: this.state.filters });
  };

  Table.prototype.clearFilters = function () {
    this.state.filters = {};
    if (this._filtersRow) {
      const inputs = this._filtersRow.querySelectorAll('input, select');
      inputs.forEach(i => { try { if (i.type === 'checkbox' || i.type === 'radio') i.checked = false; else i.value = ''; } catch (e) {} });
    }
    this.emit('filtersChanged', { filters: this.state.filters });
    this.state.page = 1;
    this.refresh();
  };

  Table.prototype._renderHeader = function () {
    this._thead.innerHTML = '';
    const tr = document.createElement('tr');
    if (this.opts.selectable) {
      const th = document.createElement('th'); th.style.width = '40px'; tr.appendChild(th);
    }
    for (const col of this.opts.columns) {
      const th = document.createElement('th'); th.setAttribute('role', 'columnheader');
      th.style.cursor = col.sortable ? 'pointer' : 'default'; th.style.userSelect = 'none';
      const label = document.createElement('span'); label.textContent = col.label || col.key; th.appendChild(label);
      if (col.sortable) {
        const arrow = document.createElement('span'); arrow.className = 'yt-sort-arrow'; arrow.style.marginLeft='6px'; arrow.style.fontSize='0.8em'; arrow.textContent='';
        th.appendChild(arrow);
        th.addEventListener('click', () => {
          if (this.state.sortKey === col.key) this.state.sortDir = (this.state.sortDir === 'asc' ? 'desc' : 'asc');
          else { this.state.sortKey = col.key; this.state.sortDir = 'asc'; }
          this.state.page = 1;
          this.emit('sortChanged', { sortKey: this.state.sortKey, sortDir: this.state.sortDir });
          this.refresh();
        });
      }
      tr.appendChild(th);
    }
    this._thead.appendChild(tr);
  };

  Table.prototype._renderBody = function () {
    this._tbody.innerHTML = '';

    const start = (this.state.page - 1) * this.state.pageSize;
    const rows = this.state.data;

    if (!rows || rows.length === 0) {
      const tr = document.createElement('tr');
      const td = document.createElement('td'); td.colSpan = this.opts.columns.length + (this.opts.selectable ? 1 : 0);
      td.style.padding = '12px'; td.style.textAlign = 'center'; td.textContent = this.opts.emptyText;
      tr.appendChild(td); this._tbody.appendChild(tr); return;
    }

    const pageRows = rows.slice(start, start + this.state.pageSize);

    for (let i = 0; i < pageRows.length; ++i) {
      const row = pageRows[i];
      const tr = document.createElement('tr');
      tr.dataset.rowIndex = (start + i); tr.style.cursor = 'pointer';

      if (this.opts.selectable) {
        const tdSel = document.createElement('td'); tdSel.style.textAlign='center';
        const input = document.createElement('input'); input.type = (this.opts.selectable === 'multi') ? 'checkbox' : 'radio';
        input.name = '__yt_select'; input.dataset.idx = (start + i); tdSel.appendChild(input); tr.appendChild(tdSel);
        input.addEventListener('change', () => { this.emit('rowSelect', { selectedIndex: Number(input.dataset.idx), row }); });
      }

      for (const col of this.opts.columns) {
        const td = document.createElement('td');
        const renderer = (typeof col.render === 'function') ? col.render : defaultRenderCell;
        try {
          const inner = renderer(row, col, start + i);
          if (inner instanceof Node) td.appendChild(inner);
          else td.innerHTML = String(inner == null ? '' : inner);
        } catch (e) {
          td.textContent = defaultRenderCell(row, col);
        }
        tr.appendChild(td);
      }

      tr.addEventListener('click', (ev) => {
        if (ev.target && (ev.target.tagName === 'INPUT' || ev.target.closest('input'))) return;
        this.emit('rowClick', { index: Number(tr.dataset.rowIndex), row });
      });

      this._tbody.appendChild(tr);
    }
  };

  Table.prototype._renderPagination = function () {
    const total = this.state.total || this.state.data.length || 0;
    const totalPages = Math.max(1, Math.ceil(total / this.state.pageSize));
    this._pagination.innerHTML = '';

    const info = document.createElement('div'); info.style.marginRight='12px';
    info.textContent = `Page ${this.state.page} / ${totalPages} — ${total} rows`;
    this._pagination.appendChild(info);

    function btn(label, disabled, cb) {
      const b = document.createElement('button'); b.type='button'; b.textContent = label;
      if (disabled) b.disabled = true; b.style.padding='6px 8px'; b.style.borderRadius='6px'; b.addEventListener('click', cb); return b;
    }

    this._pagination.appendChild(btn('First', this.state.page <= 1, () => { this.state.page = 1; this.emit('pageChanged', { page: this.state.page }); this.refresh(); }));
    this._pagination.appendChild(btn('Prev', this.state.page <= 1, () => { this.state.page = Math.max(1, this.state.page - 1); this.emit('pageChanged', { page: this.state.page }); this.refresh(); }));

    const windowSize = 5;
    let startPage = Math.max(1, this.state.page - Math.floor(windowSize/2));
    let endPage = Math.min(totalPages, startPage + windowSize - 1);
    if (endPage - startPage < windowSize - 1) startPage = Math.max(1, endPage - windowSize + 1);

    for (let p = startPage; p <= endPage; ++p) {
      const b = document.createElement('button'); b.type='button'; b.textContent = String(p); b.style.padding='6px 8px';
      if (p === this.state.page) { b.disabled = true; b.style.fontWeight = '700'; }
      b.addEventListener('click', () => { this.state.page = p; this.emit('pageChanged', { page: this.state.page }); this.refresh(); });
      this._pagination.appendChild(b);
    }

    this._pagination.appendChild(btn('Next', this.state.page >= totalPages, () => { this.state.page = Math.min(totalPages, this.state.page + 1); this.emit('pageChanged', { page: this.state.page }); this.refresh(); }));
    this._pagination.appendChild(btn('Last', this.state.page >= totalPages, () => { this.state.page = totalPages; this.emit('pageChanged', { page: this.state.page }); this.refresh(); }));

    const jump = document.createElement('input'); jump.type='number'; jump.min=1; jump.max=totalPages; jump.placeholder='Go to page'; jump.style.width='110px';
    jump.addEventListener('change', () => { let v = Number(jump.value) || 1; v = Math.max(1, Math.min(totalPages, v)); this.state.page = v; this.emit('pageChanged', { page: this.state.page }); this.refresh(); jump.value=''; });
    this._pagination.appendChild(jump);
  };

  // Main refresh: fetch from server or filter client data then render
  Table.prototype.refresh = async function () {
    const filterParams = {};
    for (const k in (this.state.filters || {})) {
      const filterDef = (this.opts.filters || []).find(f => f.key === k);
      const val = this.state.filters[k];
      if (filterDef) {
        const p = _filterToParams(filterDef, val); Object.assign(filterParams, p);
      } else {
        if (val !== undefined && val !== '') filterParams['filter_' + k] = val;
      }
    }

    if (this.opts.serverUrl) {
      const qObj = Object.assign({}, filterParams, {
        q: this.state.q, page: this.state.page, pageSize: this.state.pageSize,
        sortKey: this.state.sortKey, sortDir: this.state.sortDir
      });

      try {
        const url = this.opts.serverUrl + (this.opts.serverUrl.indexOf('?') === -1 ? '?' : '&') + _buildQuery(qObj);
        const json = await ( yantra.fetchJson ? yantra.fetchJson(url, Object.assign({ method: 'GET' }, this.opts.fetchOpts)) : fetch(url).then(r=>r.json()) );
        if (Array.isArray(json)) { this.state.data = json; this.state.total = json.length; }
        else { this.state.data = Array.isArray(json.data) ? json.data : []; this.state.total = Number(json.total || this.state.data.length || 0); }
      } catch (e) { console.error('yantra.table: server fetch error', e); this.state.data = []; this.state.total = 0; }
    } else {
      let rows = Array.isArray(this.opts.data) ? this.opts.data.slice() : [];
      const filters = this.state.filters || {};
      if (filters && Object.keys(filters).length > 0) {
        rows = rows.filter(r => {
          for (const k in filters) {
            const filterDef = (this.opts.filters || []).find(f => f.key === k);
            const val = filters[k];
            if (filterDef && filterDef.type === 'range') {
              const num = Number(r[k]);
              const min = (val && val.min) ? Number(val.min) : null;
              const max = (val && val.max) ? Number(val.max) : null;
              if (min !== null && !isNaN(min) && num < min) return false;
              if (max !== null && !isNaN(max) && num > max) return false;
            } else {
              if (val === '' || val === null || val === undefined) continue;
              const vstr = (r[k] === null || r[k] === undefined) ? '' : String(r[k]).toLowerCase();
              if (String(val).toLowerCase() !== '' && vstr.indexOf(String(val).toLowerCase()) === -1) return false;
            }
          }
          return true;
        });
      }

      if (this.state.q) {
        const q = this.state.q.toLowerCase();
        rows = rows.filter(r => {
          for (const c of this.opts.columns) {
            const v = r[c.key];
            if (v !== undefined && v !== null && String(v).toLowerCase().indexOf(q) !== -1) return true;
          }
          return false;
        });
      }

      if (this.state.sortKey) {
        const key = this.state.sortKey; const dir = this.state.sortDir === 'asc' ? 1 : -1;
        rows.sort((a,b) => {
          const va = a[key], vb = b[key];
          if (va === vb) return 0; if (va === null || va === undefined) return 1*dir; if (vb === null || vb === undefined) return -1*dir;
          if (!isNaN(Number(va)) && !isNaN(Number(vb))) return (Number(va) < Number(vb) ? -1 : 1) * dir;
          return (String(va) < String(vb) ? -1 : 1) * dir;
        });
      }

      this.state.data = rows; this.state.total = rows.length;
    }

    this._updateSortArrows();
    this._renderBody();
    this._renderPagination();
    this.emit('refresh', { state: this.state });
  };

  Table.prototype._updateSortArrows = function () {
    const ths = this._thead.querySelectorAll('th');
    let colIndex = 0; if (this.opts.selectable) colIndex = 1;
    this.opts.columns.forEach((col, idx) => {
      const th = ths[colIndex + idx]; if (!th) return;
      const arrow = th.querySelector('.yt-sort-arrow'); if (!arrow) return;
      if (this.state.sortKey === col.key) arrow.textContent = this.state.sortDir === 'asc' ? '▲' : '▼'; else arrow.textContent = '↕';
    });
  };

  Table.prototype.setData = function (arr) { this.opts.data = Array.isArray(arr) ? arr.slice() : []; this.state.page = 1; return this.refresh(); };
  Table.prototype.loadData = function (params) {
    if (!params) return this.refresh();
    if (params.q !== undefined) this.state.q = String(params.q || '');
    if (params.page !== undefined) this.state.page = Number(params.page) || 1;
    if (params.pageSize !== undefined) this.state.pageSize = Number(params.pageSize) || this.state.pageSize;
    if (params.sortKey !== undefined) this.state.sortKey = params.sortKey;
    if (params.sortDir !== undefined) this.state.sortDir = params.sortDir;
    if (params.filters !== undefined) this.state.filters = params.filters;
    return this.refresh();
  };

  Table.prototype.exportCsv = function (opts) {
    opts = opts || {};
    const cols = this.opts.columns;
    const rows = this.state.data;
    const start = (this.state.page - 1) * this.state.pageSize;
    const pageRows = rows.slice(start, start + this.state.pageSize);
    const headers = cols.map(c => _escapeCsv(c.label || c.key)).join(',');
    const body = pageRows.map(r => cols.map(c => _escapeCsv((r[c.key] === undefined ? '' : r[c.key]))).join(',')).join('\n');
    const csv = headers + '\n' + body;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const filename = opts.filename || ('table-export-' + (new Date()).toISOString().slice(0,19).replace(/[:T]/g,'-') + '.csv');
    if (typeof navigator.msSaveBlob !== 'undefined') return navigator.msSaveBlob(blob, filename);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = filename; a.style.display='none'; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  };

  /* ---------------------------
     Validation runner (uses window.yantraValidationRules if present)
     --------------------------- */
  function runValidationRule(ruleName, value, param) {
    const rmap = (window && window.yantraValidationRules) || null;
    const fn = rmap && rmap[ruleName];
    if (typeof fn === 'function') {
      try { return fn(value, param); } catch (e) { return 'Invalid'; }
    }
    try {
      if (ruleName === 'required') return (value === null || value === undefined || String(value).trim() === '') ? 'Required' : true;
      if (ruleName === 'minLength') return (String(value || '').length >= Number(param || 0)) ? true : ('Must be at least ' + param + ' chars');
      if (ruleName === 'integer') return (/^-?\d+$/).test(String(value)) ? true : 'Must be integer';
      if (ruleName === 'number') return (!isNaN(Number(value))) ? true : 'Must be number';
      if (ruleName === 'min') return (Number(value) >= Number(param || 0)) ? true : ('Must be ≥ ' + param);
      if (ruleName === 'max') return (Number(value) <= Number(param || 0)) ? true : ('Must be ≤ ' + param);
    } catch (e) { return 'Invalid'; }
    return true;
  }

  /* ---------------------------
     INLINE EDITING SUPPORT with editor factories
     --------------------------- */

  Table.prototype.enableInlineEdit = function () {
    const self = this;
    if (this.opts.inlineEditAutoActions) {
      const hasActions = this.opts.columns.some(c => c.key === '_actions');
      if (!hasActions) {
        this.opts.columns.push({
          key: '_actions',
          label: 'Actions',
          editable: false,
          sortable: false,
          render: (row) => {
            const wrap = document.createElement('div'); wrap.className='actions-col';
            const edit = document.createElement('button'); edit.type='button'; edit.className='small btn-edit'; edit.textContent='Edit'; edit.dataset.id = row.id; wrap.appendChild(edit);
            return wrap;
          }
        });
        this._renderHeader();
      }
    }

    this._inlineClickHandler = (ev) => {
      const editBtn = ev.target.closest('.btn-edit');
      if (editBtn) { const tr = editBtn.closest('tr'); if (!tr) return; this._startInlineEdit(tr); return; }
      const saveBtn = ev.target.closest('.btn-inline-save');
      if (saveBtn) { const tr = saveBtn.closest('tr'); if (!tr) return; this._commitInlineEdit(tr); return; }
      const cancelBtn = ev.target.closest('.btn-inline-cancel');
      if (cancelBtn) { const tr = cancelBtn.closest('tr'); if (!tr) return; this._cancelInlineEdit(tr); return; }
    };
    this.el.addEventListener('click', this._inlineClickHandler);
    return this;
  };

  Table.prototype.disableInlineEdit = function () {
    if (this._inlineClickHandler) this.el.removeEventListener('click', this._inlineClickHandler);
    this._inlineClickHandler = null; return this;
  };

  // helper: create editor element via factory spec
  // returns { el: HTMLElement, readValue: () => any, writeValue: (v) => void }
  Table.prototype._createEditorForColumn = async function (rowData, col) {
    // precedence:
    // 1) col.editor (legacy function that returns element)
    // 2) col.editorFactory (string|object|function)
    // 3) default fallback: text input
    if (col.editor && typeof col.editor === 'function') {
      const el = col.editor(rowData, col);
      return { el, read: () => (el.type === 'checkbox' ? el.checked : el.value), write: (v) => { if (el.type === 'checkbox') el.checked = !!v; else el.value = (v == null ? '' : v); } };
    }

    const factorySpec = col.editorFactory || (col.editorOptions && col.editorOptions.type) || 'text';
    // function factory
    if (typeof factorySpec === 'function') {
      const maybe = factorySpec(rowData, col, col.editorOptions || {});
      const el = (maybe instanceof Promise) ? await maybe : maybe;
      return { el, read: () => (el.type === 'checkbox' ? el.checked : el.value), write: (v) => { if (el.type === 'checkbox') el.checked = !!v; else el.value = (v == null ? '' : v); } };
    }

    // object spec or string
    if (typeof factorySpec === 'object') {
      const type = factorySpec.type;
      const opts = Object.assign({}, factorySpec, col.editorOptions || {});
      const factoryFn = editorFactories[type];
      if (!factoryFn) {
        // fallback text
        const el = factory_text(rowData, col, opts);
        return { el, read: () => el.value, write: (v) => { el.value = v == null ? '' : v; } };
      }
      const maybe = factoryFn(rowData, col, opts);
      const el = (maybe instanceof Promise) ? await maybe : maybe;
      // select factory returns <select> which we read .value
      return {
        el,
        read: () => {
          if (el.type === 'checkbox') return el.checked;
          if (el.tagName === 'SELECT') return el.value;
          return el.value;
        },
        write: (v) => {
          if (el.type === 'checkbox') el.checked = !!v;
          else if (el.tagName === 'SELECT') {
            try { el.value = (v == null ? '' : v); } catch(e) {}
          } else el.value = (v == null ? '' : v);
        }
      };
    }

    // string spec
    if (typeof factorySpec === 'string') {
      const f = editorFactories[factorySpec];
      const opts = col.editorOptions || {};
      if (f) {
        const maybe = f(rowData, col, opts);
        const el = (maybe instanceof Promise) ? await maybe : maybe;
        return { el, read: () => el.type === 'checkbox' ? el.checked : el.value, write: (v) => { if (el.type === 'checkbox') el.checked = !!v; else el.value = (v == null ? '' : v); } };
      }
    }

    // fallback text input
    const el = factory_text(rowData, col, col.editorOptions || {});
    return { el, read: () => el.value, write: (v) => { el.value = v == null ? '' : v; } };
  };

  Table.prototype._startInlineEdit = async function (tr) {
    if (this._editing) { yantra.toast('Finish current edit first', { group: 'table' }); return; }
    const rowIndex = Number(tr.dataset.rowIndex);
    const rowData = this.state.data[rowIndex];
    if (!rowData) return;
    const tds = Array.from(tr.children);
    const originalCells = tds.map(td => td.innerHTML);
    const inputs = [];

    // build editors for editable columns only
    for (let ci = 0; ci < this.opts.columns.length; ++ci) {
      const col = this.opts.columns[ci];
      const td = tds[ci];
      if (!td) continue;
      if (col.key === '_actions' || col.editable === false) continue;
      try {
        const ed = await this._createEditorForColumn(rowData, col);
        // write initial value (factory may already set but ensure)
        ed.write(rowData[col.key]);
        td.innerHTML = '';
        td.appendChild(ed.el);
        const err = document.createElement('div'); err.className='error-inline'; err.style.display='none'; td.appendChild(err);
        inputs.push({ key: col.key, read: ed.read, write: ed.write, el: ed.el, err, col });
      } catch (e) {
        // fallback: text input
        const txt = factory_text(rowData, col, col.editorOptions || {});
        td.innerHTML = ''; td.appendChild(txt);
        const err = document.createElement('div'); err.className='error-inline'; err.style.display='none'; td.appendChild(err);
        inputs.push({ key: col.key, read: () => txt.value, write: (v) => txt.value = v, el: txt, err, col });
      }
    }

    // action cell replace
    const actionIndex = this.opts.columns.findIndex(c => c.key === '_actions');
    if (actionIndex !== -1) {
      const actionTd = tds[actionIndex];
      actionTd.innerHTML = '';
      const saveBtn = document.createElement('button'); saveBtn.type='button'; saveBtn.className='btn-inline-save small'; saveBtn.textContent='Save';
      const cancelBtn = document.createElement('button'); cancelBtn.type='button'; cancelBtn.className='btn-inline-cancel small'; cancelBtn.textContent='Cancel';
      const genErr = document.createElement('span'); genErr.className='error-inline'; genErr.style.display='none';
      actionTd.appendChild(saveBtn); actionTd.appendChild(cancelBtn); actionTd.appendChild(genErr);
    }

    this._editing = { tr, rowIndex, id: rowData.id, originalCells, inputs };
    this.emit('editStart', { id: rowData.id, row: rowData });

    // focus first editor
    setTimeout(() => { if (this._editing && this._editing.inputs.length) { const e0 = this._editing.inputs[0].el; e0.focus && e0.focus(); if (e0.select) e0.select(); } }, 10);

    // keyboard handlers
    const self = this;
    function moveFocusNext(current, dir) {
      const flat = self._editing.inputs.map(x => x.el);
      let i = flat.indexOf(current);
      if (i === -1) return;
      i += dir; if (i < 0) i = 0; if (i >= flat.length) i = flat.length - 1;
      flat[i].focus && flat[i].focus(); if (flat[i].select) flat[i].select();
    }

    function validateAllAndShow() {
      let ok = true;
      self._editing.inputs.forEach(item => {
        const rules = (self.opts.validationMap && self.opts.validationMap[item.key]) || [];
        let res = true;
        for (let r of rules) {
          const idx = r.indexOf(':');
          const name = idx === -1 ? r : r.slice(0, idx);
          const param = idx === -1 ? null : r.slice(idx+1);
          res = runValidationRule(name, item.read(), param);
          if (res !== true) break;
        }
        if (res !== true) { item.err.textContent = res; item.err.style.display = 'inline-block'; ok = false; }
        else { item.err.textContent = ''; item.err.style.display = 'none'; }
      });
      return ok;
    }

    // attach per-input listeners
    this._editing.inputKeyHandlers = [];
    this._editing.inputs.forEach(({ el }) => {
      const h = (ev) => {
        const mac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const ctrlKey = mac ? ev.metaKey : ev.ctrlKey;
        if (ev.key === 'Escape') { ev.preventDefault(); self._cancelInlineEdit(self._editing.tr); return; }
        if ((ctrlKey) && ev.key.toLowerCase() === 'enter') { ev.preventDefault(); self._commitInlineEdit(self._editing.tr); return; }
        if (ev.key === 'Enter') { ev.preventDefault(); moveFocusNext(ev.target, 1); return; }
      };
      el.addEventListener('keydown', h);
      this._editing.inputKeyHandlers.push({ el, h });
    });

    // global handler
    this._editing.globalHandler = function (ev) {
      const mac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
      const ctrlKey = mac ? ev.metaKey : ev.ctrlKey;
      if (!self._editing) return;
      if ((ctrlKey) && ev.key.toLowerCase() === 'enter') { ev.preventDefault(); self._commitInlineEdit(self._editing.tr); }
      else if (ev.key === 'Escape') { ev.preventDefault(); self._cancelInlineEdit(self._editing.tr); }
    };
    document.addEventListener('keydown', this._editing.globalHandler);
  };

  Table.prototype._commitInlineEdit = function (tr) {
    if (!this._editing) return;
    const inputs = this._editing.inputs;
    // optimistic validation
    let ok = true;
    for (const it of inputs) {
      const rules = (this.opts.validationMap && this.opts.validationMap[it.key]) || [];
      for (let r of rules) {
        const idx = r.indexOf(':'); const name = idx === -1 ? r : r.slice(0, idx); const param = idx === -1 ? null : r.slice(idx+1);
        const res = runValidationRule(name, it.read(), param);
        if (res !== true) { it.err.textContent = res; it.err.style.display = 'inline-block'; ok = false; break; }
        else { it.err.textContent = ''; it.err.style.display = 'none'; }
      }
    }
    if (!ok) return;

    // apply to master data
    const master = this.opts.data;
    const id = this._editing.id;
    const idx = master.findIndex(r => Number(r.id) === Number(id));
    const updated = Object.assign({}, master[idx] || {});
    this._editing.inputs.forEach(it => {
      let v = it.read();
      if (it.col && (it.col.key === 'amount' || it.el.type === 'number')) { v = (v === '' ? null : Number(v)); }
      updated[it.key] = v;
    });
    if (idx !== -1) master[idx] = updated;

    this._teardownEditing();
    this.setData(master);
    this.emit('editSave', { id, row: updated });
    yantra.toast('Saved', { group: 'table' });
  };

  Table.prototype._cancelInlineEdit = function (tr) {
    if (!this._editing) return;
    const orig = this._editing.originalCells; const tds = Array.from(tr.children);
    for (let i=0;i<tds.length;++i) tds[i].innerHTML = orig[i];
    const id = this._editing.id; this._teardownEditing(); this.emit('editCancel', { id });
  };

  Table.prototype._teardownEditing = function () {
    if (!this._editing) return;
    (this._editing.inputKeyHandlers || []).forEach(o => { try { o.el.removeEventListener('keydown', o.h); } catch(e){} });
    if (this._editing.globalHandler) document.removeEventListener('keydown', this._editing.globalHandler);
    this._editing = null;
  };

  Table.prototype.startInlineEditById = function (id) {
    const idx = this.state.data.findIndex(r => Number(r.id) === Number(id));
    if (idx === -1) return;
    const page = Math.floor(idx / this.state.pageSize) + 1; this.state.page = page;
    this.refresh().then(() => {
      const trs = Array.from(this._tbody.querySelectorAll('tr'));
      for (const tr of trs) if (Number(tr.dataset.rowIndex) === idx) { this._startInlineEdit(tr); break; }
    });
  };

  Table.prototype.destroy = function () { try { this.disableInlineEdit(); } catch (e) {} try { this.el.innerHTML = ''; } catch (e) {} };

  // attach factory
  yantraObj.createTable = function (container, options) { return new Table(container, options); };

  // minimal styles injection if not present
  if (!document.getElementById('yantra-table-styles')) {
    const s = document.createElement('style'); s.id = 'yantra-table-styles';
    s.innerHTML = `
      .yantra-table-wrapper { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
      .yt-table { width:100%; border-collapse:collapse; }
      .yt-table th, .yt-table td { padding:8px 10px; border-bottom:1px solid #eee; text-align:left; }
      .yt-table thead th { background:#fafafa; font-weight:700; }
      .yt-table tbody tr:hover { background:#fbfbfb; }
      .yt-controls { display:flex; justify-content:space-between; margin-bottom:8px; }
      .yt-footer { margin-top:8px; display:flex; justify-content:space-between; align-items:center; }
      .yt-pagination button { margin-left:6px; }
      .yt-filters { margin-bottom:8px; }
      .yt-filter label { font-size:.85rem; margin-bottom:4px; color:#333; display:block; }
      .small { padding:6px 8px; border-radius:6px; background:#f3f4f6; border:0; cursor:pointer; }
      .inline-input { width:100%; box-sizing:border-box; padding:6px 8px; border-radius:4px; border:1px solid #ddd; font-size:14px; }
      .actions-col { display:flex; gap:6px; align-items:center; }
      .error-inline { color:#b00020; font-size:0.85rem; margin-left:6px; display:none; }
    `;
    document.head.appendChild(s);
  }
})(typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : this));
