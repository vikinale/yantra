<?php if (!defined('YANTRA')) exit; ?>
<section class="container mt-4">
  <div class="card shadow-sm">
    <div class="card-header bg-warning text-white"><h5 class="mb-0">My Profile</h5></div>
    <div class="card-body">
      <form id="admin-profile-form" action="<?php echo site_url('admin/profile/save'); ?>" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES); ?>">
        <div class="row">
          <div class="col-md-3 text-center">
            <?php
            $avatar = $meta['avatar'] ?? ($meta['avatar'] ?? '');
            $avatarUrl = $avatar ? site_url($avatar['file_path'].'/'.$avatar['file_name']) : theme_url('images/default-avatar.png');
            ?>
            <img id="avatar-preview" src="<?php echo $avatarUrl; ?>" class="img-fluid rounded mb-2" style="max-width:160px;">
            <div class="small text-muted">PNG/JPG, max 2MB</div>
            <div class="field-error small text-danger mt-1" id="avatar-error"></div>
            <!-- mark file input with data-chunk so yantra.chunkUploader picks it -->
            <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg" class="form-control mt-2" data-chunk>
          </div>
          <div class="col-md-9">
            <div class="mb-3">
              <label>Display name</label>
              <input name="display_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name'] ?? '', ENT_QUOTES); ?>" data-validate="required|minLength:2">
              <div id="display_name-error" class="field-error small text-danger mt-1"></div>
            </div>

            <div class="mb-3">
              <label>Username</label>
              <input name="username" id="username" class="form-control" required value="<?php echo htmlspecialchars($admin['username'] ?? '', ENT_QUOTES); ?>" data-validate="required|minLength:3">
              <div id="username-error" class="field-error small text-danger mt-1"></div>
            </div>

            <div class="mb-3">
              <label>Email</label>
              <input name="email" id="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($admin['email'] ?? '', ENT_QUOTES); ?>" data-validate="required|email">
              <div id="email-error" class="field-error small text-danger mt-1"></div>
            </div>

            <div class="mb-3">
              <label>Bio</label>
              <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($admin['bio'] ?? '', ENT_QUOTES); ?></textarea>
              <div id="bio-error" class="field-error small text-danger mt-1"></div>
            </div>

            <div class="text-end">
              <button class="btn btn-warning" id="profile-save-btn" type="submit">Save profile</button>
            </div>

            <div class="message-container mt-3" role="status" aria-live="polite"></div>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php add_action('custom_css',function(){
    ?>
    <style>
        /* ===== Profile form styles (scoped to #admin-profile-form) ===== */
        #admin-profile-form .card-header {
        background: #ffc107; /* keep existing bg-warning look */
        color: #fff;
        padding: 1rem 1.25rem;
        border-top-left-radius: .375rem;
        border-top-right-radius: .375rem;
        box-shadow: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .5rem;
        }

        /* card body spacing */
        #admin-profile-form .card-body {
        padding: 1.25rem;
        }

        /* layout: avatar column + fields column */
        #admin-profile-form .row {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
        flex-wrap: wrap;
        }

        /* left column (avatar) sizing */
        #admin-profile-form .col-md-3 {
        flex: 0 0 220px;
        max-width: 220px;
        text-align: center;
        padding-top: .25rem;
        }

        /* right column grows */
        #admin-profile-form .col-md-9 {
        flex: 1 1 0;
        min-width: 260px;
        }

        /* avatar preview */
        #admin-profile-form #avatar-preview {
        width: 160px;
        height: 160px;
        object-fit: cover;
        display: inline-block;
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 6px 18px rgba(31,45,61,0.05);
        background-color: #fff;
        }

        /* small helper under avatar */
        #admin-profile-form .col-md-3 .small {
        display: block;
        margin-top: .5rem;
        color: #6c757d;
        font-size: 0.85rem;
        }

        /* form fields */
        #admin-profile-form .form-control,
        #admin-profile-form textarea.form-control,
        #admin-profile-form .form-control-file {
        width: 100%;
        padding: .5rem .625rem;
        border: 1px solid #dfe7ea;
        border-radius: .375rem;
        background-color: #fff;
        box-shadow: none;
        transition: border-color .12s ease, box-shadow .12s ease;
        font-size: 0.95rem;
        color: #1f2d3d;
        }

        /* textarea spacing */
        #admin-profile-form textarea.form-control {
        min-height: 110px;
        resize: vertical;
        }

        /* input focus */
        #admin-profile-form .form-control:focus,
        #admin-profile-form textarea.form-control:focus {
        outline: none;
        border-color: #8ab6c7;
        box-shadow: 0 0 0 3px rgba(138,182,199,0.08);
        }

        /* labels */
        #admin-profile-form label {
        display: block;
        margin-bottom: .35rem;
        font-weight: 600;
        color: #21313b;
        font-size: .95rem;
        }

        /* field rows spacing */
        #admin-profile-form .mb-3 {
        margin-bottom: .9rem;
        }

        /* field error */
        #admin-profile-form .field-error {
        color: #d9534f;
        font-size: .85rem;
        margin-top: .35rem;
        }

        /* message container */
        #admin-profile-form .message-container {
        min-height: 1.4rem;
        font-size: 0.95rem;
        }

        /* buttons */
        #admin-profile-form .btn.btn-warning {
        background-color: #f0ad4e;
        color: #fff;
        border: none;
        padding: .5rem 1rem;
        border-radius: .375rem;
        box-shadow: 0 6px 18px rgba(31,45,61,0.04);
        transition: transform .06s ease, box-shadow .06s ease, opacity .06s ease;
        }
        #admin-profile-form .btn.btn-warning:active { transform: translateY(1px); }
        #admin-profile-form .btn.btn-warning:disabled { opacity: .7; cursor: not-allowed; }

        /* small file input style (improve appearance) */
        #admin-profile-form input[type="file"] {
        border: 1px dashed #e6eef0;
        padding: .35rem .5rem;
        border-radius: .375rem;
        background: linear-gradient(180deg,#fff,#fbfdfe);
        }

        /* responsive: stack columns on small screens */
        @media (max-width: 767.98px) {
        #admin-profile-form .col-md-3,
        #admin-profile-form .col-md-9 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        #admin-profile-form #avatar-preview {
            width: 120px;
            height: 120px;
        }

        #admin-profile-form .row {
            gap: 1rem;
        }
        }

        /* subtle card shadow refinement */
        #admin-profile-form .card {
        border-radius: .5rem;
        box-shadow: 0 6px 20px rgba(31,45,61,0.05);
        border: 1px solid rgba(31,45,61,0.04);
        }

        /* keep small help/error text readable */
        #admin-profile-form .small,
        #admin-profile-form .field-error,
        #admin-profile-form .message-container p {
        margin: 0;
        line-height: 1.25;
        }

        /* ensure wide inputs inside narrow cells wrap nicely */
        #admin-profile-form input[type="text"],
        #admin-profile-form input[type="email"],
        #admin-profile-form input[type="tel"] {
        box-sizing: border-box;
        }

    </style>
    <?php
}); ?>

