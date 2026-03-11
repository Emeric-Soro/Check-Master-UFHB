/* CheckMaster UFRMI - Confirm Modal (Promise-based) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var CM = window.CM;

  /**
   * SYSTÈME DE CONFIRMATION UNIQUE
   *
   * Remplace tous les window.confirm() et modals existants.
   *
   * Usage:
   *   const ok = await CM.confirm('Supprimer ?');
   *   const ok = await CM.confirm({ title: '...', message: '...', type: 'danger' });
   *
   * @param {string|object} options
   * @returns {Promise<boolean>}
   */
  CM.confirm = function (options) {
    var settings = typeof options === 'string'
      ? { message: options }
      : (options || {});

    var config = {
      title: settings.title || 'Confirmation',
      message: settings.message || 'Êtes-vous sûr de vouloir continuer ?',
      confirmText: settings.confirmText || 'Confirmer',
      cancelText: settings.cancelText || 'Annuler',
      type: settings.type || 'primary' // primary | danger | warning | info
    };

    return new Promise(function (resolve) {
      var modal = document.getElementById('cm-confirm-modal');
      var titleEl = document.getElementById('cm-confirm-title');
      var messageEl = document.getElementById('cm-confirm-message');
      var okBtn = document.getElementById('cm-confirm-ok');
      var cancelBtn = document.getElementById('cm-confirm-cancel');

      if (!modal || !okBtn || !cancelBtn) {
        // Fallback to native confirm if modal not in DOM
        resolve(window.confirm(config.message));
        return;
      }

      // Update content
      if (titleEl) titleEl.textContent = config.title;
      if (messageEl) messageEl.textContent = config.message;
      okBtn.textContent = config.confirmText;
      cancelBtn.textContent = config.cancelText;

      // Update button style based on type
      okBtn.className = 'cm-btn is-' + config.type;

      // Show modal
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      okBtn.focus();

      function cleanup() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        okBtn.removeEventListener('click', handleOk);
        cancelBtn.removeEventListener('click', handleCancel);
        document.removeEventListener('keydown', handleKeydown);
        modal.removeEventListener('click', handleOverlay);
      }

      function handleOk() {
        cleanup();
        resolve(true);
      }

      function handleCancel() {
        cleanup();
        resolve(false);
      }

      function handleKeydown(e) {
        if (e.key === 'Escape') {
          cleanup();
          resolve(false);
        }
      }

      function handleOverlay(e) {
        if (e.target === modal) {
          cleanup();
          resolve(false);
        }
      }

      okBtn.addEventListener('click', handleOk);
      cancelBtn.addEventListener('click', handleCancel);
      document.addEventListener('keydown', handleKeydown);
      modal.addEventListener('click', handleOverlay);
    });
  };
})(window, document);
