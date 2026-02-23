/* CheckMaster UFRMI - Tabs (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};

  var module = (function () {
    var initDone = false;

    function activateLocalTab(container, trigger) {
      var href = trigger.getAttribute('href') || '';
      var tabId = trigger.getAttribute('data-tab-id') || '';
      var targetId = '';

      if (href.indexOf('#') === 0) {
        targetId = href.substring(1);
      } else if (tabId !== '') {
        var prefix = container.getAttribute('data-content-prefix') || 'tab';
        targetId = prefix + '-' + tabId;
      }
      if (targetId === '') {
        return;
      }

      container.querySelectorAll('li.is-active').forEach(function (li) {
        li.classList.remove('is-active');
      });
      var linkLi = trigger.closest('li');
      if (linkLi) {
        linkLi.classList.add('is-active');
      }

      container.querySelectorAll('.cm-tabs-accordion-header').forEach(function (header) {
        header.classList.remove('is-active');
      });
      var tabIdRef = trigger.getAttribute('data-tab-id');
      if (tabIdRef) {
        container.querySelectorAll('.cm-tabs-accordion-header[data-tab-id="' + tabIdRef + '"]').forEach(function (header) {
          header.classList.add('is-active');
        });
      }

      var region = container.closest('.cm-crud-wrapper') || document;
      region.querySelectorAll('.cm-tab-content').forEach(function (content) {
        content.classList.remove('is-active');
        content.style.display = 'none';
      });
      var contentEl = document.getElementById(targetId);
      if (contentEl) {
        contentEl.classList.add('is-active');
        contentEl.style.display = '';
      }

      if (window.CM && window.CM.events && typeof window.CM.events.emit === 'function') {
        window.CM.events.emit('tabs:changed', { tabId: targetId });
      }
      if (href.indexOf('#') === 0) {
        window.location.hash = targetId;
      }
    }

    function init() {
      if (initDone) return;
      initDone = true;

      document.querySelectorAll('.cm-tabs-container').forEach(function (container) {
        container.addEventListener('click', function (e) {
          var trigger = e.target.closest('a.cm-tab-link, a.cm-tabs-accordion-header');
          if (!trigger) {
            return;
          }

          var href = trigger.getAttribute('href') || '';
          var isLocal = trigger.getAttribute('data-tab-local') === '1' || href.indexOf('#') === 0;
          if (!isLocal) {
            return;
          }

          e.preventDefault();
          activateLocalTab(container, trigger);
        });

        var hash = window.location.hash;
        if (hash) {
          var hashLink = container.querySelector('a.cm-tab-link[href="' + hash + '"], a.cm-tabs-accordion-header[href="' + hash + '"]');
          if (hashLink) {
            activateLocalTab(container, hashLink);
            return;
          }
        }

        var active = container.querySelector('li.is-active a.cm-tab-link[data-tab-local="1"]');
        if (active) {
          activateLocalTab(container, active);
        }
      });
    }

    return { init: init };
  })();

  window.CM.tabs = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.tabs && typeof window.CM.tabs.init === 'function') {
    window.CM.tabs.init();
  }
});
