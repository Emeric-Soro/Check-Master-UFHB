/* CheckMaster UFRMI - App core (ES6+) 
   - Global init and utilities
   - CSRF token handling for fetch
   - Global event bus (CM.events)
   - Namespace: window.CM
   - Code entièrement en Français
*/
(function (window, document) {
  'use strict';

  // Namespace globale
  window.CM = window.CM || {};

  // Utilitaires globaux
  CM.utils = {
    // Débounce simple
    debounce(fn, delay) {
      let timer;
      return function (...args) {
        const context = this;
        clearTimeout(timer);
        timer = setTimeout(() => {
          fn.apply(context, args);
        }, delay);
      };
    },
    // Échappement HTML basique
    escapeHtml(str) {
      if (str == null) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },
    // Formatage nombre
    formatNumber(n) {
      if (n == null || Number.isNaN(Number(n))) return n;
      try {
        return Number(n).toLocaleString(undefined);
      } catch (e) {
        return n;
      }
    },
    // Requête JSON avec injection CSRF
    async fetchJson(url, options = {}) {
      const headers = Object.assign({}, options.headers || {});
      let token = '';

      const m = document.querySelector('meta[name="csrf-token"]');
      if (m) token = m.getAttribute('content');

      if (!token) {
        const i = document.querySelector('input[name="_csrf_token"]');
        if (i) token = i.value;
      }

      if (token) headers['X-CSRF-Token'] = token;

      if (options.body && typeof options.body !== 'string') {
        options.body = JSON.stringify(options.body);
      }

      const cfg = {
        method: 'GET',
        headers: headers,
        credentials: 'same-origin',
        ...options
      };

      const resp = await fetch(url, cfg);
      if (!resp.ok) {
        let text = '';
        try { text = await resp.text(); } catch (e) { text = ''; }
        throw new Error(`Échec de la requête: ${resp.status} ${text}`);
      }

      try {
        const contentType = resp.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return await resp.json();
        }
        return await resp.text();
      } catch (e) {
        return await resp.text();
      }
    }
  };

  // Bus d'événements simple
  CM.events = CM.events || (() => {
    const _listeners = {};
    return {
      on(name, fn) {
        if (typeof fn !== 'function') return;
        _listeners[name] = _listeners[name] || [];
        _listeners[name].push(fn);
      },
      emit(name, data) {
        const fns = _listeners[name] || [];
        fns.forEach(fn => {
          try {
            fn(data);
          } catch (err) {
            console.error(`Erreur dans l'écouteur d'événement ${name}:`, err);
          }
        });
      }
    };
  })();

  // Initialisation globale
  document.addEventListener('DOMContentLoaded', () => {
    const components = [
      'sidebar', 'dataTable', 'selectSearch', 'tabs',
      'steps', 'autoSave', 'toast', 'formValidation',
      'confirmModal'
    ];

    components.forEach(comp => {
      if (window.CM[comp] && typeof window.CM[comp].init === 'function') {
        window.CM[comp].init();
      }
    });
  });
})(window, document);
