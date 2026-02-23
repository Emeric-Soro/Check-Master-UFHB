/* CheckMaster UFRMI - Confirm Modal (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    let initDone = false;
    function init() {
      if (initDone) return; initDone = true;
      document.addEventListener('click', function (e) {
        let btn = e.target.closest('[data-action="confirm"]');
        if (!btn) return;
        let title = btn.getAttribute('data-confirm-title') || '';
        let message = btn.getAttribute('data-confirm-message') || '';
        let url = btn.getAttribute('data-confirm-url');
        let method = (btn.getAttribute('data-confirm-method') || 'POST').toUpperCase();
        // Inject dans modal
        let modal = document.querySelector('.cm-modal');
        if (modal) {
          let titleEl = modal.querySelector('.cm-modal-title');
          let bodyEl = modal.querySelector('.cm-modal-body');
          if (titleEl) titleEl.textContent = title;
          if (bodyEl) bodyEl.textContent = message;
          modal.setAttribute('data-confirm-url', url || '');
          modal.setAttribute('data-confirm-method', method);
          modal.classList.add('is-active');
          let overlay = document.querySelector('.cm-modal-overlay');
          if (overlay) overlay.classList.add('is-active');
        }
      });
      // Close
      document.addEventListener('click', function (e) {
        let close = e.target.closest('.cm-modal-close');
        let overlay = e.target.closest('.cm-modal-overlay');
        if (close || overlay) {
          let modal = document.querySelector('.cm-modal');
          if (modal) modal.classList.remove('is-active');
          if (overlay) overlay.classList.remove('is-active');
        }
      });
      // Confirmer
      document.addEventListener('click', function (e) {
        let confirmBtn = e.target.closest('.cm-modal-confirm');
        if (!confirmBtn) return;
        let modal = document.querySelector('.cm-modal');
        if (!modal) return;
        let url = modal.getAttribute('data-confirm-url');
        let method = (modal.getAttribute('data-confirm-method') || 'POST').toUpperCase();
        if (!url) return;
        // CSRF token
        let token = '';
        let m = document.querySelector('meta[name="csrf-token"]');
        if (m) token = m.getAttribute('content');
        if (token) {
          // Requête fetch
          fetch(url, {
            method: method,
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': token
            },
            credentials: 'same-origin'
          }).then(function (r) {
            if (r.ok) {
              window.location.reload();
            } else {
              r.text().then(function (t) {
                if (window.CM && window.CM.toast) window.CM.toast.show('Échec de l’action: ' + t, 'error');
              });
            }
          }).catch(function () {
            if (window.CM && window.CM.toast) window.CM.toast.show('Erreur réseau', 'error');
          });
        }
      });
    }
    return { init: init };
  })();
  window.CM.confirmModal = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM.confirmModal && typeof window.CM.confirmModal.init === 'function') window.CM.confirmModal.init();
});
