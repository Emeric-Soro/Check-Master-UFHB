/* CheckMaster UFRMI - Side Panel (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function init() {
      let panel, overlay, body, url;
      if (initDone) return; initDone = true;
      document.addEventListener('click', function (e) {
        // Open panel
        let openBtn = e.target.closest('[data-action="open-panel"]');
        if (openBtn) {
          url = openBtn.getAttribute('data-panel-url');
          var rowPayload = openBtn.getAttribute('data-row');
          panel = document.querySelector('.cm-side-panel');
          overlay = document.querySelector('.cm-side-panel-overlay');
          body = document.querySelector('.cm-side-panel-body');
          if (panel && body) body.innerHTML = '<div class="cm-skeleton">Chargement…</div>';
          if (panel) panel.classList.add('is-active');
          if (overlay) overlay.classList.add('is-active');
          if (url) {
            fetch(url, { credentials: 'same-origin' })
              .then(r => r.text())
              .then(html => { if (body) body.innerHTML = html; });
          } else if (rowPayload && body) {
            try {
              var parsed = JSON.parse(rowPayload);
              var html = '<dl class="cm-detail-card">';
              Object.keys(parsed).forEach(function (key) {
                var val = parsed[key];
                if (val === null || typeof val === 'object') return;
                html += '<dt>' + String(key) + '</dt><dd>' + String(val) + '</dd>';
              });
              html += '</dl>';
              body.innerHTML = html;
            } catch (err) {
              body.innerHTML = '<p>Aucun détail disponible.</p>';
            }
          }
          CM.events.emit('panel:opened');
          return;
        }
        // Close
        var closeBtn = e.target.closest('.cm-side-panel-close');
        if (closeBtn || e.target.closest('.cm-side-panel-overlay')) {
          panel = document.querySelector('.cm-side-panel');
          overlay = document.querySelector('.cm-side-panel-overlay');
          if (panel) panel.classList.remove('is-active');
          if (overlay) overlay.classList.remove('is-active');
          CM.events.emit('panel:closed');
        }
      });
      // Escape
      document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
          panel = document.querySelector('.cm-side-panel');
          overlay = document.querySelector('.cm-side-panel-overlay');
          if (panel) panel.classList.remove('is-active');
          if (overlay) overlay.classList.remove('is-active');
          CM.events.emit('panel:closed');
        }
      });
    }
    return { init: init };
  })();
  window.CM.sidePanel = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM.sidePanel && typeof window.CM.sidePanel.init === 'function') window.CM.sidePanel.init();
});
