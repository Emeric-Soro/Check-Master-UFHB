/**
 * DocViewer — Module de previsualisation PDF unifiee.
 * Fournit CM.openDocViewer(type, id, options) pour ouvrir un apercu PDF en overlay.
 *
 * Usage :
 *   CM.openDocViewer('rapport', '123', { title: 'Mon rapport' });
 *   CM.openDocViewer('recu', 'REC-2026-00001', { title: 'Recu de paiement' });
 */
(function () {
    'use strict';

    if (typeof window.CM === 'undefined') {
        window.CM = {};
    }

    var currentOverlay = null;

    /**
     * Ouvre un apercu PDF dans un overlay Plein écran.
     *
     * @param {string} type   — Type de document (rapport, recu, pv_commission, pv_final, planning, bulletin, compte_rendu)
     * @param {string} id     — Identifiant du document (PK, reference, ou nom de fichier)
     * @param {object} [options]
     * @param {string} [options.title]        — Titre affiche dans la toolbar
     * @param {boolean} [options.fullscreen]  — Demarrer en mode plein ecran
     */
    window.CM.openDocViewer = function (type, id, options) {
        options = options || {};

        // Fermer un overlay existant
        if (currentOverlay) {
            closeDocViewer();
        }

        var title = options.title || 'Document';
        var previewUrl = '?page=docviewer&type=' + encodeURIComponent(type) +
                         '&id=' + encodeURIComponent(id) + '&action=preview';
        var downloadUrl = '?page=docviewer&type=' + encodeURIComponent(type) +
                          '&id=' + encodeURIComponent(id) + '&action=download';

        // Creer l'overlay
        var overlay = document.createElement('div');
        overlay.className = 'cm-docviewer-overlay' + (options.fullscreen ? ' is-fullscreen' : '');

        var container = document.createElement('div');
        container.className = 'cm-docviewer-overlay__container';

        // Toolbar
        var toolbar = document.createElement('div');
        toolbar.className = 'cm-docviewer-overlay__toolbar';

        var titleEl = document.createElement('h3');
        titleEl.className = 'cm-docviewer-overlay__title';
        titleEl.textContent = title;

        var actions = document.createElement('div');
        actions.className = 'cm-docviewer-overlay__actions';

        // Bouton Plein écran
        var btnFullscreen = document.createElement('button');
        btnFullscreen.className = 'cm-btn is-ghost is-sm';
        btnFullscreen.innerHTML = '<i class="fas fa-expand"></i>';
        btnFullscreen.title = 'Plein écran';
        btnFullscreen.setAttribute('type', 'button');
        btnFullscreen.addEventListener('click', function () {
            overlay.classList.toggle('is-fullscreen');
            var icon = btnFullscreen.querySelector('i');
            if (overlay.classList.contains('is-fullscreen')) {
                icon.className = 'fas fa-compress';
            } else {
                icon.className = 'fas fa-expand';
            }
        });

        // Bouton Télécharger
        var btnDownload = document.createElement('a');
        btnDownload.className = 'cm-btn is-info is-sm';
        btnDownload.href = downloadUrl;
        btnDownload.innerHTML = '<i class="fas fa-download"></i> Télécharger';
        btnDownload.setAttribute('target', '_blank');
        btnDownload.setAttribute('rel', 'noopener');

        // Bouton fermer
        var btnClose = document.createElement('button');
        btnClose.className = 'cm-btn is-ghost is-sm';
        btnClose.innerHTML = '<i class="fas fa-times"></i>';
        btnClose.title = 'Fermer';
        btnClose.setAttribute('type', 'button');
        btnClose.addEventListener('click', closeDocViewer);

        actions.appendChild(btnFullscreen);
        actions.appendChild(btnDownload);
        actions.appendChild(btnClose);

        toolbar.appendChild(titleEl);
        toolbar.appendChild(actions);

        // Corps avec iframe
        var body = document.createElement('div');
        body.className = 'cm-docviewer-overlay__body';

        // Loading
        var loading = document.createElement('div');
        loading.className = 'cm-docviewer-loading';
        loading.innerHTML = '<div class="cm-docviewer-loading__spinner"></div>' +
                            '<div class="cm-docviewer-loading__text">Chargement du document...</div>';

        var iframe = document.createElement('iframe');
        iframe.setAttribute('title', title);
        iframe.addEventListener('load', function () {
            loading.classList.add('is-hidden');
        });

        body.appendChild(loading);
        body.appendChild(iframe);

        container.appendChild(toolbar);
        container.appendChild(body);
        overlay.appendChild(container);

        // Fermer en cliquant sur le fond
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeDocViewer();
            }
        });

        document.body.appendChild(overlay);
        currentOverlay = overlay;

        // Definir le src apres insertion dans le DOM pour declencher le chargement
        iframe.src = previewUrl;

        // Bloquer le scroll du body
        document.body.style.overflow = 'hidden';
    };

    /**
     * Ferme l'overlay DocViewer actif.
     */
    function closeDocViewer() {
        if (currentOverlay) {
            currentOverlay.remove();
            currentOverlay = null;
            document.body.style.overflow = '';
        }
    }

    // Escape pour fermer
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && currentOverlay) {
            closeDocViewer();
        }
    });

    // Exposer la fermeture
    window.CM.closeDocViewer = closeDocViewer;
})();
