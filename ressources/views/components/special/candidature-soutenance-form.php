<?php
/**
 * Formulaire de Candidature à la Soutenance (Infos Stage)
 */
$stage_info = $stage_info ?? [];
?>

<form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage">
    <div class="is-flex is-align-items-center mb-5">
        <span class="tag is-primary is-rounded mr-3">1</span>
        <h3 class="title is-4 mb-0">Informations de stage</h3>
    </div>

    <!-- Autocomplete Entreprise -->
    <div class="field mb-5">
        <label class="label">Entreprise <small class="has-text-grey-light">(Tapez pour filtrer ou ajouter)</small></label>
        <div class="cm-autocomplete-wrapper control has-icons-left">
            <input type="text" name="entreprise" id="entreprise" required autocomplete="off"
                value="<?= htmlspecialchars($stage_info['nom_entreprise'] ?? ''); ?>"
                class="input is-medium" placeholder="Ex: CIE, Orange, SODECI...">
            <span class="icon is-left is-medium"><i class="fas fa-building"></i></span>
            <div class="cm-autocomplete-suggestions" id="suggestions-list"></div>
        </div>
        <p class="help"><i class="fas fa-info-circle mr-1"></i> Si l'entreprise n'existe pas, elle sera créée automatiquement.</p>
    </div>

    <!-- Dates & Sujet -->
    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="field">
                <label class="label">Date de début</label>
                <div class="control has-icons-left">
                    <input type="date" name="date_debut" id="date_debut" required max="<?= date('Y-m-d'); ?>"
                        value="<?= htmlspecialchars($stage_info['date_debut_stage'] ?? ''); ?>" class="input">
                    <span class="icon is-left"><i class="fas fa-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Date de fin</label>
                <div class="control has-icons-left">
                    <input type="date" name="date_fin" id="date_fin" required max="<?= date('Y-m-d'); ?>"
                        value="<?= htmlspecialchars($stage_info['date_fin_stage'] ?? ''); ?>" class="input">
                    <span class="icon is-left"><i class="fas fa-calendar"></i></span>
                </div>
                <p id="date-error" class="help is-danger is-hidden"></p>
            </div>
        </div>
        <div class="column is-12">
            <div class="field">
                <label class="label">Sujet du stage</label>
                <div class="control has-icons-left">
                    <input type="text" name="sujet" required value="<?= htmlspecialchars($stage_info['sujet_stage'] ?? ''); ?>"
                        class="input" placeholder="Décrivez brièvement votre sujet de stage">
                    <span class="icon is-left"><i class="fas fa-file-alt"></i></span>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <!-- Encadrant -->
    <h4 class="title is-6 mb-4"><i class="fas fa-user-tie mr-2"></i>Encadrant en entreprise</h4>
    <div class="columns is-multiline">
        <div class="column is-4">
            <div class="field">
                <label class="label">Nom Complet</label>
                <div class="control has-icons-left">
                    <input class="input" type="text" name="encadrant" required 
                        value="<?= htmlspecialchars($stage_info['encadrant_entreprise'] ?? ''); ?>" placeholder="Nom de l'encadrant">
                    <span class="icon is-left"><i class="fas fa-user"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-4">
            <div class="field">
                <label class="label">Email</label>
                <div class="control has-icons-left">
                    <input class="input" type="email" name="email_encadrant" required 
                        value="<?= htmlspecialchars($stage_info['email_encadrant'] ?? ''); ?>" placeholder="Email professionnel">
                    <span class="icon is-left"><i class="fas fa-envelope"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-4">
            <div class="field">
                <label class="label">Téléphone</label>
                <div class="control has-icons-left">
                    <input class="input" type="tel" name="telephone_encadrant" required 
                        value="<?= htmlspecialchars($stage_info['telephone_encadrant'] ?? ''); ?>" placeholder="Numéro de téléphone">
                    <span class="icon is-left"><i class="fas fa-phone"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-6">
        <div class="control">
            <button type="submit" name="btn_enregistrer" value="1" class="button is-primary is-large">
                <span class="icon"><i class="fas fa-save"></i></span>
                <span>Enregistrer et continuer</span>
            </button>
        </div>
    </div>
</form>
