/**
 * CheckMaster UFRMI - Confirm Modal Component
 * Handles modal open/close interactions:
 * - Opens modal when an element with data-modal-target="<modal-id>" is clicked
 * - Closes modal on overlay, cancel, or X click
 * - Keyboard Escape support
 */
(function (window, document) {
  'use strict';

  window.CM = window.CM || {};

  CM.confirmModal = {
    init() {
      this._bindTriggers();
      this._bindCloseActions();
      this._bindKeyboard();
    },

    /* Open triggers: [data-modal-target] = id of the modal to show */
    _bindTriggers() {
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-modal-target]');
        if (!trigger) return;

        e.preventDefault();
        const modalId = trigger.dataset.modalTarget;
        const modal = document.getElementById(modalId);
        if (modal) {
          this.open(modal);

          // Dynamic URL: [data-confirm-url] on the trigger populates the action
          const dynamicUrl = trigger.dataset.confirmUrl;
          if (dynamicUrl) {
            const form = modal.querySelector('.cm-modal-form');
            if (form) form.action = dynamicUrl;
            const link = modal.querySelector('.cm-modal-confirm');
            if (link) link.href = dynamicUrl;
          }

          // Dynamic message: [data-confirm-message]
          const dynamicMsg = trigger.dataset.confirmMessage;
          if (dynamicMsg) {
            const msgEl = modal.querySelector('.cm-modal-message');
            if (msgEl) msgEl.textContent = dynamicMsg;
          }
        }
      });
    },

    /* Close actions: overlay, cancel, and close buttons */
    _bindCloseActions() {
      document.addEventListener('click', (e) => {
        const target = e.target;

        // Close on overlay click
        if (target.classList.contains('cm-modal-overlay')) {
          const modal = target.closest('.modal');
          if (modal) this.close(modal);
        }

        // Close on cancel button
        if (target.closest('.cm-modal-cancel')) {
          const modal = target.closest('.modal');
          if (modal) this.close(modal);
        }

        // Close on X button
        if (target.closest('.cm-modal-close')) {
          const modal = target.closest('.modal');
          if (modal) this.close(modal);
        }
      });
    },

    /* Keyboard: Escape to close */
    _bindKeyboard() {
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          const activeModal = document.querySelector('.modal.is-active');
          if (activeModal) this.close(activeModal);
        }
      });
    },

    /* Open modal */
    open(modal) {
      modal.classList.add('is-active');
      document.body.classList.add('cm-no-scroll');
      CM.events.emit('modal:opened', { id: modal.id });
    },

    /* Close modal */
    close(modal) {
      modal.classList.remove('is-active');
      document.body.classList.remove('cm-no-scroll');
      CM.events.emit('modal:closed', { id: modal.id });
    }
  };
})(window, document);
