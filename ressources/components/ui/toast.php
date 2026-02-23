<?php
?>
<div class="cm-toast-container" id="cmToastContainer" aria-live="polite" aria-atomic="true"></div>
<script>
(function () {
    if (window.cmToast) {
        return;
    }

    window.cmToast = function (message, type, timeout) {
        const container = document.getElementById('cmToastContainer');
        if (!container) {
            return;
        }
        const toast = document.createElement('div');
        toast.className = 'cm-toast is-' + (type || 'info');
        toast.textContent = String(message || '');
        container.appendChild(toast);
        setTimeout(function () {
            toast.classList.add('is-leaving');
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 180);
        }, Number(timeout || 3000));
    };
})();
</script>
