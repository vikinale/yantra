// validations.js
// ES module that exports a single default function:
//   import registerValidators from './validations.js';
//   registerValidators(registry);

export default function registerValidators(registry) {
  if (!registry || typeof registry.register !== 'function') {
    throw new Error('registerValidators expects a ValidatorRegistry instance');
  }

  const register = (name, fn) => registry.register(name, fn);

  const isEmpty = v => v === null || v === undefined || String(v).trim() === '';

  /* --------------------
     Basic / Presence
  -------------------- */
  register('required', (value, param, field) => {
    if (!field) return !isEmpty(value) ? true : 'This field is required';
    const t = (field.type || '').toLowerCase();
    if (t === 'checkbox') return field.checked ? true : 'This field is required';
    if (t === 'radio') {
      const name = field.name;
      if (name && field.form) {
        const group = field.form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
        for (let i = 0; i < group.length; i++) if (group[i].checked) return true;
      }
      return 'This field is required';
    }
    if (t === 'file') return (field.files && field.files.length > 0) ? true : 'Please select a file';
    return !isEmpty(value) ? true : 'This field is required';
  });

  /* --------------------
     Length / String
  -------------------- */
  register('minLength', (v, p) => (isEmpty(v) ? true : (String(v).length >= (parseInt(p, 10) || 0) ? true : `Minimum length is ${p}`)));
  register('maxLength', (v, p) => (isEmpty(v) ? true : (String(v).length <= (parseInt(p, 10) || 0) ? true : `Maximum length is ${p}`)));
  register('length', (v, p) => (String(v).length === (parseInt(p, 10) || 0) ? true : `Length must be ${p}`));
  register('betweenLength', (v, p) => {
    if (!p || p.indexOf(',') === -1) return true;
    const [a, b] = p.split(',').map(x => parseInt(x, 10));
    const L = String(v || '').length;
    return (L >= a && L <= b) ? true : `Length must be between ${a} and ${b}`;
  });

  /* --------------------
     Numeric
  -------------------- */
  register('number', v => (isEmpty(v) ? true : (!isNaN(Number(v)) ? true : 'Must be a number')));
  register('integer', v => (isEmpty(v) ? true : (Number.isInteger(Number(v)) ? true : 'Must be an integer')));
  register('min', (v, p) => (isEmpty(v) ? true : (Number(v) >= Number(p) ? true : `Must be at least ${p}`)));
  register('max', (v, p) => (isEmpty(v) ? true : (Number(v) <= Number(p) ? true : `Must be no greater than ${p}`)));
  register('between', (v, p) => {
    if (!p || p.indexOf(',') === -1) return true;
    const [a, b] = p.split(',').map(Number);
    const n = Number(v);
    return (n >= a && n <= b) ? true : `Must be between ${a} and ${b}`;
  });

  /* --------------------
     Char sets
  -------------------- */
  register('alpha', v => (isEmpty(v) ? true : (/^[A-Za-z]+$/.test(v) ? true : 'Only letters allowed')));
  register('alphaNum', v => (isEmpty(v) ? true : (/^[A-Za-z0-9]+$/.test(v) ? true : 'Only letters & numbers allowed')));
  register('alphaDash', v => (isEmpty(v) ? true : (/^[A-Za-z0-9_-]+$/.test(v) ? true : 'Only letters, numbers, dash & underscore allowed')));

  /* --------------------
     Email / URL
  -------------------- */
  register('email', v => (isEmpty(v) ? true : (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v)) ? true : 'Invalid email')));
  register('url', v => {
    if (isEmpty(v)) return true;
    try { new URL(String(v)); return true; } catch (e) { return 'Invalid URL'; }
  });

  /* --------------------
     Dates & times
  -------------------- */
  function parseDateSafe(val) {
    if (isEmpty(val)) return null;
    const d = new Date(String(val));
    return isNaN(d.getTime()) ? null : d;
  }
  register('date', v => parseDateSafe(v) ? true : 'Invalid date');
  register('before', (v, p) => { const a = parseDateSafe(v), b = parseDateSafe(p); if (!a || !b) return true; return a < b ? true : `Must be before ${p}`; });
  register('after', (v, p) => { const a = parseDateSafe(v), b = parseDateSafe(p); if (!a || !b) return true; return a > b ? true : `Must be after ${p}`; });
  register('betweenDates', (v, p) => {
    if (!p || p.indexOf(',') === -1) return true;
    const [s, e] = p.split(',');
    const d = parseDateSafe(v), a = parseDateSafe(s), b = parseDateSafe(e);
    if (!d || !a || !b) return true;
    return (d >= a && d <= b) ? true : `Date must be between ${s} and ${e}`;
  });

  /* --------------------
     Regex / Pattern
  -------------------- */
  register('pattern', (v, p) => {
    if (isEmpty(v) || !p) return true;
    try {
      let re;
      if (p[0] === '/' && p.lastIndexOf('/') > 0) {
        const last = p.lastIndexOf('/');
        re = new RegExp(p.slice(1, last), p.slice(last + 1));
      } else re = new RegExp(p);
      return re.test(String(v)) ? true : 'Invalid format';
    } catch (e) { return true; }
  });

  /* --------------------
     Field matching
  -------------------- */
  register('sameAs', (v, p, field) => {
    if (!field || !field.form || !p) return true;
    const other = field.form.querySelector(`[name="${p}"]`);
    if (!other) return true;
    return String(v) === String(other.value) ? true : `Must match ${p}`;
  });
  register('equals', (v, p) => (String(v) === String(p) ? true : `Must equal ${p}`));
  register('notEquals', (v, p) => (String(v) !== String(p) ? true : `Must not equal ${p}`));

  /* --------------------
     Boolean / Checkbox
  -------------------- */
  register('boolean', v => ((v === true || v === false || v === '1' || v === '0') ? true : 'Must be true or false'));

  /* --------------------
     File / Image (async-capable)
  -------------------- */
  register('file', (v, p, field) => (field && field.files && field.files.length > 0) ? true : 'Please select a file');
  register('fileType', (v, p, field) => {
    if (!field || !field.files || !field.files[0] || !p) return true;
    const allowed = p.split(',').map(s => s.trim().toLowerCase());
    const t = (field.files[0].type || '').toLowerCase();
    return allowed.some(a => t.indexOf(a) !== -1) ? true : `Allowed types: ${allowed.join(', ')}`;
  });
  register('fileSize', (v, p, field) => {
    if (!field || !field.files || !field.files[0] || !p) return true;
    const maxKB = Number(p);
    const sizeKB = field.files[0].size / 1024;
    return sizeKB <= maxKB ? true : `Max file size is ${p} KB`;
  });
  register('image', (v, p, field) => (field && field.files && field.files[0] && (field.files[0].type || '').startsWith('image/')) ? true : 'Only images allowed');

  register('imageWidth', (v, p, field) => {
    if (!field || !field.files || !field.files[0] || !p) return true;
    return new Promise(res => {
      const img = new Image();
      const url = URL.createObjectURL(field.files[0]);
      img.onload = () => { URL.revokeObjectURL(url); res(img.width == Number(p) ? true : `Width must be ${p}px`); };
      img.onerror = () => { URL.revokeObjectURL(url); res(true); };
      img.src = url;
    });
  });

  register('imageHeight', (v, p, field) => {
    if (!field || !field.files || !field.files[0] || !p) return true;
    return new Promise(res => {
      const img = new Image();
      const url = URL.createObjectURL(field.files[0]);
      img.onload = () => { URL.revokeObjectURL(url); res(img.height == Number(p) ? true : `Height must be ${p}px`); };
      img.onerror = () => { URL.revokeObjectURL(url); res(true); };
      img.src = url;
    });
  });

  /* --------------------
     Advanced common validators
  -------------------- */
  // Credit card (Luhn)
  function luhn(num) {
    const s = String(num).replace(/\D/g, '');
    let sum = 0, alt = false;
    for (let i = s.length - 1; i >= 0; i--) {
      let n = parseInt(s.charAt(i), 10);
      if (alt) { n *= 2; if (n > 9) n -= 9; }
      sum += n; alt = !alt;
    }
    return sum % 10 === 0;
  }
  register('creditcard', v => {
    if (isEmpty(v)) return true;
    const s = String(v).replace(/\s+/g, '');
    return (/^\d{12,19}$/.test(s) && luhn(s)) ? true : 'Invalid credit card';
  });

  // Phone (lenient)
  register('phone', v => {
    if (isEmpty(v)) return true;
    const s = String(v).trim();
    return (/^\+?[0-9\-\s().]{6,20}$/.test(s)) ? true : 'Invalid phone number';
  });

  // UUID v1..5
  register('uuid', v => (isEmpty(v) ? true : (/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(String(v)) ? true : 'Invalid UUID')));

  // IP v4/v6
  register('ip', v => {
    if (isEmpty(v)) return true;
    const s = String(v).trim();
    const ipv4 = /^(25[0-5]|2[0-4]\d|[01]?\d\d?)(\.(25[0-5]|2[0-4]\d|[01]?\d\d?)){3}$/;
    const ipv6 = /^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i;
    return (ipv4.test(s) || ipv6.test(s)) ? true : 'Invalid IP address';
  });

  // Domain
  register('domain', v => {
    if (isEmpty(v)) return true;
    const s = String(v).trim();
    const re = /^(?=.{1,253}$)(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*$/;
    return re.test(s) ? true : 'Invalid domain';
  });

  // slug
  register('slug', v => (isEmpty(v) ? true : (/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(String(v)) ? true : 'Invalid slug')));

  // hex color
  register('hexColor', v => (isEmpty(v) ? true : (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(String(v)) ? true : 'Invalid color')));

  // JSON
  register('json', v => {
    if (isEmpty(v)) return true;
    try { JSON.parse(String(v)); return true; } catch (e) { return 'Invalid JSON'; }
  });

  // base64
  register('base64', v => {
    if (isEmpty(v)) return true;
    const s = String(v).replace(/\s+/g, '');
    return (/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/.test(s)) ? true : 'Invalid base64';
  });

  // hex
  register('hex', v => (isEmpty(v) ? true : (/^[0-9a-fA-F]+$/.test(String(v)) ? true : 'Invalid hex')));

  // password strength (simple)
  register('passwordStrength', v => {
    if (isEmpty(v)) return true;
    const s = String(v);
    let score = 0;
    if (s.length >= 8) score++;
    if (/[a-z]/.test(s)) score++;
    if (/[A-Z]/.test(s)) score++;
    if (/[0-9]/.test(s)) score++;
    if (/[^A-Za-z0-9]/.test(s)) score++;
    return score >= 3 ? true : 'Password too weak';
  });

  // currency (basic)
  register('currency', v => (isEmpty(v) ? true : (/^[0-9]+(\.[0-9]{1,2})?$/.test(String(v)) ? true : 'Invalid currency')));

  // time (HH:MM or HH:MM:SS)
  register('time', v => (isEmpty(v) ? true : (/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/.test(String(v)) ? true : 'Invalid time')));

  // datetime (ISO-ish)
  register('datetime', v => (isEmpty(v) ? true : (isNaN(new Date(String(v)).getTime()) ? 'Invalid datetime' : true)));

  // domain with limited TLDs: domainTld:com,org
  register('domainTld', (v, p) => {
    if (isEmpty(v)) return true;
    const parts = String(v).split('.');
    if (parts.length < 2) return 'Invalid domain';
    if (!p) return true;
    const tld = parts[parts.length - 1].toLowerCase();
    return (p.split(',').map(x => x.trim().toLowerCase()).indexOf(tld) !== -1) ? true : `Domain must be one of: ${p}`;
  });

  /* --------------------
     Remote (server-side) validator example
     - Usage: data-validate="remote:/api/validate-email"
     - remote validator expects the endpoint to accept { value } JSON and respond { ok: true } or { ok:false, message: '...' }
  -------------------- */
  register('remote', (v, p) => {
    if (!p) return true;
    if (typeof fetch === 'undefined') return true;
    const url = String(p);
    const payload = { value: v };
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(r => r.json().catch(() => null))
      .then(json => {
        if (!json) return true;
        if (json.ok === false) return (json.message || 'Server validation failed');
        return true;
      })
      .catch(() => true);
  });
}