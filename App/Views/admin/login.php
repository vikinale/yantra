<?php
// content-only login view for Yantra (drop into theme view)
if (!defined('YANTRA')) exit;
?>
<section class="auth-page">
  <div class="auth-card">
    <!-- optional logo -->
    <!-- <div class="auth-logo" style="background-image: url('<?php echo theme_url('images/logo.png'); ?>');"></div> -->

    <header class="auth-header">
      <h1><?php echo htmlspecialchars($page->getMeta('title', 'Admin Login')); ?></h1>
    </header>

    <!-- NOTE: Server-side error_message removed because endpoint returns JSON.
         Keep for debug: <?php /* if (!empty($error_message)): ... */ ?> -->

    <form id="admin-login-form" method="post" action="<?php echo site_url('admin/login'); ?>"
          class="auth-form" autocomplete="off" novalidate>
      <!-- CSRF token (also sent as X-CSRF-Token header by JS) -->
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES); ?>">

      <div class="form-row mb-3">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autofocus validate="required"
               value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>"
               class="form-control" aria-describedby="username-error">
        <div id="username-error" class="field-error small text-danger mt-1" aria-live="polite"></div>
      </div>

      <div class="form-row mb-3">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required validate="required" autocomplete="current-password"
               class="form-control" aria-describedby="password-error">
        <div id="password-error" class="field-error small text-danger mt-1" aria-live="polite"></div>
      </div>

      <div class="form-row mb-3 text-center">
        <button type="submit" class="btn btn-primary" id="admin-login-submit">
          <span id="admin-login-submit-text">Sign in</span>
          <span id="admin-login-submit-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
      </div>

      <div class="form-row" style="margin-top:8px;">
        <a href="<?php echo site_url('admin/forgot'); ?>">Forgot Password</a>
      </div>

      <!-- General message container for success/error not tied to a single field -->
      <div id="message-container" class="message-container text-center mt-3" role="status" aria-live="polite"></div>
      <div id="error-container" class="error-container text-center mt-3"></div>
    </form>

    <footer class="auth-footer">
      <small>© <?php echo date('Y'); ?> Admin</small>
    </footer>
  </div>
</section>

