/* CheckMaster - App core */
(function (window, document) {
  'use strict';

  window.CM = window.CM || {};
  var CM = window.CM;

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

  CM.ajax = CM.ajax || (function () {
    var initialized = false;
    var activeRequestController = null;

    function canHandleAjax() {
      return !!(window.fetch && window.DOMParser && document.getElementById('cmLayoutMain'));
    }

    function shouldIgnoreClick(event, link) {
      if (event.defaultPrevented || event.button !== 0) {
        return true;
      }
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return true;
      }
      if (!link || !link.href) {
        return true;
      }
      if (link.target && link.target !== '_self') {
        return true;
      }
      if (link.hasAttribute('download')) {
        return true;
      }
      if ((link.getAttribute('href') || '').indexOf('#') === 0) {
        return true;
      }
      return false;
    }

    function isAjaxEligibleLink(link) {
      var href, parsedUrl, action;

      if (!link || link.getAttribute('data-cm-ajax-link') === 'false') {
        return false;
      }

      href = link.getAttribute('href') || '';
      if (!href || /^javascript:/i.test(href) || /^mailto:/i.test(href) || /^tel:/i.test(href)) {
        return false;
      }

      try {
        parsedUrl = new window.URL(link.href, window.location.href);
      } catch (error) {
        return false;
      }

      if (parsedUrl.origin !== window.location.origin) {
        return false;
      }

      if (link.hasAttribute('download')) {
        return false;
      }

      action = (parsedUrl.searchParams.get('action') || '').toLowerCase();
      if (action.indexOf('imprimer') !== -1 || action.indexOf('export') !== -1 || action.indexOf('download') !== -1) {
        return false;
      }
      if (action === 'export_pdf' || action === 'imprimer_pv') {
        return false;
      }

      if (/\.(pdf|csv|xlsx?|zip)$/i.test(parsedUrl.pathname)) {
        return false;
      }

      return true;
    }

    function executeInlineScripts(container) {
      if (!container) {
        return;
      }
      var scripts = container.querySelectorAll('script');
      scripts.forEach(function (oldScript) {
        var newScript = document.createElement('script');
        Array.prototype.forEach.call(oldScript.attributes, function (attr) {
          newScript.setAttribute(attr.name, attr.value);
        });

        if (oldScript.src) {
          newScript.src = oldScript.src;
          newScript.async = false;
        } else {
          newScript.textContent = oldScript.textContent;
        }

        oldScript.parentNode.replaceChild(newScript, oldScript);
      });
    }

    function updateSidebarActive(url) {
      try {
        var parsedUrl = new window.URL(url, window.location.href);
        var page = parsedUrl.searchParams.get('page') || '';
        var action = parsedUrl.searchParams.get('action') || '';

        // Remove all active classes first
        var allLinks = document.querySelectorAll(
          '.cm-sidebar__menu-link.is-active-blue, .cm-sidebar__menu-link.is-active'
        );
        allLinks.forEach(function (el) {
          el.classList.remove('is-active-blue', 'is-active');
        });

        if (!page) return;

        // Find the matching link
        var links = document.querySelectorAll('.cm-sidebar__menu-link');
        var matched = null;
        links.forEach(function (link) {
          var href = link.getAttribute('href') || '';
          try {
            var linkUrl = new window.URL(href, window.location.href);
            var linkPage = linkUrl.searchParams.get('page') || '';
            var linkAction = linkUrl.searchParams.get('action') || '';
            if (linkPage === page) {
              // Prefer exact action match; fallback to page-only match
              if (action && linkAction) {
                if (linkAction === action && !matched) matched = link;
              } else if (!action && !linkAction && !matched) {
                matched = link;
              } else if (!matched) {
                matched = link; // broad fallback
              }
            }
          } catch (e) { /* skip */ }
        });

        if (!matched) return;

        matched.classList.add('is-active-blue');

        // Reveal parent category if collapsed
        var catItems = matched.closest('[id^="collapse-"]');
        if (catItems) {
          catItems.style.display = '';
          var catIcon = document.getElementById('icon-' + catItems.id);
          if (catIcon) catIcon.classList.add('is-open');
        }

        // Reveal parent sub-menu if inside one
        var subItems = matched.closest('.cm-sidebar__sub-items');
        if (subItems && subItems.id) {
          subItems.style.display = '';
          var subIcon = document.getElementById('icon-' + subItems.id);
          if (subIcon) subIcon.classList.add('is-open');
          // Also activate the parent button
          var parentBtn = document.querySelector(
            '[aria-controls="' + subItems.id + '"]'
          );
          if (parentBtn) parentBtn.classList.add('is-active-blue');
        }
      } catch (e) { /* Silently ignore */ }
    }

    function afterSwap(url) {
      // Update sidebar active state on every AJAX navigation
      updateSidebarActive(url);

      if (window.CM && window.CM.selectSearch && typeof window.CM.selectSearch.init === 'function') {
        window.CM.selectSearch.init();
      }

      if (window.CM && window.CM.dataTable && typeof window.CM.dataTable.init === 'function') {
        window.CM.dataTable.init();
      }

      try {
        document.dispatchEvent(new CustomEvent('cm:ajax:navigation:done', { detail: { url: url } }));
      } catch (error) {
        // Ignore CustomEvent issues on legacy browsers.
      }
    }

    function swapMainContentFromHtml(html, url) {
      var parser = new window.DOMParser();
      var incomingDocument = parser.parseFromString(html, 'text/html');
      var incomingMain = incomingDocument.getElementById('cmLayoutMain');
      var currentMain = document.getElementById('cmLayoutMain');

      if (!incomingMain || !currentMain) {
        return false;
      }

      currentMain.className = incomingMain.className;
      var incomingPage = incomingMain.getAttribute('data-page');
      if (incomingPage !== null) {
        currentMain.setAttribute('data-page', incomingPage);
      } else {
        currentMain.removeAttribute('data-page');
      }

      currentMain.innerHTML = incomingMain.innerHTML;

      var incomingTitle = incomingDocument.querySelector('title');
      if (incomingTitle && incomingTitle.textContent) {
        document.title = incomingTitle.textContent;
      }

      // Update navbar title from incoming page
      var incomingNavbarTitle = incomingDocument.querySelector('.cm-navbar__app-name');
      var currentNavbarTitle = document.querySelector('.cm-navbar__app-name');
      if (incomingNavbarTitle && currentNavbarTitle) {
        currentNavbarTitle.textContent = incomingNavbarTitle.textContent;
      }

      executeInlineScripts(currentMain);
      afterSwap(url);
      return true;
    }

    function load(url, options) {
      var loadOptions = options || {};
      var currentMain = document.getElementById('cmLayoutMain');

      if (!url || !canHandleAjax() || !currentMain) {
        window.location.href = url;
        return Promise.resolve(false);
      }

      if (activeRequestController) {
        activeRequestController.abort();
      }
      activeRequestController = new window.AbortController();

      currentMain.setAttribute('aria-busy', 'true');
      currentMain.classList.add('cm-is-loading');

      return window.fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html, */*;q=0.8'
        },
        signal: activeRequestController.signal
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('HTTP ' + response.status);
          }
          return response.text();
        })
        .then(function (html) {
          var swapped = swapMainContentFromHtml(html, url);
          if (!swapped) {
            window.location.href = url;
            return false;
          }

          if (!loadOptions.skipHistory) {
            var state = { cmAjax: true, url: url };
            if (loadOptions.replaceHistory) {
              window.history.replaceState(state, '', url);
            } else {
              window.history.pushState(state, '', url);
            }
          }
          return true;
        })
        .catch(function (error) {
          if (error && error.name === 'AbortError') {
            return false;
          }
          window.location.href = url;
          return false;
        })
        .finally(function () {
          activeRequestController = null;
          currentMain.removeAttribute('aria-busy');
          currentMain.classList.remove('cm-is-loading');
        });
    }

    function navigateWithParams(changes, options) {
      var url = new URL(window.location.href);
      var opts = options || {};
      var key;

      if (changes && typeof changes === 'object') {
        for (key in changes) {
          if (!Object.prototype.hasOwnProperty.call(changes, key)) {
            continue;
          }
          var value = changes[key];
          if (value === null || value === undefined || value === '') {
            url.searchParams.delete(key);
          } else {
            url.searchParams.set(key, String(value));
          }
        }
      }

      if (Array.isArray(opts.removeParams)) {
        opts.removeParams.forEach(function (paramName) {
          url.searchParams.delete(paramName);
        });
      }

      return load(url.toString(), {
        replaceHistory: !!opts.replaceHistory
      });
    }

    function bindAjaxLinks() {
      document.addEventListener('click', function (event) {
        var link = event.target.closest('a');
        if (!link || shouldIgnoreClick(event, link) || !isAjaxEligibleLink(link)) {
          return;
        }

        event.preventDefault();
        load(link.href);
      });
    }

    function bindParamControls() {
      document.addEventListener('change', function (event) {
        var control = event.target.closest('[data-cm-ajax-param]');
        if (!control) {
          return;
        }

        var paramName = control.getAttribute('data-cm-ajax-param');
        if (!paramName) {
          return;
        }

        var changes = {};
        changes[paramName] = control.value;

        var resetParam = control.getAttribute('data-cm-ajax-reset-param');
        if (resetParam) {
          changes[resetParam] = control.getAttribute('data-cm-ajax-reset-value') || '1';
        }

        navigateWithParams(changes);
      });
    }

    function bindHistoryNavigation() {
      window.addEventListener('popstate', function () {
        if (!canHandleAjax()) {
          return;
        }
        load(window.location.href, { replaceHistory: true, skipHistory: true });
      });
    }

    function init() {
      if (initialized || !canHandleAjax()) {
        return;
      }
      initialized = true;
      bindAjaxLinks();
      bindParamControls();
      bindHistoryNavigation();
    }

    return {
      init: init,
      load: load,
      navigateWithParams: navigateWithParams
    };
  })();

  CM.formsAjax = CM.formsAjax || (function () {
    var initialized = false;

    function isAjaxForm(form) {
      return !!form && form.getAttribute('data-cm-ajax-form') === 'true';
    }

    function buildGetUrl(action, formData) {
      var url;
      var params;

      try {
        url = new window.URL(action || window.location.href, window.location.href);
      } catch (error) {
        return action || window.location.href;
      }

      params = new window.URLSearchParams(url.search);
      formData.forEach(function (value, key) {
        if (params.has(key)) {
          params.delete(key);
        }
      });
      formData.forEach(function (value, key) {
        if (value === null || value === undefined || value === '') {
          return;
        }
        params.append(key, value);
      });

      url.search = params.toString();
      return url.toString();
    }

    function submitWithAjax(event) {
      var form = event.target;
      var method, action, formData, submitter;

      if (!isAjaxForm(form)) {
        return;
      }

      if (!(window.fetch && window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function')) {
        return;
      }

      event.preventDefault();

      if (form.getAttribute('data-cm-ajax-submitting') === '1') {
        return;
      }

      method = (form.getAttribute('method') || 'POST').toUpperCase();
      action = form.getAttribute('action') || window.location.href;

      if (method !== 'POST') {
        submitter = event.submitter || document.activeElement;
        formData = new window.FormData(form);
        if (submitter && submitter.name && !formData.has(submitter.name)) {
          formData.append(submitter.name, submitter.value || '');
        }
        window.CM.ajax.load(buildGetUrl(action, formData));
        return;
      }

      formData = new window.FormData(form);
      submitter = event.submitter || document.activeElement;
      if (submitter && submitter.name && !formData.has(submitter.name)) {
        formData.append(submitter.name, submitter.value || '');
      }

      form.setAttribute('data-cm-ajax-submitting', '1');
      form.classList.add('cm-is-loading');

      window.fetch(action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html, application/json;q=0.9, */*;q=0.8'
        }
      })
        .then(function (response) {
          var contentType = (response.headers.get('content-type') || '').toLowerCase();
          if (contentType.indexOf('application/json') !== -1) {
            return response.json().then(function (payload) {
              return {
                kind: 'json',
                payload: payload,
                url: response.url,
                ok: response.ok
              };
            });
          }

          return response.text().then(function () {
            return {
              kind: 'html',
              url: response.url,
              ok: response.ok
            };
          });
        })
        .then(function (result) {
          if (result.kind === 'json') {
            if (result.payload && result.payload.redirect) {
              return window.CM.ajax.load(result.payload.redirect, { replaceHistory: false });
            }

            if (result.payload && result.payload.success === false) {
              try {
                document.dispatchEvent(new CustomEvent('cm:ajax:form:error', {
                  detail: { form: form, payload: result.payload }
                }));
              } catch (error) {
                // Ignore CustomEvent issues on legacy browsers.
              }
              return false;
            }

            return window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
          }

          return window.CM.ajax.load(result.url || window.location.href, { replaceHistory: true });
        })
        .catch(function () {
          window.location.href = action;
        })
        .finally(function () {
          form.removeAttribute('data-cm-ajax-submitting');
          form.classList.remove('cm-is-loading');
        });
    }

    function init() {
      if (initialized) {
        return;
      }
      initialized = true;
      document.addEventListener('submit', submitWithAjax, true);
    }

    return {
      init: init
    };
  })();

  document.addEventListener('DOMContentLoaded', function () {
    var contentArea = document.getElementById('contentArea');

    if (contentArea && document.querySelector('.cm-prd3-crud-screen')) {
      contentArea.classList.add('is-prd3-page');
    }

    if (window.CM.selectSearch && typeof window.CM.selectSearch.init === 'function') {
      window.CM.selectSearch.init();
    }

    if (window.CM.ajax && typeof window.CM.ajax.init === 'function') {
      window.CM.ajax.init();
    }

    if (window.CM.formsAjax && typeof window.CM.formsAjax.init === 'function') {
      window.CM.formsAjax.init();
    }
  });
})(window, document);
