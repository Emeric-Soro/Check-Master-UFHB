<?php
/**
 * User Form Component
 */
$user = $user ?? null;
$action = $_GET['action'] ?? 'add';
$enseignantsNonUtilisateurs = $GLOBALS['enseignantsNonUtilisateurs'] ?? [];
$personnelNonUtilisateurs = $GLOBALS['personnelNonUtilisateurs'] ?? [];
$etudiantsNonUtilisateurs = $GLOBALS['etudiantsNonUtilisateurs'] ?? [];
?>
<form id="userForm" method="POST" action="?page=gestion_utilisateurs">
    <input type="hidden" name="id_utilisateur" value="<?= $user ? $user->id_utilisateur : ''; ?>">

    <div class="field">
        <label class="label">Nom d'utilisateur</label>
        <div class="control">
            <?php if ($action === 'add'): ?>
                <div class="select is-fullwidth">
                    <select name="nom_utilisateur" id="nom_utilisateur" required>
                        <option value="">Sélectionner une personne</option>
                        <optgroup label="Enseignants">
                            <?php foreach($enseignantsNonUtilisateurs as $e): ?>
                                <option value="<?= htmlspecialchars($e->nom_enseignant . ' ' . $e->prenom_enseignant) ?>" data-login="<?= htmlspecialchars($e->email_enseignant) ?>">
                                    <?= htmlspecialchars($e->nom_enseignant . ' ' . $e->prenom_enseignant) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Personnel Administratif">
                            <?php foreach($personnelNonUtilisateurs as $p): ?>
                                <option value="<?= htmlspecialchars($p->nom_pers_admin . ' ' . $p->prenom_pers_admin) ?>" data-login="<?= htmlspecialchars($p->email_pers_admin) ?>">
                                    <?= htmlspecialchars($p->nom_pers_admin . ' ' . $p->prenom_pers_admin) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Étudiants">
                            <?php foreach($etudiantsNonUtilisateurs as $et): ?>
                                <option value="<?= htmlspecialchars($et->nom_etu . ' ' . $et->prenom_etu) ?>" data-login="<?= htmlspecialchars($et->email_etu) ?>">
                                    <?= htmlspecialchars($et->nom_etu . ' ' . $et->prenom_etu) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            <?php else: ?>
                <input type="text" name="nom_utilisateur" class="input" value="<?= htmlspecialchars($user->nom_utilisateur ?? '') ?>" required>
            <?php endif; ?>
        </div>
    </div>

    <div class="field">
        <label class="label">Login (Email)</label>
        <div class="control">
            <input type="email" name="login_utilisateur" id="login_utilisateur" class="input" value="<?= htmlspecialchars($user->login_utilisateur ?? '') ?>" required>
        </div>
    </div>

    <div class="columns">
        <div class="column is-6">
            <div class="field">
                <label class="label">Type utilisateur</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_type_utilisateur" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($types as $type): ?>
                                <option value="<?= $type->id_type_utilisateur ?>" <?= ($user && $type->id_type_utilisateur == $user->id_type_utilisateur) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type->lib_type_utilisateur) ?>
                                </option>
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
                                <option value="<?= $groupe->id_GU ?>" <?= ($user && $groupe->id_GU == $user->id_GU) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($groupe->lib_GU) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="columns">
        <div class="column is-6">
            <div class="field">
                <label class="label">Niveau d'accès</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_niveau_acces" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach($niveaux as $niv): ?>
                                <option value="<?= $niv->id_niveau_acces_donnees ?>" <?= ($user && $niv->id_niveau_acces_donnees == $user->id_niv_acces_donnee) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($niv->lib_niveau_acces_donnees) ?>
                                </option>
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
                            <option value="Actif" <?= ($user && $user->statut_utilisateur === 'Actif') ? 'selected' : '' ?>>Actif</option>
                            <option value="Inactif" <?= ($user && $user->statut_utilisateur === 'Inactif') ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-5">
        <div class="control"><button type="button" onclick="closeUserModal()" class="button is-light">Annuler</button></div>
        <div class="control">
            <?php if ($action === 'edit'): ?>
                <button type="button" onclick="submitModifyForm()" class="button is-primary">Modifier</button>
            <?php else: ?>
                <button type="submit" name="btn_add_utilisateur" class="button is-primary">Enregistrer</button>
            <?php endif; ?>
        </div>
    </div>
</form>
