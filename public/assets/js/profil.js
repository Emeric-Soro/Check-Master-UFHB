/**
 * Profil Utilisateur JS
 */

document.addEventListener('DOMContentLoaded', function () {
    // Auto-fermeture des alertes
    const alerts = document.querySelectorAll('.notification');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Gestion des Onglets (Tabs)
    const tabs = document.querySelectorAll('.tabs li');
    const sections = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');

            // Toggle active tab
            tabs.forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            // Show target section
            sections.forEach(s => {
                if (s.id === target + '-content') {
                    s.classList.remove('is-hidden');
                } else {
                    s.classList.add('is-hidden');
                }
            });

            // Update URL for persistence
            const url = new URL(window.location);
            url.searchParams.set('tab', target);
            window.history.replaceState({}, '', url);
        });
    });
});
