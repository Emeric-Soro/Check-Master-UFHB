/* CheckMaster - App core */
(function (window, document) {
  'use strict';

  window.CM = window.CM || {};

  CM.utils = CM.utils || {
    debounce: function (fn, delay) {
      var timer;
      return function () {
        var args = arguments;
        var context = this;
        clearTimeout(timer);
        timer = setTimeout(function () {
          fn.apply(context, args);
        }, delay);
      };
    }
  };

  document.addEventListener('DOMContentLoaded', function () {
    var contentArea = document.getElementById('contentArea');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('cmSidebar');

    if (contentArea && document.querySelector('.cm-prd3-crud-screen')) {
      contentArea.classList.add('is-prd3-page');
    }

    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 768px)').matches) {
          sidebar.classList.toggle('is-open');
        }
      });

      document.addEventListener('click', function (event) {
        if (!window.matchMedia('(max-width: 768px)').matches) {
          return;
        }
        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
          sidebar.classList.remove('is-open');
        }
      });
    }

    if (window.CM.selectSearch && typeof window.CM.selectSearch.init === 'function') {
      window.CM.selectSearch.init();
    }
  });
})(window, document);
