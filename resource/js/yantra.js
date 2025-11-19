/*!
 * yantra.js — consolidated bundle
 * - Combines: YantraForm engine (extended), helpers, and adapter
 * - Optimized: Emitter, debounce, fetchWithRetries, chunk progress throttle, toast reuse, getData/buildFormData
 *
 * Usage:
 *  - Include this single file in your page (defer recommended).
 *  - Create forms: const f = yantra.createForm('#myForm', opts);
 *  - Or new YantraForm('#myForm', opts)
 */

(function (global) {
  'use strict';

  // ensure single global object
  const yantra = global.yantra || {};

  /* ---------------------------
     Small utilities & helpers
     --------------------------- */

  // minimal noop
  function noop() {}

  // debounce utility
  function debounce(fn, wait = 200) {
    let timer = null;
    let lastArgs = null;
    return function (...args) {
      lastArgs = args;
      if (timer) clearTimeout(timer);
      timer = setTimeout(() => { timer = null; fn(...lastArgs); }, wait);
    };
  }

  // simple throttle by time (returns wrapped fn that only runs at most every `ms`)
  function throttleByTime(fn, ms) {
    let last = 0;
    let scheduled = null;
    let lastArgs = null;
    return function (...args) {
      const now = Date.now();
      lastArgs = args;
      if (now - last >= ms) {
        last = now;
        fn(...args);
      } else {
        if (scheduled) return;
        scheduled = setTimeout(() => {
          scheduled = null;
          last = Date.now();
          fn(...lastArgs);
        }, ms - (now - last));
      }
    };
  }

  // safe assignment util
  function assign(dst, src) {
    return Object.assign(dst || {}, src || {});
  }

  /* ---------------------------
     Minimal Event Emitter (fast)
     --------------------------- */
  class Emitter {
    constructor() { this._map = Object.create(null); }
    on(ev, fn) {
      if (!this._map[ev]) this._map[ev] = [];
      this._map[ev].push(fn);
      return this;
    }
    off(ev, fn) {
      const a = this._map[ev];
      if (!a) return this;
      for (let i = a.length - 1; i >= 0; --i) if (a[i] === fn) a.splice(i, 1);
      return this;
    }
    emit(ev, ...args) {
      const a = this._map[ev];
      if (!a || a.length === 0) return [];
      const results = [];
      for (let i = 0; i < a.length; ++i) {
        try { results.push(a[i](...args)); }
        catch (err) { if (console && console.error) console.error('yantra emitter handler error', err); results.push(undefined); }
      }
      return results;
    }
  }

  /* ---------------------------
     fetchWithRetries (optimized)
     --------------------------- */
  async function fetchWithRetries(url, opts = {}, retries = 1, timeout = 7000) {
    const baseOpts = Object.assign({}, opts);
    let lastErr = null;
    for (let attempt = 0; attempt <= retries; ++attempt) {
      let controller = null, timer = null;
      try {
        if (typeof AbortController !== 'undefined' && timeout > 0) {
          controller = new AbortController();
          baseOpts.signal = controller.signal;
          timer = setTimeout(() => controller.abort(), timeout);
        }
        const res = await fetch(url, baseOpts);
        if (timer) clearTimeout(timer);
        if (!res.ok) {
          const text = await res.text().catch(() => null);
          const err = new Error('HTTP ' + res.status);
          err.status = res.status;
          err.body = text;
          throw err;
        }
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.indexOf('application/json') !== -1) return await res.json().catch(() => null);
        return await res.text().catch(() => null);
      } catch (err) {
        lastErr = err.name === 'AbortError' ? new Error('Request timeout') : err;
        if (attempt >= retries) throw lastErr;
        // small backoff
        await new Promise(r => setTimeout(r, 120 * (attempt + 1)));
      } finally {
        if (timer) clearTimeout(timer);
        if (baseOpts.signal) delete baseOpts.signal;
      }
    }
    throw lastErr;
  }

  /* ---------------------------
     yantra helpers (UI + utils)
     --------------------------- */

  // Toast container cache keyed by group
  const _toastContainers = new Map();
  function _ensureToastContainer(group) {
    if (_toastContainers.has(group)) return _toastContainers.get(group);
    const container = document.createElement('div');
    container.className = 'yantra-toast-container';
    container.dataset.group = group;
    Object.assign(container.style, {
      position: 'fixed', right: '16px', bottom: '16px', zIndex: 99999,
      display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '8px'
    });
    document.body.appendChild(container);
    _toastContainers.set(group, container);
    return container;
  }

  yantra.toast = function (message, opts) {
    opts = opts || {};
    const duration = typeof opts.duration === 'number' ? opts.duration : 3500;
    const group = opts.group || 'default';
    const container = _ensureToastContainer(group);
    const el = document.createElement('div');
    el.className = 'yantra-toast';
    Object.assign(el.style, {
      background: 'rgba(0,0,0,0.85)', color: '#fff',
      padding: '8px 12px', borderRadius: '6px', boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
      maxWidth: '320px'
    });
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity .25s ease, transform .25s ease';
      el.style.opacity = 0;
      el.style.transform = 'translateY(8px)';
      setTimeout(() => el.remove(), 260);
    }, duration);
    return el;
  };

  yantra.showLoader = function () {
    if (document.getElementById('page-loader')) return;
    const loader = document.createElement('div');
    loader.id = 'page-loader';
    loader.setAttribute('aria-hidden', 'true');
    loader.innerHTML = '<div class="yantra-loader-spinner" style="width:48px;height:48px;border:6px solid rgba(0,0,0,0.1);border-top-color:#1976d2;border-radius:50%;animation:yantra-spin 1s linear infinite;margin:20px auto"></div>';
    Object.assign(loader.style, { position: 'fixed', left: 0, top: 0, right: 0, bottom: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(255,255,255,0.6)', zIndex: 100000 });
    document.body.appendChild(loader);
    if (!document.getElementById('yantra-loader-style')) {
      const s = document.createElement('style'); s.id = 'yantra-loader-style';
      s.innerHTML = '@keyframes yantra-spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}';
      document.head.appendChild(s);
    }
  };

  yantra.hideLoader = function () {
    const el = document.getElementById('page-loader'); if (el) el.remove();
    const s = document.getElementById('yantra-loader-style'); if (s) s.remove();
  };

  yantra.fetchJson = async function (url, opts) {
    opts = opts || {};
    const method = opts.method || 'GET';
    const headers = opts.headers || {};
    const body = opts.body || null;
    const timeout = opts.timeout || 0;
    let controller = null, timer = null;
    if (timeout) {
      controller = new AbortController();
      timer = setTimeout(() => controller.abort(), timeout);
    }
    const r = await fetch(url, Object.assign({ method, headers, body, signal: controller ? controller.signal : undefined }, opts));
    if (timer) clearTimeout(timer);
    const text = await r.text().catch(() => null);
    if (!r.ok) { const err = new Error('HTTP ' + r.status); err.status = r.status; err.body = text; throw err; }
    try { return JSON.parse(text); } catch (e) { return text; }
  };

  yantra.postJson = function (url, data, opts) {
    opts = opts || {};
    const headers = Object.assign({}, opts.headers || {}, { 'Content-Type': 'application/json' });
    return yantra.fetchJson(url, { method: 'POST', headers, body: JSON.stringify(data), timeout: opts.timeout || 0 });
  };

  // buildFormData optimized (recursive)
  yantra.buildFormData = function buildFormData(obj, form, namespace) {
    const fd = form || new FormData();
    namespace = namespace || '';
    for (const prop in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, prop)) continue;
      const key = namespace ? (namespace + '[' + prop + ']') : prop;
      const val = obj[prop];
      if (val instanceof Date) fd.append(key, val.toISOString());
      else if (val instanceof File || val instanceof Blob) fd.append(key, val);
      else if (Array.isArray(val)) {
        for (let i = 0; i < val.length; ++i) {
          const v = val[i];
          const k = key + '[' + i + ']';
          if (v !== null && typeof v === 'object') buildFormData(v, fd, k); else fd.append(k, v);
        }
      } else if (val !== null && typeof val === 'object') buildFormData(val, fd, key);
      else if (val !== undefined) fd.append(key, val == null ? '' : val);
    }
    return fd;
  };

  yantra.loadScript = function (src, opts) {
    opts = opts || {};
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      if (opts.async !== undefined) s.async = !!opts.async;
      if (opts.module) s.type = 'module';
      s.onload = () => resolve(s);
      s.onerror = (e) => reject(e);
      document.head.appendChild(s);
    });
  };

  yantra.store = {
    set: (k, v) => {
      try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    },
    get: (k, def) => {
      def = def === undefined ? null : def;
      try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : def; } catch (e) { try { const v = sessionStorage.getItem(k); return v ? JSON.parse(v) : def } catch (e) { return def; } }
    },
    remove: (k) => { try { localStorage.removeItem(k); } catch (e) { try { sessionStorage.removeItem(k); } catch (e) {} } },
    clear: () => { try { localStorage.clear(); } catch (e) { try { sessionStorage.clear(); } catch (e) {} } }
  };

  yantra.download = function (blobOrUrl, filename) {
    filename = filename || 'download';
    if (typeof blobOrUrl === 'string') {
      const a = document.createElement('a'); a.href = blobOrUrl; a.download = filename; a.target = '_blank'; document.body.appendChild(a); a.click(); a.remove(); return;
    }
    const url = URL.createObjectURL(blobOrUrl);
    const a = document.createElement('a'); a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  };

  yantra.copyToClipboard = function (text) {
    if (!navigator.clipboard) {
      const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch (e) { throw e; } finally { ta.remove(); }
      return Promise.resolve();
    }
    return navigator.clipboard.writeText(text);
  };

  /* ---------------------------
     Validator registry (async-capable)
     --------------------------- */
  class ValidatorRegistry {
    constructor() { this._map = Object.create(null); }
    register(name, fn) {
      if (!name || typeof fn !== 'function') throw new Error('register(name, fn) expects a function');
      this._map[name] = fn;
      return this;
    }
    has(name) { return !!this._map[name]; }

    // supports sync return or Promise
    async run(name, value, param, field) {
      const fn = this._map[name];
      if (!fn) return true;
      try {
        const res = fn(value, param, field);
        if (res && typeof res.then === 'function') return await res;
        return res;
      } catch (e) {
        if (console && console.error) console.error('validator error', e);
        return 'Validation failed';
      }
    }
  }

  /* ---------------------------
     YantraForm engine (core)
     --------------------------- */

  const DEFAULTS = {
    ajax: true, url: null, method: 'POST', validateOn: 'submit',
    queue: false, autoReset: false, autoScrollToError: true, debug: false,
    fetchRetries: 2, fetchTimeout: 7000, formDataMode: 'formdata'
  };

  function isFunction(v) { return typeof v === 'function'; }

  class YantraForm {
    constructor(selectorOrEl, options = {}) {
      this.form = (typeof selectorOrEl === 'string') ? document.querySelector(selectorOrEl) : selectorOrEl;
      if (!this.form || this.form.tagName !== 'FORM') throw new Error('YantraForm requires a form element or selector');
      this.opts = Object.assign({}, DEFAULTS, options);
      this.emitter = new Emitter();
      this.validators = new ValidatorRegistry();
      this.plugins = [];
      this._fieldListeners = [];
      this._boundSubmit = (e) => this._onDomSubmit(e);
      this._destroyed = false;
      this.debug = !!this.opts.debug;
      this.form.addEventListener('submit', this._boundSubmit);
      this._attachAutoValidation();
      this._formatters = [];
      this._formatterListeners = [];
      this.localQueue = Promise.resolve();
      this.serverGlobalErrors = [];
      try { this.form.__yantra_form__ = this; } catch (e) { }
      // init conditional and repeatables
      this._applyConditionalFields();
      this._initRepeatables();
    }

    /* public API */
    registerValidator(name, fn) { this.validators.register(name, fn); return this; }
    use(plugin) {
      if (!isFunction(plugin)) throw new Error('plugin must be a function');
      const p = plugin(this);
      this.plugins.push(p || {});
      return this;
    }
    on(event, cb) { this.emitter.on(event, cb); return this; }
    off(event, cb) { this.emitter.off(event, cb); return this; }

    // optimized getData -> plain object (handles multiple fields with same name as arrays)
    getData() {
      const data = Object.create(null);
      const fd = new FormData(this.form);
      for (const [k, v] of fd.entries()) {
        if (!Object.prototype.hasOwnProperty.call(data, k)) data[k] = v;
        else if (!Array.isArray(data[k])) data[k] = [data[k], v];
        else data[k].push(v);
      }
      return data;
    }

    // internal helpers
    _attachAutoValidation() {
      // autosave + conditional handlers using debounce
      this._autosaveHandler = debounce(() => { try { if (this.opts.autoSaveKey) this._saveDraft(); } catch (e) {} }, 400);
      this._conditionalHandler = debounce(() => { try { this._applyConditionalFields(); } catch (e) {} }, 140);
      // attach to form
      this.form.addEventListener('input', this._autosaveHandler, { passive: true });
      this.form.addEventListener('change', this._autosaveHandler, { passive: true });
      this.form.addEventListener('input', this._conditionalHandler, { passive: true });
      this.form.addEventListener('change', this._conditionalHandler, { passive: true });
    }

    // async-safe submit handler
    async _onDomSubmit(e) {
      try {
        if (this.opts.validateOn === 'submit') {
          // perform validation (supports async validators)
          const res = await this._validateAll();
          if (!res) { e.preventDefault(); this.emitter.emit('validationFailed', this); return; }
        }
        if (this.opts.ajax) {
          e.preventDefault();
          await this.submit();
        }
      } catch (err) {
        // always catch inside to avoid unhandled Promise rejections from event handler
        if (this.debug && console && console.error) console.error(err);
      }
    }

    // supports async validators
    async _validateAll() {
      const els = Array.from(this.form.querySelectorAll('[data-validate]'));
      let ok = true;

      // clear previous field errors
      for (const el of els) this._clearFieldError(el);

      for (let i = 0; i < els.length; ++i) {
        const el = els[i];
        const rules = (el.getAttribute('data-validate') || '').split('|').map(s => s.trim()).filter(Boolean);
        const value = el.type === 'file' ? (el.files && el.files[0]) : el.value;

        for (let j = 0; j < rules.length; ++j) {
          const r = rules[j];
          const [name, param] = r.indexOf(':') > -1 ? [r.slice(0, r.indexOf(':')), r.slice(r.indexOf(':') + 1)] : [r, null];
          try {
            const result = await this.validators.run(name, value, param, el);
            if (result !== true) {
              ok = false;
              this._renderFieldError(el, result);
              break; // stop at first failure for this field
            } else {
              this._clearFieldError(el);
            }
          } catch (e) {
            ok = false;
            this._renderFieldError(el, 'Validation error');
            break;
          }
        }
      }
      return ok;
    }

    _renderFieldError(el, msg) {
      this._clearFieldError(el);
      const e = document.createElement('div');
      e.className = 'yf-error';
      e.style.color = '#b00020';
      e.style.fontSize = '0.9rem';
      e.textContent = typeof msg === 'string' ? msg : String(msg || 'Invalid');
      el.insertAdjacentElement('afterend', e);
    }

    _clearFieldError(el) {
      const next = el.nextElementSibling;
      if (next && next.classList && next.classList.contains('yf-error')) next.remove();
    }

    /* submit: handles chunk upload plugin hooks, formdata/json modes and fetch with retries */
    async submit() {
      try {
        // plugin beforeSubmit hooks (allow plugins to process e.g. chunk uploads)
        for (let i = 0; i < this.plugins.length; ++i) {
          const p = this.plugins[i];
          if (p.beforeSubmit && typeof p.beforeSubmit === 'function') {
            const r = await p.beforeSubmit(this);
            if (r && r.continue === false) return r;
          }
        }

        // prepare data
        let body = null, headers = {};
        if (this.opts.formDataMode === 'formdata') {
          body = new FormData(this.form);
          // attach extraFields that can be functions
          if (this.opts.extraFields) {
            for (const k in this.opts.extraFields) {
              if (!Object.prototype.hasOwnProperty.call(this.opts.extraFields, k)) continue;
              const v = this.opts.extraFields[k];
              body.append(k, typeof v === 'function' ? (v()) : v);
            }
          }
        } else {
          body = JSON.stringify(this.getData());
          headers['Content-Type'] = 'application/json';
        }

        // basic loading UI
        this.emitter.emit('submitStart', this);
        yantra.showLoader();

        // perform network request
        const opts = { method: this.opts.method || 'POST', body, headers: assign({}, this.opts.headers || {}, headers) };
        const res = await fetchWithRetries(this.opts.url || this.form.action, opts, this.opts.fetchRetries, this.opts.fetchTimeout);

        // success hooks
        this.emitter.emit('submitSuccess', res, this);
        if (this.opts.autoReset) this.form.reset();
        return res;
      } catch (err) {
        // try map server errors if available
        const mapped = (typeof this.opts.serverErrorMapper === 'function') ? this.opts.serverErrorMapper(err) : null;
        if (mapped) this.applyServerErrors(mapped, err);
        this.emitter.emit('submitError', err, this);
        throw err;
      } finally {
        yantra.hideLoader();
        this.emitter.emit('submitEnd', this);
      }
    }

    applyServerErrors(mapped, raw) {
      if (!mapped) return false;
      const { fieldErrors = {}, globalErrors = [] } = mapped;
      const leftover = Array.isArray(globalErrors) ? globalErrors.slice() : [];
      let any = false;
      for (const k in fieldErrors) {
        if (!Object.prototype.hasOwnProperty.call(fieldErrors, k)) continue;
        const msg = fieldErrors[k];
        const el = this.form.querySelector(`[name="${k}"]`) || this.form.querySelector(`#${k}`);
        if (el) { this._renderFieldError(el, msg); any = true; }
        else leftover.push(`${k}: ${msg}`);
      }
      if (leftover.length) {
        this.serverGlobalErrors = leftover;
        this.emitter.emit('serverGlobalErrors', leftover, this);
      } else this.serverGlobalErrors = [];
      this.emitter.emit('serverValidationFailed', { fieldErrors, globalErrors: leftover }, this);
      return any;
    }

    /* repeatables (minimal impl) */
    _initRepeatables() {
      // left simple — implement as needed
    }

    _applyConditionalFields() {
      const nodes = this.form.querySelectorAll('[data-show-if]');
      for (let i = 0; i < nodes.length; ++i) {
        const n = nodes[i];
        const expr = n.getAttribute('data-show-if');
        try {
          const visible = (new Function('form', 'with(form){ return !!(' + expr + ')}'))(this.form);
          n.style.display = visible ? '' : 'none';
        } catch (e) {
          // on error hide
          n.style.display = 'none';
        }
      }
    }

    enableAutoSave(key, opts) {
      opts = opts || {};
      this.opts.autoSaveKey = key;
      this._autoSaveInterval = opts.interval || 600;
      // save periodically
      this._autoSaveTimer = setInterval(() => {
        try { this._saveDraft(); } catch (e) { }
      }, this._autoSaveInterval);
      if (opts.restore) {
        const saved = yantra.store.get(key);
        if (saved && typeof saved === 'object') {
          // simple restore (only primitive inputs, not files)
          for (const k in saved) {
            try {
              const el = this.form.querySelector(`[name="${k}"]`);
              if (el && el.type !== 'file') el.value = saved[k];
            } catch (e) {}
          }
        }
      }
      return this;
    }

    _saveDraft() {
      if (!this.opts.autoSaveKey) return;
      const data = this.getData();
      // remove File objects (they can't be stored in localStorage)
      for (const k in data) {
        const v = data[k];
        if (v instanceof File || v instanceof Blob) delete data[k];
      }
      yantra.store.set(this.opts.autoSaveKey, data);
      this.emitter.emit('autoSaved', this.opts.autoSaveKey, this);
    }

    clearDraft() {
      if (!this.opts.autoSaveKey) return;
      yantra.store.remove(this.opts.autoSaveKey);
      this.emitter.emit('autoDraftCleared', this.opts.autoSaveKey, this);
    }

    destroy() {
      if (this._destroyed) return;
      this._destroyed = true;
      try { this.form.removeEventListener('submit', this._boundSubmit); } catch (e) {}
      try { this.form.removeEventListener('input', this._autosaveHandler); this.form.removeEventListener('change', this._autosaveHandler); } catch (e) {}
      try { this.form.removeEventListener('input', this._conditionalHandler); this.form.removeEventListener('change', this._conditionalHandler); } catch (e) {}
      for (const p of this.plugins) { if (p.destroy && isFunction(p.destroy)) { try { p.destroy(this); } catch (e) { console.error(e); } } }
      this.emitter = new Emitter();
      try { delete this.form.__yantra_form__; } catch (e) {}
    }
  } // end YantraForm

  /* ---------------------------
     Validator injection helpers (non-constructor approach)
     --------------------------- */

  // copy plain map into registry
  function _copyMapIntoRegistry(map, registry) {
    if (!map || typeof map !== 'object') return;
    for (const k in map) {
      try {
        if (Object.prototype.hasOwnProperty.call(map, k) && typeof map[k] === 'function') {
          registry.register(k, map[k]);
        }
      } catch (e) { if (console && console.error) console.error('validator register error', e); }
    }
  }

  function _applyGlobalAfterLoad(registry, globalObj) {
    try {
      if (!globalObj) globalObj = (typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : null));
      if (!globalObj) return;
      if (typeof globalObj.registerValidators === 'function') {
        try { globalObj.registerValidators(registry); } catch (e) { if (console && console.error) console.error(e); }
      }
      if (globalObj.yantra && globalObj.yantra._globalValidators) {
        _copyMapIntoRegistry(globalObj.yantra._globalValidators, registry);
      }
    } catch (e) { if (console && console.error) console.error(e); }
  }

  /**
   * Instance method: register validators from a variety of sources.
   *
   * Accepts:
   *  - function(registry) { ... }         // immediate registration
   *  - { name: fn, ... }                  // object map
   *  - '/path/validations.js' or { script: '/path/validations.js' }  // dynamic load
   *  - 'registerValidators'               // global function name to call (window.registerValidators)
   *
   * Returns a Promise that resolves when async loading/registration is complete.
   */
  YantraForm.prototype.addValidators = async function (source) {
    if (!this || !this.validators) return Promise.resolve();

    // source is a registration function
    if (typeof source === 'function') {
      try { source(this.validators); } catch (e) { if (this.debug && console && console.error) console.error('addValidators(function) error', e); }
      return Promise.resolve();
    }

    // source is an object map of validators
    if (source && typeof source === 'object' && !source.script) {
      _copyMapIntoRegistry(source, this.validators);
      return Promise.resolve();
    }

    // source is a string - could be URL or global function name
    if (typeof source === 'string') {
      // treat as URL if contains a slash or starts with http(s)
      if (source.indexOf('/') === 0 || source.indexOf('http://') === 0 || source.indexOf('https://') === 0) {
        try {
          await yantra.loadScript(source);
          _applyGlobalAfterLoad(this.validators, (typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : null)));
        } catch (e) { if (this.debug && console && console.error) console.error('addValidators(load script) error', e); }
        return Promise.resolve();
      }
      // otherwise try to call a global function by name
      try {
        const globalObj = (typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : null));
        if (globalObj && typeof globalObj[source] === 'function') {
          try { globalObj[source](this.validators); } catch (e) { if (this.debug && console && console.error) console.error('addValidators(global fn) error', e); }
        } else {
          // fallback: if global map exists, copy it
          if (globalObj && globalObj.yantra && globalObj.yantra._globalValidators) {
            _copyMapIntoRegistry(globalObj.yantra._globalValidators, this.validators);
          }
        }
      } catch (e) { if (this.debug && console && console.error) console.error(e); }
      return Promise.resolve();
    }

    // source appears to be an object with a script property
    if (source && typeof source === 'object' && source.script) {
      try {
        await yantra.loadScript(source.script);
        _applyGlobalAfterLoad(this.validators, (typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : null)));
      } catch (e) { if (this.debug && console && console.error) console.error('addValidators(load script object) error', e); }
      return Promise.resolve();
    }

    // nothing matched
    return Promise.resolve();
  };

  /**
   * Global helper to register global validators (plain map or function).
   * This populates yantra._globalValidators and/or defines a global registerValidators function.
   * Use this when you want scripts loaded before forms to create a shared registry source.
   */
  yantra.addGlobalValidators = function (source) {
    const globalObj = (typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : null));
    if (!globalObj) return;

    // ensure map exists
    globalObj.yantra = globalObj.yantra || {};
    globalObj.yantra._globalValidators = globalObj.yantra._globalValidators || {};

    if (typeof source === 'function') {
      // store as the global register function too (convenience)
      try { globalObj.registerValidators = source; } catch (e) { /* ignore */ }
      // we don't call it here because there's no registry instance to attach to
      return;
    }

    if (source && typeof source === 'object') {
      // copy the map into global map
      for (const k in source) {
        try {
          if (Object.prototype.hasOwnProperty.call(source, k) && typeof source[k] === 'function') {
            globalObj.yantra._globalValidators[k] = source[k];
          }
        } catch (e) { if (console && console.error) console.error(e); }
      }
    }
  };

  /* ---------------------------
     Built-in plugins (chunkUploader minimal)
     --------------------------- */
  YantraForm.plugins = {
    chunkUploader: (options = {}) => {
      return function (engine) {
        const opt = Object.assign({ selector: 'input[type=file][data-chunk]', registerFieldPrefix: null, url: null }, options);
        // throttled progress emitter (~8 updates/sec)
        const progressEmit = throttleByTime((pct, fileEl) => {
          try { engine.emitter.emit('uploadProgress', { file: fileEl, percent: pct }, engine); } catch (e) { }
        }, 125);

        return {
          beforeSubmit: async (frmEngine) => {
            const files = Array.from(frmEngine.form.querySelectorAll(opt.selector));
            if (files.length === 0) return { continue: true };
            if (!(global && global.yantra && typeof global.yantra.uploadFileInChunks === 'function')) {
              const err = new Error('No global chunk uploader available (yantra.uploadFileInChunks)');
              return Promise.reject(err);
            }
            // upload all, but throttle progress emissions to avoid UI jank
            const promises = files.map(fi => {
              const file = fi.files && fi.files[0];
              if (!file) return Promise.resolve(null);
              const registerKey = opt.registerFieldPrefix ? (opt.registerFieldPrefix + '_' + (fi.name || fi.id || 'file')) : (fi.name || fi.id || 'file');
              const upOpts = {
                url: opt.url || undefined,
                registerFieldId: registerKey,
                onProgress: (pct) => progressEmit(pct, fi)
              };
              return global.yantra.uploadFileInChunks(file, upOpts).then(res => {
                try {
                  if (!frmEngine.opts.extraFields) frmEngine.opts.extraFields = {};
                  frmEngine.opts.extraFields[registerKey + '_file_id'] = () => res.fileId;
                } catch (e) { }
                return res;
              });
            });
            const results = await Promise.all(promises);
            return { continue: true, uploads: results };
          }
        };
      };
    },

    clientSideHints: () => {
      return function (engine) {
        return { beforeValidate(engine) { }, destroy(engine) { } };
      };
    }
  };

  // expose YantraForm constructor
  global.YantraForm = YantraForm;

  /* ---------------------------
     Adapter convenience methods (createForm, FormAdapter)
     --------------------------- */
  yantra.createForm = function (selectorOrEl, opts) {
    if (typeof global.YantraForm === 'function') { return new global.YantraForm(selectorOrEl, opts || {}); }
    console.warn('YantraForm not available.');
    return null;
  };

  yantra.FormAdapter = function (formSelectorOrEl, legacyOptions = {}) {
    const inst = yantra.createForm(formSelectorOrEl, {
      ajax: true,
      queue: !!legacyOptions.waitForQueue,
      method: legacyOptions.method || 'POST',
      url: legacyOptions.url || (typeof formSelectorOrEl === 'string' ? (document.querySelector(formSelectorOrEl) && document.querySelector(formSelectorOrEl).action) : (formSelectorOrEl && formSelectorOrEl.action) || null),
      validateOn: legacyOptions.validateOn || 'submit',
      formDataMode: legacyOptions.formDataMode || 'formdata',
      autoReset: false
    });
    if (!inst) return null;
    return {
      instance: inst,
      addEventListener: function (name, fn) {
        const map = { 'success': 'submitSuccess', 'error': 'submitError', 'validationfailed': 'validationFailed', 'upload_progress': 'uploadProgress' };
        const ev = map[name] || name;
        inst.on(ev, fn);
        return this;
      },
      updateExtraFields: function (obj) { inst.opts.extraFields = Object.assign({}, inst.opts.extraFields || {}, obj || {}); return this; },
      submit: function () { return inst.submit(); },
      destroy: function () { inst.destroy(); }
    };
  };

  // helper: default (very small) uploadFileInChunks stub (if user hasn't provided one).
  if (typeof yantra.uploadFileInChunks !== 'function') {
    yantra.uploadFileInChunks = function (file, opts) {
      // simulated uploader (resolve with fake fileId). Replace in production.
      return new Promise((resolve) => {
        const total = file.size || 1000000;
        let sent = 0;
        const step = Math.max(200000, Math.floor(total / 6));
        const id = 'fakefile_' + Date.now();
        function tick() {
          sent = Math.min(total, sent + step);
          const pct = Math.round((sent / total) * 100);
          if (opts && typeof opts.onProgress === 'function') opts.onProgress(pct);
          if (sent < total) setTimeout(tick, 160); else setTimeout(() => resolve({ fileId: id }), 260);
        }
        setTimeout(tick, 80);
      });
    };
  }

  // attach yantra to global
  try { global.yantra = Object.assign(global.yantra || {}, yantra); } catch (e) { /* ignore */ }

})(typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : this));
