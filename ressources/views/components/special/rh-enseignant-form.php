<?php
$action = $action ?? 'add';
$enseignant = $enseignant ?? null;
$specialites = $specialites ?? [];
$fonctions = $fonctions ?? [];
$grades = $grades ?? [];
?>
<form action="?page=gestion_rh&tab=enseignant" method="POST">
    <?php if ($action === 'edit' && $enseignant): ?>
        <input type="hidden" name="id_enseignant" value="<?= htmlspecialchars($enseignant->id_enseignant) ?>">
    <?php endif; ?>

    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="field">
                <label class="label">Nom</label>
                <div class="control">
                    <input class="input" type="text" name="nom" value="<?= $enseignant ? htmlspecialchars($enseignant->nom_enseignant) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Prénom</label>
                <div class="control">
                    <input class="input" type="text" name="prenom" value="<?= $enseignant ? htmlspecialchars($enseignant->prenom_enseignant) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Email</label>
                <div class="control has-icons-left">
                    <input class="input" type="email" name="email" value="<?= $enseignant ? htmlspecialchars($enseignant->mail_enseignant) : '' ?>" required>
                    <span class="icon is-small is-left"><i class="fas fa-envelope"></i></span>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Spécialité</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_specialite" required>
                            <option value="">Sélectionnez...</option>
                            <?php foreach ($specialites as $s): ?>
                                <option value="<?= $s->id_specialite ?>" <?= ($enseignant && $enseignant->id_specialite == $s->id_specialite) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->lib_specialite) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Fonction</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_fonction" required>
                            <option value="">Sélectionnez...</option>
                            <?php foreach ($fonctions as $f): ?>
                                <option value="<?= $f->id_fonction ?>" <?= ($enseignant && $enseignant->id_fonction == $f->id_fonction) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f->lib_fonction) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Date fonction</label>
                <div class="control">
                    <input class="input" type="date" name="date_fonction" value="<?= $enseignant ? $enseignant->date_occupation : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Grade</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="id_grade" required>
                            <option value="">Sélectionnez...</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g->id_grade ?>" <?= ($enseignant && $enseignant->id_grade == $g->id_grade) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g->lib_grade) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Date grade</label>
                <div class="control">
                    <input class="input" type="date" name="date_grade" value="<?= $enseignant ? $enseignant->date_grade : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Type d'enseignant</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="type_enseignant" required>
                            <option value="Simple" <?= ($enseignant && $enseignant->type_enseignant) ? 'selected' : '' ?>>Simple</option>
                            <option value="Administratif" <?= ($enseignant && !$enseignant->type_enseignant) ? 'selected' : '' ?>>Administratif</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-5">
        <div class="control">
            <a href="?page=gestion_rh&tab=enseignant" class="button is-light">Annuler</a>
        </div>
        <div class="control">
            <button type="submit" name="<?= ($action === 'edit') ? 'btn_modifier_enseignant' : 'btn_add_enseignant' ?>" class="button is-primary">
                <?= ($action === 'edit') ? 'Modifier' : 'Ajouter' ?>
            </button>
        </div>
    </div>
</form>
