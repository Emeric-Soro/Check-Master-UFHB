<?php
/**
 * Toast notification component
 * - Renders the toast container
 * - Auto-displays PHP session flash messages (success, error, warning, info, message)
 * - Exposes cmToast(message, type, timeout) for dynamic toasts
 */

// Read and clear flash messages from session
$flashMessages = [];
$flashKeys = ['success', 'error', 'warning', 'info', 'message'];
foreach ($flashKeys as $key) {
    if (isset($_SESSION[$key]) && $_SESSION[$key] !== '' && $_SESSION[$key] !== []) {
        $raw = $_SESSION[$key];
        if (is_array($raw) && isset($raw['text'])) {
            // Format: ['text' => '...', 'type' => '...']
            $flashMessages[] = [
                'text' => (string) $raw['text'],
                'type' => (string) ($raw['type'] ?? ($key === 'message' ? 'info' : $key)),
            ];
        } elseif (is_string($raw) && trim($raw) !== '') {
            $flashMessages[] = [
                'text' => trim($raw),
                'type' => $key === 'message' ? 'info' : $key,
            ];
        }
        unset($_SESSION[$key]);
    }
}

$icons = [
    'success' => 'fa-circle-check',
    'error'   => 'fa-circle-xmark',
    'danger'  => 'fa-circle-xmark',
    'warning' => 'fa-triangle-exclamation',
    'info'    => 'fa-circle-info',
];
?>
<div class="cm-toast-container" id="cmToastContainer" aria-live="polite" aria-atomic="true"></div>
<script>
(function () {
    if (window.cmToast) {
        return;
    }

    var ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        danger: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    window.cmToast = function (message, type, timeout) {
        var container = document.getElementById('cmToastContainer');
        if (!container) {
            return;
        }
        var toast = document.createElement('div');
        toast.className = 'cm-toast is-' + (type || 'info');

        var icon = document.createElement('span');
        icon.className = 'cm-toast__icon fas ' + (ICONS[type] || ICONS.info);

        var msg = document.createElement('span');
        msg.className = 'cm-toast__message';
        msg.textContent = String(message || '');

        var closeBtn = document.createElement('button');
        closeBtn.className = 'cm-toast__close fas fa-xmark';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.addEventListener('click', function () {
            removeToast(toast);
        });

        toast.appendChild(icon);
        toast.appendChild(msg);
        toast.appendChild(closeBtn);
        container.appendChild(toast);

        var duration = Number(timeout || 5000);
        if (duration > 0) {
            setTimeout(function () {
                removeToast(toast);
            }, duration);
        }
    };

    function removeToast(el) {
        if (!el || !el.parentNode) return;
        el.classList.add('is-leaving');
        setTimeout(function () {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 250);
    }

    // Auto-display PHP flash messages injected below
    var flashMessages = <?= json_encode($flashMessages, JSON_UNESCAPED_UNICODE) ?>;
    if (flashMessages && flashMessages.length > 0) {
        for (var i = 0; i < flashMessages.length; i++) {
            var fm = flashMessages[i];
            cmToast(fm.text, fm.type);
        }
    }
})();
</script>
