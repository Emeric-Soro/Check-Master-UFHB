/* CheckMaster UFRMI - Toasts (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function ensureContainer() {
      var cont = document.querySelector('.cm-toast-container');
      if (!cont) {
        cont = document.createElement('div');
        cont.className = 'cm-toast-container';
        document.body.appendChild(cont);
      }
      return cont;
    }
    function show(message, type, duration) {
      duration = duration || 3000;
      var container = ensureContainer();
      var toast = document.createElement('div');
      toast.className = 'cm-toast cm-toast-' + (type || 'info');
      toast.innerHTML = '<span class="cm-toast-message">'+(message||'')+'</span><button class="cm-toast-close">×</button>';
      container.appendChild(toast);
      function dismiss(){
        toast.classList.add('cm-toast-slide-out');
        setTimeout(function(){ toast.remove(); }, 300);
      }
      // auto dismiss
      var t = setTimeout(dismiss, duration);
      toast.addEventListener('mouseenter', function(){ clearTimeout(t); });
      toast.addEventListener('mouseleave', function(){ t = setTimeout(dismiss, duration); });
      toast.querySelector('.cm-toast-close').addEventListener('click', dismiss);
    }
    return { init: function(){ initDone = true; } , show: show };
  })();
  window.CM.toast = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.toast) window.CM.toast.init();
});