<?php add_action('footer_scripts', function () { ?>
  <script type="text/javascript">
    (function () {
      // ensure yantra and FormHandler are available
      if (typeof yantra === 'undefined' || !yantra.FormHandler) return;

      // initialize form handler
      const fh = new yantra.FormHandler('#admin-login-form', false, { useNativeValidity: false });

      const submitBtn = document.getElementById('admin-login-submit');
      const submitText = document.getElementById('admin-login-submit-text');
      const submitSpinner = document.getElementById('admin-login-submit-spinner');
      const messageContainer = document.getElementById('message-container');
      const errorContainer = document.getElementById('error-container');

      const fieldMap = {
        username: 'username-error',
        password: 'password-error'
      };

      function setSubmitting(isSubmitting) {
        if (!submitBtn) return;
        submitBtn.disabled = !!isSubmitting;
        if (isSubmitting) {
          submitSpinner.classList.remove('d-none');
        } else {
          submitSpinner.classList.add('d-none');
        }
      }

      function clearFieldErrors() {
        Object.values(fieldMap).forEach(id => {
          const el = document.getElementById(id);
          if (el) el.textContent = '';
        });
      }

      function showFormMessage(type, text) {
        // type: 'success' or 'error' or 'info'
        if (!messageContainer || !errorContainer) return;
        if (type === 'success') {
          messageContainer.classList.remove('d-none');
          messageContainer.classList.remove('text-danger');
          messageContainer.classList.add('text-success');
          messageContainer.textContent = text;
          errorContainer.classList.add('d-none');
          errorContainer.textContent = '';
        } else {
          errorContainer.classList.remove('d-none');
          errorContainer.classList.add('text-danger');
          errorContainer.textContent = text;
          messageContainer.classList.add('d-none');
          messageContainer.textContent = '';
        }
      }

      // per-field validation complete: show message if validation failed for that validator
      Object.keys(fieldMap).forEach(fieldId => {
        fh.addEventListener(`${fieldId}_validation_complete`, ({ field, message, validator }) => {
          const fb = document.getElementById(fieldMap[fieldId]);
          if (!fb) return;
          fb.textContent = message || '';
        });
      });

      // validation failed: show first error and focus
      fh.addEventListener('validationfailed', ({ errors }) => {
        clearFieldErrors();
        if (errors && typeof errors === 'object') {
          // put field errors
          const keys = Object.keys(errors);
          if (keys.length) {
            keys.forEach(k => {
              const fbId = fieldMap[k];
              if (fbId) {
                const el = document.getElementById(fbId);
                if (el) el.textContent = errors[k];
              }
            });
            // focus first field
            const firstKey = keys[0];
            const fieldEl = document.getElementById(firstKey);
            if (fieldEl && typeof fieldEl.focus === 'function') fieldEl.focus();
          }
        }
        showFormMessage('error', 'Please fix validation errors and try again.');
      });

      fh.addEventListener('validationsuccess', () => {
        clearFieldErrors();
        showFormMessage('info', 'Validation passed.');
        // hide info after brief moment
        setTimeout(() => {
          if (messageContainer && messageContainer.textContent === 'Validation passed.') {
            messageContainer.textContent = '';
            messageContainer.classList.add('d-none');
          }
        }, 900);
      });

      // disable submit while upload/submit is in progress
      let inFlight = false;

      // when form submit starts (beforesubmit is cancellable - not used here)
      fh.addEventListener('beforesubmit', (formData, handler) => {
        // prevent double submits
        if (inFlight) return false;
        // clear messages and enable spinner
        errorContainer.textContent = '';
        errorContainer.classList.add('d-none');
        messageContainer.textContent = '';
        messageContainer.classList.add('d-none');
        setSubmitting(true);
        inFlight = true;
        return true; // allow proceed
      });

      // Response received + parsed handlers
      fh.addEventListener('response_received', (response) => {
        // response object with ok,status,content
      });

      fh.addEventListener('response_parsed', (parsed, handler, formElement) => {
        // parsed could be various shapes. Support both legacy and API envelope
        // Legacy: { status: true|false, message: "...", errors: { field: msg }, redirect: "/admin" }
        // API envelope: { status: "success", code:200, data: { ... } }
        try {
          // first handle legacy boolean status
          if (typeof parsed.status === 'boolean') {
            if (parsed.status === true) {
              showFormMessage('success', parsed.message || 'Login successful');
              // optional redirect
              if (parsed.redirect) {
                setTimeout(() => { window.location.href = parsed.redirect; }, 500);
              }
            } else {
              // server-side validation errors mapping
              if (parsed.errors && typeof parsed.errors === 'object') {
                Object.keys(parsed.errors).forEach(k => {
                  const fbId = fieldMap[k];
                  if (fbId) {
                    const el = document.getElementById(fbId);
                    if (el) el.textContent = parsed.errors[k];
                  }
                });
              }
              showFormMessage('error', parsed.message || 'Login failed');
            }
            return;
          }

          // handle API envelope { status: 'success'|'error', data: {...}, message? }
          if (typeof parsed.status === 'string') {
            if (parsed.status === 'success' || parsed.status === 'ok') {
              const data = parsed.data || parsed.data || {};
              showFormMessage('success', parsed.message || (data.message || 'Login successful'));
              // redirect via data.redirect or message
              const redirect = (parsed.data && parsed.data.redirect) || parsed.redirect;
              if (redirect) {
                setTimeout(() => { window.location.href = redirect; }, 400);
              }
            } else {
              // error envelope
              const errMsg = parsed.message || (parsed.data && parsed.data.message) || 'Login failed';
              // map field errors if present
              const errors = parsed.data && parsed.data.errors || parsed.errors || null;
              if (errors && typeof errors === 'object') {
                Object.keys(errors).forEach(k => {
                  const fbId = fieldMap[k];
                  if (fbId) {
                    const el = document.getElementById(fbId);
                    if (el) el.textContent = errors[k];
                  }
                });
              }
              showFormMessage('error', errMsg);
            }
            return;
          }

          // fallback: unknown response shape - show message if present
          if (parsed && parsed.message) {
            showFormMessage(parsed.status ? 'success' : 'error', parsed.message);
          }
        } catch (err) {
          console.error('response_parsed handler error', err);
        }
      });

      // Response status error (parsed but status false)
      fh.addEventListener('response_status_error', (res) => {
        // res is parsed server response when status false
        // fallback message
        if (res && res.message) {
          showFormMessage('error', res.message);
        } else {
          showFormMessage('error', 'Login failed');
        }
      });

      // Final completion (success or failure)
      fh.addEventListener('response_completed', (response) => {
        setSubmitting(false);
        inFlight = false;
      });

      // Network / fetch error
      fh.addEventListener('request_error', (err) => {
        console.error('Login request error', err);
        setSubmitting(false);
        inFlight = false;
        showFormMessage('error', 'Network error — please try again.');
      });

      // Also handle low-level 'error' event
      fh.addEventListener('error', (err) => {
        console.error('FormHandler error', err);
        setSubmitting(false);
        inFlight = false;
        showFormMessage('error', 'An unexpected error occurred.');
      });

      // optional: focus username on load
      document.addEventListener('DOMContentLoaded', function () {
        const u = document.getElementById('username');
        if (u && typeof u.focus === 'function') u.focus();
      });

    })();
  </script>
<?php }, 9); ?>
