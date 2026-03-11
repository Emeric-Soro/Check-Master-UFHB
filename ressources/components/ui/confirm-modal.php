<?php
/**
 * MODAL DE CONFIRMATION UNIQUE DU SYSTÈME
 * 
 * Ce composant est le SEUL modal autorisé dans l'application.
 * Il est injecté une seule fois dans le layout principal (app-shell.php).
 * 
 * Usage JavaScript:
 *   const confirmed = await CM.confirm('Êtes-vous sûr ?');
 *   if (confirmed) { // action }
 * 
 * ou avec options:
 *   const confirmed = await CM.confirm({
 *       title: 'Confirmation',
 *       message: 'Supprimer cet élément ?',
 *       confirmText: 'Supprimer',
 *       cancelText: 'Annuler',
 *       type: 'danger' // 'danger' | 'warning' | 'info' | 'primary'
 *   });
 */
?>

<div id="cm-confirm-modal" class="cm-modal-overlay cm-modal-overlay--confirm" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cm-confirm-title">
    <div class="cm-modal cm-modal--confirm">
        <div class="cm-modal__header">
            <h3 class="cm-modal__title" id="cm-confirm-title">Confirmation</h3>
        </div>
        <div class="cm-modal__body">
            <p id="cm-confirm-message">Êtes-vous sûr de vouloir continuer ?</p>
        </div>
        <div class="cm-modal__footer">
            <button type="button" class="cm-btn is-light" id="cm-confirm-cancel">
                Annuler
            </button>
            <button type="button" class="cm-btn is-danger" id="cm-confirm-ok">
                Confirmer
            </button>
        </div>
    </div>
</div>