<?php add_action('footer_scripts', function(){ ?>
<script>
(function(){
  const formEl = document.getElementById('admin-profile-form');
  if (!formEl) return;

  // --- tiny DOM helpers ---
  const $ = sel => formEl.querySelector(sel);
  const setFieldErrors = errors => {
    formEl.querySelectorAll('.field-error').forEach(e => e.textContent = '');
    if (!errors) return;
    Object.keys(errors).forEach(k => {
      const el = document.getElementById(k+'-error');
      if (el) el.textContent = errors[k];
    });
  };
  const showMessage = (html) => { const m = $('.message-container'); if (m) m.innerHTML = html; };

  // --- avatar preview + quick client check (2MB, PNG/JPEG) ---
  const avatarIn = document.getElementById('avatar');
  const avatarPreview = document.getElementById('avatar-preview');
  const avatarErr = document.getElementById('avatar-error');
  function okAvatar(f){
    avatarErr.textContent = '';
    if (!f) return true;
    if (!/^image\/(png|jpeg)$/.test(f.type)) { avatarErr.textContent = 'Only PNG/JPG allowed.'; return false; }
    if (f.size > 2*1024*1024) { avatarErr.textContent = 'Max 2MB.'; return false; }
    return true;
  }
  avatarIn.addEventListener('change', e => {
    const f = e.target.files && e.target.files[0];
    if (!okAvatar(f)) return;
    if (!f) { avatarPreview.src = '<?php echo theme_url("images/default-avatar.png"); ?>'; return; }
    const r = new FileReader(); r.onload = ev => avatarPreview.src = ev.target.result; r.readAsDataURL(f);
  });

  // --- if yantra available: use it (short init) ---
  if (typeof yantra !== 'undefined' && typeof yantra.createForm === 'function' && typeof YantraForm !== 'undefined') {
    const yf = yantra.createForm('#admin-profile-form', {
      url: formEl.action,
      method: 'POST',
      ajax: true,
      formDataMode: 'formdata',
      fetchRetries: 1,
      fetchTimeout: 10000,
      autoReset: false
    });

    // validators (short)
    yf.registerValidator('required', v => (v && v.toString().trim() !== '') || 'This field is required');
    yf.registerValidator('email', v => (!v || /^\S+@\S+\.\S+$/.test(v)) || 'Invalid email');
    yf.registerValidator('minLength', (v, n) => (!v || v.length >= parseInt(n,10)) || `Minimum ${n} characters required`);

    // autosave & chunk uploader
    yf.enableAutoSave('admin_profile_draft', { interval: 1000, restore: true });
    yf.use(YantraForm.plugins.chunkUploader({
      selector: 'input[data-chunk]',
      registerFieldPrefix: 'avatar',
      url: '<?php echo site_url("admin/profile/upload-chunk"); ?>'
    }));

    // map server response errors to UI
    yf.opts.serverErrorMapper = raw => {
      try {
        const parsed = (typeof raw === 'string') ? JSON.parse(raw) : raw;
        if (parsed && typeof parsed === 'object') return { fieldErrors: parsed.errors || {}, globalErrors: parsed.message ? [parsed.message] : [] };
      } catch(e){}
      return null;
    };

    yf.on('submitSuccess', res => {
      // res usually parsed JSON; handle legacy wrapper { content: '...' }
      let data = res && res.content && typeof res.content === 'string' ? JSON.parse(res.content) : res;
      setFieldErrors(data && data.errors ? data.errors : null);
      if (data && data.status) {
        if (data.admin && data.admin.avatar) avatarPreview.src = data.admin.avatar;
        if (data.csrf) { const t = formEl.querySelector('input[name="csrf_token"]'); if (t) t.value = data.csrf; }
        showMessage('<p class="text-success">'+(data.message||'Saved')+'</p>');
        yantra.toast(data.message || 'Profile saved');
      } else {
        showMessage('<p class="text-danger">'+(data && data.message || 'Failed')+'</p>');
        yantra.toast(data && data.message || 'Save failed', { duration: 5000 });
      }
    });

    yf.on('submitError', err => {
      showMessage('<p class="text-danger">Network error.</p>');
      yantra.toast('Network error', { duration: 4000 });
      console.error('Profile save error', err);
    });

    // show upload progress optionally
    yf.on('uploadProgress', info => {
      // keep short: we don't render UI here, but you can use yantra.toast or console
      // console.debug('upload', info.percent);
    });

    // toggle save button during submit
    const saveBtn = document.getElementById('profile-save-btn');
    yf.on('submitStart', () => { if (saveBtn) saveBtn.disabled = true; });
    yf.on('submitEnd',   () => { if (saveBtn) saveBtn.disabled = false; });

    return;
  }

})();
</script>
<?php }, 9); ?>