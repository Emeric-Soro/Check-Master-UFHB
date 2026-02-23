/* CheckMaster UFRMI - Form Validation (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    function init() {
      document.querySelectorAll('form[data-validate]').forEach(function (form) {
        function showError(input, message) {
          input.classList.add('is-danger');
          let field = input.closest('.cm-field') || input.parentElement;
          if (field) field.classList.add('has-error');
          let error = input.parentElement.querySelector('.help.is-danger');
          if (!error) {
            error = document.createElement('div');
            error.className = 'help is-danger';
            input.parentElement.appendChild(error);
          }
          error.textContent = message;
        }
        function clearError(input) {
          input.classList.remove('is-danger');
          var field = input.closest('.cm-field') || input.parentElement;
          if (field) field.classList.remove('has-error', 'has-success');
          var error = input.parentElement.querySelector('.help.is-danger');
          if (error) error.textContent = '';
        }
        function validateInput(input) {
          clearError(input);
          var rules = (input.getAttribute('data-rules')||'').split('|').filter(Boolean);
          var value = (input.value || '').trim();
          var ok = true;
          for (let i=0; i<rules.length; i++) {
            let rule = rules[i];
            if (rule === 'required') { if (value === '') { showError(input, 'Ce champ est requis'); ok = false; break; } }
            else if (rule === 'email') { if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(value)) { showError(input, 'Adresse e-mail invalide'); ok = false; break; } }
            else if (rule.startsWith('min:')) { let min = parseInt(rule.split(':')[1], 10); if (value.length < min) { showError(input, 'Minimum '+min+' caractères'); ok = false; break; } }
            else if (rule.startsWith('max:')) { let max = parseInt(rule.split(':')[1], 10); if (value.length > max) { showError(input, 'Max '+max+' caractères'); ok = false; break; } }
            else if (rule.startsWith('pattern:')) { let pattern = rule.split(':')[1]; try { let re = new RegExp(pattern); if (!re.test(value)) { showError(input, 'Format invalide'); ok = false; } } catch (e) { /* ignore invalid pattern */ } if (!ok) break; }
          }
          if (ok && input.getAttribute('data-match')) {
            let other = document.querySelector('input[name="'+input.getAttribute('data-match')+'"]');
            if (other && other.value !== input.value) { showError(input, 'Non conforme'); ok = false; }
          }
          if (!ok) {
            input.closest('.cm-field') && input.closest('.cm-field').classList.add('has-error');
          } else {
            input.closest('.cm-field') && input.closest('.cm-field').classList.add('has-success');
          }
          return ok;
        }
        form.addEventListener('submit', function (ev) {
          let valid = true;
          Array.prototype.forEach.call(form.querySelectorAll('input, textarea, select'), function (inp) {
            if (inp.closest('[data-validate]')) {
              if (!validateInput(inp)) valid = false;
            }
          });
          if (!valid) {
            ev.preventDefault();
          }
        });
        form.addEventListener('blur', function (ev) {
          let t = ev.target;
          if (t && t.matches && (t.matches('input, textarea, select'))) {
            validateInput(t);
          }
        }, true);
      });
    }
    return { init: init };
  })();
  window.CM.formValidation = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.formValidation && typeof window.CM.formValidation.init === 'function') window.CM.formValidation.init();
});
