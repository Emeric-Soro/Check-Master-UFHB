<?php
/**
 * User Mass Form Component
 */
$enseignantsNonUtilisateurs = $GLOBALS['enseignantsNonUtilisateurs'] ?? [];
$personnelNonUtilisateurs = $GLOBALS['personnelNonUtilisateurs'] ?? [];
$etudiantsNonUtilisateurs = $GLOBALS['etudiantsNonUtilisateurs'] ?? [];
?>
<form method="POST" action="?page=gestion_utilisateurs" id="userMasse">
    <div class="field">
        <label class="label">Sélectionner les personnes</label>
        <div class="control">
            <select name="selected_persons[]" multiple size="10" required class="input" style="height: auto; min-height: 200px;">
                <optgroup label="Enseignants">
                    <?php foreach($enseignantsNonUtilisateurs as $e): ?>
                        <option value="ens_<?= $e->id_enseignant ?>">
                            <?= htmlspecialchars($e->nom_enseignant . ' ' . $e->prenom_enseignant) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Personnel Administratif">
                    <?php foreach($personnelNonUtilisateurs as $p): ?>
                        <option value="pers_<?= $p->id_pers_admin ?>">
                            <?= htmlspecialchars($p->nom_pers_admin . ' ' . $p->prenom_pers_admin) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Étudiants">
                    <?php foreach($etudiantsNonUtilisateurs as $et): ?>
                        <option value="etu_<?= $et->num_carte_etud ?>">
                            <?= htmlspecialchars($et->nom_etu . ' ' . $et->prenom_etu) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <p class="help">Maintenez Shift ou Ctrl pour sélectionner plusieurs personnes</p>
    </div>

    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="field">
                <label class="label">Type utilisateur</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_type_utilisateur" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($types as $type): ?>
                                <option value="<?= $type->id_type_utilisateur ?>"><?= htmlspecialchars($type->lib_type_utilisateur) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Groupe utilisateur</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_GU" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($groupes as $groupe): ?>
                                <option value="<?= $groupe->id_GU ?>"><?= htmlspecialchars($groupe->lib_GU) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Niveau d'accès</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_niveau_acces" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($niveaux as $niv): ?>
                                <option value="<?= $niv->id_niveau_acces_donnees ?>"><?= htmlspecialchars($niv->lib_niveau_acces_donnees) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Statut</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="statut_utilisateur" required>
                            <option value="Actif">Actif</option>
                            <option value="Inactif">Inactif</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-5">
        <div class="control"><button type="button" onclick="closeMasseModal()" class="button is-light">Annuler</button></div>
        <div class="control">
            <button type="submit" name="btn_add_multiple" class="button is-primary">Ajouter en masse</button>
        </div>
    </div>
</form>
