<?php
// content-only forgot password view for Yantra
// Variables expected in scope (optional):
//   $page             -> WebPage object
//   $error_message    -> string (if validation/auth failed server-side fallback)
//   $success_message  -> string (if email sent / success notice server-side fallback)
//   $csrf_token       -> string CSRF token (recommended to pass from controller)
// If $csrf_token is not present, the JS still works if token is present in rendered form input.
if (!defined('YANTRA')) exit;
?>
<section class="auth-page">
  <div class="auth-card">
    <header class="auth-header">
      <h1><?php echo htmlspecialchars($page->getMeta('title', 'Forgot Password'), ENT_QUOTES); ?></h1>
      <p class="auth-subtitle">Enter your account email and we’ll send reset instructions.</p>
    </header>

    <!-- Server-side fallback messages (kept for non-AJAX fallback if controller renders page) -->
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success" role="status" aria-live="polite">
        <?php echo htmlspecialchars($success_message, ENT_QUOTES); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-error" role="alert">
        <?php echo htmlspecialchars($error_message, ENT_QUOTES); ?>
      </div>
    <?php endif; ?>

    <form id="forgot-password-form" method="post" action="<?php echo site_url('admin/forgot'); ?>" class="auth-form" autocomplete="off" novalidate>
      <!-- CSRF token (also sent as X-CSRF-Token header by JS) -->
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES); ?>">

      <div class="form-row mb-3">
        <label for="forgot-email">Email address</label>
        <input id="forgot-email" name="email" type="email" required
               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
               placeholder="you@example.com" autocomplete="email" class="form-control" aria-describedby="email-error">
        <div id="email-error" class="field-error small text-danger mt-1" aria-live="polite"></div>
      </div>

      <div class="form-row mb-3 text-center">
        <button type="submit" class="btn btn-primary">Send reset link</button>
      </div>

      <div class="form-row" style="margin-top:8px;">
        <a href="<?php echo site_url('admin/login'); ?>">← Back to sign in</a>
      </div>

      <!-- General message container for success/error not tied to a single field -->
      <div class="message-container text-center mt-3" role="status" aria-live="polite"></div>
    </form>

    <footer class="auth-footer">
      <small>© <?php echo date('Y'); ?> Admin</small>
    </footer>
  </div>
</section>

<?php add_action('footer_scripts', function () { ?>
  <script type="text/javascript">
    (function () {
      // Helper to read token from form
      function getFormCsrfToken(form) {
        const t = form.querySelector('input[name="csrf_token"]');
        return t ? t.value : null;
      }

      // Temporary storage for CSRF token during a submit (cleared quickly)
      let __YANTRA_TEMP_CSRF = null;
      let __YANTRA_TEMP_CLEAR = null;

      // Patch fetch to add X-CSRF-Token header while temp token is set
      const _origFetch = window.fetch;
      window.fetch = function (input, init) {
        try {
          if (__YANTRA_TEMP_CSRF) {
            init = init || {};
            init.headers = init.headers || {};
            if (typeof Headers !== 'undefined' && init.headers instanceof Headers) {
              init.headers.set('X-CSRF-Token', __YANTRA_TEMP_CSRF);
            } else if (typeof init.headers === 'object') {
              init.headers['X-CSRF-Token'] = __YANTRA_TEMP_CSRF;
            }
          }
        } catch (e) {
          // fail silently and call original fetch
        }
        return _origFetch.call(this, input, init);
      };

      // Patch XHR send to attach header if temp token present
      (function () {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method, url) {
          this.__yantra_xhr_url = url;
          return origOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function (body) {
          if (__YANTRA_TEMP_CSRF && typeof this.setRequestHeader === 'function') {
            try { this.setRequestHeader('X-CSRF-Token', __YANTRA_TEMP_CSRF); } catch (e) {}
          }
          return origSend.apply(this, arguments);
        };
      })();

      // When the form is submitted, set the temp token briefly so FormHandler's XHR/fetch includes it
      document.addEventListener('submit', function (ev) {
        const form = ev.target;
        if (!(form && form.id === 'forgot-password-form')) return;

        const token = getFormCsrfToken(form);
        if (!token) return;

        __YANTRA_TEMP_CSRF = token;
        if (__YANTRA_TEMP_CLEAR) {
          clearTimeout(__YANTRA_TEMP_CLEAR);
        }
        __YANTRA_TEMP_CLEAR = setTimeout(function () {
          __YANTRA_TEMP_CSRF = null;
          __YANTRA_TEMP_CLEAR = null;
        }, 5000);
      }, true);

      // Instantiate yantra.FormHandler and handle JSON-only responses
      document.addEventListener("DOMContentLoaded", function () {
        if (typeof yantra === 'undefined' || typeof yantra.FormHandler !== 'function') {
          console.warn('yantra.FormHandler not found — forgot-password form may submit as normal POST.');
          return;
        }

        new yantra.FormHandler('#forgot-password-form', false, {
          onSuccess: function (response, handler, formElem) {
            let result = {};
            try { result = JSON.parse(response.content); } catch (e) {
              console.error('Invalid JSON from server:', response.content);
              formElem.querySelector('.message-container').innerHTML = '<p class="text-danger">Invalid server response.</p>';
              return true;
            }

            // Clear messages and field errors
            const msgEl = formElem.querySelector('.message-container');
            msgEl.innerHTML = '';
            formElem.querySelectorAll('.field-error').forEach(function (el) { el.textContent = ''; });

            if (result.status) {
              // success - show message
              msgEl.innerHTML = `<p class="text-success">${result.message || 'If an account exists, a reset link has been sent.'}</p>`;
              // update CSRF token if server rotated it
              if (result.csrf) {
                const t = formElem.querySelector('input[name="csrf_token"]');
                if (t) t.value = result.csrf;
              }
            } else {
              // failure
              msgEl.innerHTML = `<p class="text-danger">${result.message || 'Request failed.'}</p>`;
              if (result.errors && typeof result.errors === 'object') {
                Object.keys(result.errors).forEach(function (k) {
                  const el = formElem.querySelector(`[name="${k}"]`);
                  if (el) {
                    const err = el.closest('.form-row, .mb-3').querySelector('.field-error');
                    if (err) err.textContent = result.errors[k] || '';
                  }
                });
              }
            }
            return true;
          },
          onFormError: function (response, handler, formElem) {
            formElem.querySelector('.message-container').innerHTML = '<p class="text-danger">Error submitting form.</p>';
            return true;
          },
          onError: function (err) {
            console.error('Forgot password error:', err);
            const form = document.getElementById('forgot-password-form');
            if (form) form.querySelector('.message-container').innerHTML = '<p class="text-danger">Network or server error. Please try again.</p>';
          }
        });
      });
    })();
  </script>
<?php }, 9); ?>