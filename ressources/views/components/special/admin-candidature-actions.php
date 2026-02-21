<?php
/**
 * Admin Candidature Actions Component
 */
?>
<div id="validationActions" class="is-hidden p-4 has-background-light border-top">
    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <button type="button" onclick="showValidationConfirm()" class="button is-success is-fullwidth">
                <span class="icon"><i class="fas fa-check"></i></span>
                <span>Valider</span>
            </button>
        </div>
        <div class="control is-expanded">
            <button type="button" onclick="showRejectionForm()" class="button is-danger is-fullwidth">
                <span class="icon"><i class="fas fa-times"></i></span>
                <span>Rejeter</span>
            </button>
        </div>
    </div>

    <!-- Confirm Validation -->
    <div id="validationConfirmSection" class="is-hidden mt-4 notification is-success is-light p-3">
        <p class="mb-3 is-size-7">Confirmez-vous la validation ?</p>
        <form method="POST" id="validerForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="buttons is-centered">
                <button type="submit" class="button is-success is-small">Confirmer</button>
                <button type="button" onclick="hideValidationConfirm()" class="button is-light is-small">Annuler</button>
            </div>
        </form>
    </div>

    <!-- Rejection Form -->
    <div id="rejectionFormSection" class="is-hidden mt-4 notification is-danger is-light p-3">
        <form method="POST" id="rejeterForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="field">
                <label class="label is-small">Motif</label>
                <div class="control">
                    <div class="select is-fullwidth is-small">
                        <select name="motif_rejet" required>
                            <option value="">Sélectionnez...</option>
                            <option value="scolarite_non_soldee">Scolarité non soldée</option>
                            <option value="duree_stage_insuffisante">Durée insuffisante</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="field">
                <label class="label is-small">Commentaire</label>
                <div class="control"><textarea name="commentaire_rejet" required class="textarea is-small" rows="2"></textarea></div>
            </div>
            <div class="buttons is-centered">
                <button type="submit" class="button is-danger is-small">Confirmer Rejet</button>
                <button type="button" onclick="hideRejectionForm()" class="button is-light is-small">Annuler</button>
            </div>
        </form>
    </div>
</div>
