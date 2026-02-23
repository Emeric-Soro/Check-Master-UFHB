/* CheckMaster UFRMI - Steps (IIFE) */
(function (window, document) {
  'use strict';
  window.CM = window.CM || {};
  var module = (function () {
    var initDone = false;
    function init() {
      if (initDone) return; initDone = true;
      document.addEventListener('click', function (e) {
        var stepEl = e.target.closest('.cm-steps [data-step-url]');
        if (!stepEl) return;
        var stepIndex = parseInt(stepEl.getAttribute('data-step-index') || stepEl.dataset.stepIndex || -1, 10);
        var stepUrl = stepEl.getAttribute('data-step-url') || stepEl.dataset.stepUrl;
        if (stepUrl) {
          window.location.href = stepUrl;
          CM.events.emit('steps:navigate', { stepIndex: stepIndex, stepUrl: stepUrl });
        }
      });
    }
    return { init: init };
  })();
  window.CM.steps = module;
})(window, document);

document.addEventListener('DOMContentLoaded', function () {
  if (window.CM && window.CM.steps && typeof window.CM.steps.init === 'function') window.CM.steps.init();
});
