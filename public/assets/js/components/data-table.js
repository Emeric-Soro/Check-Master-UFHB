/* CheckMaster UFRMI - Data Table (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;

    function init() {
      if (initDone) return; initDone = true;

      // Recherche en temps réel
      document.addEventListener('input', function (e) {
        var searchInput = e.target.closest('.toolbar-search input, .cm-toolbar-search');
        var term, container, table, rows, countBadge, visibleRows;
        if (!searchInput) return;

        term = searchInput.value.toLowerCase();
        container = searchInput.closest('.cm-crud-wrapper') || document;

        table = container.querySelector('.cm-table-wrapper table');
        if (!table) return;

        rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          var text = row.textContent.toLowerCase();
          row.style.display = text.indexOf(term) > -1 ? '' : 'none';
        });

        // Mise à jour du compteur si présent
        countBadge = container.querySelector('.results-count');
        if (countBadge) {
          visibleRows = Array.prototype.filter.call(rows, function (r) {
            return r.style.display !== 'none';
          }).length;
          countBadge.textContent = visibleRows + ' résultat(s)';
        }
      });

      // Tri par colonne
      document.addEventListener('click', function (e) {
        var header = e.target.closest('th.is-sortable, th.cm-sortable');
        if (!header) return;

        var col = header.dataset.column || '';
        if (!col) {
          var link = header.querySelector('a.cm-sort-link');
          if (link) {
            return;
          }
        }
        if (!col) return;

        var currentUrl = new URL(window.location.href);
        var currentSort = currentUrl.searchParams.get('sort');
        var currentDir = currentUrl.searchParams.get('dir') || 'asc';

        var newDir = 'asc';
        if (currentSort === col && currentDir === 'asc') {
          newDir = 'desc';
        }

        currentUrl.searchParams.set('sort', col);
        currentUrl.searchParams.set('dir', newDir);
        window.location.href = currentUrl.toString();
      });
    }

    return { init: init };
  })();
  window.CM.dataTable = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.dataTable && typeof window.CM.dataTable.init === 'function') window.CM.dataTable.init();
});
