/* CheckMaster UFRMI - Select with search (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function init() {
      if (initDone) return; initDone = true;
      // Initalisation pour chaque wrapper cm-select-search
      document.addEventListener('click', function (e) {
        let wrapper = e.target.closest('.cm-select-search');
        if (wrapper && e.target.closest('.cm-select-search-toggle, .cm-select-search-input')) {
          let dropdown = wrapper.querySelector('.cm-select-search-dropdown');
          if (dropdown) dropdown.classList.toggle('is-active');
        } else if (!wrapper) {
          // Ferme les dropdowns ouverts si clic à l'extérieur
          document.querySelectorAll('.cm-select-search-dropdown.is-active').forEach(function (d) {
            d.classList.remove('is-active');
          });
        }
      });

      // Filtrage des options selon l'entrée
      document.addEventListener('input', function (e) {
        let inputWrapper = e.target.closest('.cm-select-search');
        if (!inputWrapper) return;
        let input = inputWrapper.querySelector('input');
        let dropdown = inputWrapper.querySelector('.cm-select-search-dropdown');
        if (!input || !dropdown) return;
        let q = (e.target.value || '').toLowerCase();
        dropdown.querySelectorAll('.cm-select-search-option').forEach(function (opt) {
          var text = opt.textContent.toLowerCase();
          opt.style.display = text.indexOf(q) > -1 ? '' : 'none';
        });

        // Appels API si nécessaire
        let apiUrl = inputWrapper.getAttribute('data-api-url');
        let minChars = parseInt(inputWrapper.getAttribute('data-min-chars') || '2', 10);
        if (apiUrl && q.length >= minChars) {
          if (inputWrapper._fetchTimer) window.clearTimeout(inputWrapper._fetchTimer);
          inputWrapper._fetchTimer = window.setTimeout(function () {
            fetch(apiUrl, { method: 'GET' })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                dropdown.innerHTML = '';
                (data || []).forEach(function (it) {
                  var div = document.createElement('div');
                  div.className = 'cm-select-search-option';
                  div.textContent = it.text || String(it.value || '');
                  div.dataset.value = it.value;
                  if (it.fill) div.dataset.fill = JSON.stringify(it.fill);
                  dropdown.appendChild(div);
                });
                dropdown.classList.add('is-active');
              });
          }, 300);
        }
      });

      // Sélection d'une option
      document.addEventListener('click', function (e) {
        let option = e.target.closest('.cm-select-search-option');
        if (!option) return;
        let wrapper = option.closest('.cm-select-search');
        if (!wrapper) return;
        let fill = option.dataset.fill;
        if (fill) {
          try {
            let obj = JSON.parse(fill);
            let targets = wrapper.getAttribute('data-autofill-targets');
            if (targets) {
              document.querySelectorAll(targets).forEach(function (el) {
                for (let k in obj) {
                  if (Object.prototype.hasOwnProperty.call(obj, k)) {
                    if (el.name === k) el.value = obj[k];
                    else {
                      let el2 = document.querySelector('[name="' + k + '"]');
                      if (el2) el2.value = obj[k];
                    }
                  }
                }
              });
            }
          } catch (err) { /* ignore */ }
        }
        var input = wrapper.querySelector('input');
        if (input) input.value = option.textContent;
        var dropdown = wrapper.querySelector('.cm-select-search-dropdown');
        if (dropdown) dropdown.classList.remove('is-active');
      });

      // Clavier (navigation rapide) – optionnel simple
      document.addEventListener('keydown', function (e) {
        var active = document.querySelector('.cm-select-search-dropdown.is-active');
        if (!active) return;
        var items = Array.from(active.querySelectorAll('.cm-select-search-option'));
        if (e.key === 'Escape') { active.classList.remove('is-active'); }
        // Améliorations futures: navigations avec flèches
      });
    }
    return { init: init };
  })();
  window.CM.selectSearch = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.selectSearch && typeof window.CM.selectSearch.init === 'function') window.CM.selectSearch.init();
});
