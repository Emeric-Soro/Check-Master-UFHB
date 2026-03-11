/* CheckMaster UFRMI - Sidebar (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function closeAll() {
      var sb = document.querySelector('.cm-sidebar');
      var overlay = document.querySelector('.cm-drawer-overlay');
      if (sb) sb.classList.remove('is-active');
      if (overlay) overlay.classList.remove('is-active');
    }
    function init() {
      if (initDone) return; initDone = true;
      // Détection par délégation d'événements
      document.addEventListener('click', function (e) {
        var sb, overlay, section, parent;
        // Burger pour mobile
        if (e.target.closest('.cm-burger')) {
          e.preventDefault();
          sb = document.querySelector('.cm-sidebar');
          overlay = document.querySelector('.cm-drawer-overlay');
          if (sb) sb.classList.toggle('is-active');
          if (overlay) overlay.classList.toggle('is-active');
          return;
        }
        // Overlay pour fermer
        if (e.target.closest('.cm-drawer-overlay')) {
          closeAll();
        }
        // Sections: toggle collapse
        var sectionTitle = e.target.closest('.cm-menu-section-title');
        if (sectionTitle) {
          section = sectionTitle.parentElement;
          if (section) section.classList.toggle('is-collapsed');
        }
        // Sous-menus: bascule d'état sur le parent
        var parentLink = e.target.closest('.cm-menu-parent > a');
        if (parentLink) {
          parent = parentLink.parentElement;
          if (parent) parent.classList.toggle('is-collapsed');
        }
      });
      // Esc: fermer sur mobile
      document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' || ev.key === 'Esc') {
          if (window.innerWidth < 768) {
            closeAll();
          }
        }
      });
    }
    return { init: init };
  })();
  window.CM.sidebar = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.sidebar && typeof window.CM.sidebar.init === 'function') window.CM.sidebar.init();
});
