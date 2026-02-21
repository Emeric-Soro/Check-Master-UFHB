<?php
$action = $action ?? 'add';
$admin = $admin ?? null;
?>
<form action="?page=gestion_rh&tab=pers_admin" method="POST">
    <?php if ($action === 'edit' && $admin): ?>
        <input type="hidden" name="id_pers_admin" value="<?= htmlspecialchars($admin->id_pers_admin) ?>">
    <?php endif; ?>

    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="field">
                <label class="label">Nom</label>
                <div class="control">
                    <input class="input" type="text" name="nom" value="<?= $admin ? htmlspecialchars($admin->nom_pers_admin) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Prénom</label>
                <div class="control">
                    <input class="input" type="text" name="prenom" value="<?= $admin ? htmlspecialchars($admin->prenom_pers_admin) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Email</label>
                <div class="control has-icons-left">
                    <input class="input" type="email" name="email" value="<?= $admin ? htmlspecialchars($admin->email_pers_admin) : '' ?>" required>
                    <span class="icon is-small is-left"><i class="fas fa-envelope"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Téléphone</label>
                <div class="control has-icons-left">
                    <input class="input" type="tel" name="telephone" value="<?= $admin ? htmlspecialchars($admin->tel_pers_admin) : '' ?>" required>
                    <span class="icon is-small is-left"><i class="fas fa-phone"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Poste</label>
                <div class="control">
                    <input class="input" type="text" name="poste" value="<?= $admin ? htmlspecialchars($admin->poste) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Date d'embauche</label>
                <div class="control">
                    <input class="input" type="date" name="date_embauche" value="<?= $admin ? $admin->date_embauche : '' ?>" required>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-5">
        <div class="control">
            <a href="?page=gestion_rh&tab=pers_admin" class="button is-light">Annuler</a>
        </div>
        <div class="control">
            <button type="submit" name="<?= ($action === 'edit') ? 'btn_modifier_pers_admin' : 'btn_add_pers_admin' ?>" class="button is-primary">
                <?= ($action === 'edit') ? 'Modifier' : 'Enregistrer' ?>
            </button>
        </div>
    </div>
</form>
