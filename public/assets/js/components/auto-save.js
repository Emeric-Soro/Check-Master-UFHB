/* CheckMaster UFRMI - Auto-save (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function init() {
      if (initDone) return; initDone = true;
      document.querySelectorAll('form[data-autosave]').forEach(function (form) {
        var interval = parseInt(form.getAttribute('data-autosave-interval') || '30000', 10);
        var url = form.getAttribute('data-autosave-url');
        var timer;
        function save() {
          if (!url) return;
          var data = {};
          new FormData(form).forEach(function (v, k) { data[k] = v; });
          fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'same-origin'
          }).then(function (r) {
            if (r.ok && window.CM && window.CM.toast) window.CM.toast.show('Sauvegardé', 'success');
          }).catch(function () {
            if (window.CM && window.CM.toast) window.CM.toast.show('Échec de l’autosalve', 'error');
          });
          var status = form.querySelector('[data-autosave-status]');
          if (status) status.textContent = 'Dernière sauvegarde: ' + new Date().toLocaleTimeString();
        }
        function schedule() {
          clearTimeout(timer);
          timer = setTimeout(save, interval);
        }
        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);
        schedule();
      });
    }
    return { init: init };
  })();
  window.CM.autoSave = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.autoSave && typeof window.CM.autoSave.init === 'function') window.CM.autoSave.init();
});
