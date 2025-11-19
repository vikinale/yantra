<?php
// content-only reset password view for Yantra
// Expected variables (passed from controller):
//   $page          -> WebPage object
//   $csrf_token    -> CSRF token string
// Optionally server may pass $error_message / $success_message for non-AJAX fallback.
if (!defined('YANTRA')) exit;
?>
<section class="auth-page">
  <div class="auth-card">
    <header class="auth-header">
      <h1><?php echo htmlspecialchars($page->getMeta('title', 'Reset Password'), ENT_QUOTES); ?></h1>
      <p class="auth-subtitle">Set a new password for your account below.</p>
    </header>

    <!-- server-side fallback messages (kept for non-AJAX fallback) -->
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

    <form id="reset-password-form" method="post" action="<?php echo site_url('admin/reset'); ?>" class="auth-form" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES); ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ($_POST['token'] ?? ''), ENT_QUOTES); ?>">

      <div class="form-row mb-3">
        <label for="password">New password</label>
        <input id="password" name="password" type="password" required minlength="8" placeholder="Enter new password"
               class="form-control" aria-describedby="password-error" autocomplete="new-password">
        <div id="password-error" class="field-error small text-danger mt-1" aria-live="polite"></div>
      </div>

      <div class="form-row mb-3">
        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" required placeholder="Re-enter new password"
               class="form-control" aria-describedby="confirm-password-error" autocomplete="new-password">
        <div id="confirm-password-error" class="field-error small text-danger mt-1" aria-live="polite"></div>
      </div>

      <div class="form-row mb-3 text-center">
        <button type="submit" class="btn btn-primary">Reset password</button>
      </div>

      <div class="form-row" style="margin-top:8px;">
        <a href="<?php echo site_url('admin/login'); ?>">← Back to sign in</a>
      </div>

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
  // Attach short-lived CSRF header for AJAX requests (same pattern as forgot view)
  function getFormCsrfToken(form) {
    const t = form.querySelector('input[name="csrf_token"]');
    return t ? t.value : null;
  }

  let __YANTRA_TEMP_CSRF = null;
  let __YANTRA_TEMP_CLEAR = null;

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
    } catch (e) {}
    return _origFetch.call(this, input, init);
  };

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

  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    if (!(form && form.id === 'reset-password-form')) return;
    const token = getFormCsrfToken(form);
    if (!token) return;
    __YANTRA_TEMP_CSRF = token;
    if (__YANTRA_TEMP_CLEAR) clearTimeout(__YANTRA_TEMP_CLEAR);
    __YANTRA_TEMP_CLEAR = setTimeout(function () { __YANTRA_TEMP_CSRF = null; __YANTRA_TEMP_CLEAR = null; }, 5000);
  }, true);

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof yantra === 'undefined' || typeof yantra.FormHandler !== 'function') {
      console.warn('yantra.FormHandler not found — reset form may submit as normal POST.');
      return;
    }

    new yantra.FormHandler('#reset-password-form', false, {
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
        formElem.querySelectorAll('.field-error').forEach(el => el.textContent = '');

        if (result.status) {
          msgEl.innerHTML = `<p class="text-success">${result.message || 'Password reset successful. You may now sign in.'}</p>`;
          if (result.csrf) {
            const t = formElem.querySelector('input[name="csrf_token"]');
            if (t) t.value = result.csrf;
          }
          // optionally redirect
          if (result.redirect) {
            window.location.href = result.redirect;
            return true;
          }
        } else {
          msgEl.innerHTML = `<p class="text-danger">${result.message || 'Reset failed.'}</p>`;
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
        console.error('Reset error:', err);
        const form = document.getElementById('reset-password-form');
        if (form) form.querySelector('.message-container').innerHTML = '<p class="text-danger">Network or server error. Please try again.</p>';
      }
    });
  });
})();
</script>
<?php }, 9); ?>