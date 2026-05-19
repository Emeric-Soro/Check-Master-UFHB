<div class="cm-prd3-screen">
    <?php if (!$identite): ?>
        <div class="cm-card">
            <div class="cm-card__body cm-text-center">
                <p class="cm-text-muted">Parametre <code>id</code> manquant ou personnel introuvable.</p>
                <p class="cm-mt-sm">
                    <a href="?page=maj_personnel_admin" class="cm-btn is-primary-accent">
                        <i class="fas fa-arrow-left cm-mr-sm"></i>Retour a la liste
                    </a>
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- En-tete identite -->
        <div class="cm-card">
            <div class="cm-card__header cm-flex-between">
                <h3 class="cm-card__title">
                    <i class="fas fa-user-tie cm-mr-sm"></i>
                    <?= htmlspecialchars(trim(($identite['nom_pers_admin'] ?? '') . ' ' . ($identite['prenom_pers_admin'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <a href="?page=maj_personnel_admin" class="cm-btn is-sm is-primary-accent">
                    <i class="fas fa-arrow-left cm-mr-sm"></i>Liste
                </a>
            </div>
            <div class="cm-card__body">
                <div class="cm-grid-4">
                    <div>
                        <span class="cm-text-muted cm-text-sm">Matricule</span>
                        <p class="cm-text-semibold">#<?= (int) ($identite['id_pers_admin'] ?? 0) ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Email</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($identite['email_pers_admin'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Telephone</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($identite['tel_pers_admin'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Genre</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($identite['libelle_genre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Poste et compte utilisateur -->
        <div class="cm-grid-2 cm-mt-md">
            <div class="cm-card">
                <div class="cm-card__header">
                    <h3 class="cm-card__title"><i class="fas fa-briefcase cm-mr-sm"></i>Poste</h3>
                </div>
                <div class="cm-card__body">
                    <div class="cm-grid-2">
                        <div>
                            <span class="cm-text-muted cm-text-sm">Intitule du poste</span>
                            <p class="cm-text-semibold"><?= htmlspecialchars($identite['poste'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <span class="cm-text-muted cm-text-sm">Date d'embauche</span>
                            <p class="cm-text-semibold"><?= !empty($identite['date_embauche']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $identite['date_embauche'])), ENT_QUOTES, 'UTF-8') : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cm-card">
                <div class="cm-card__header">
                    <h3 class="cm-card__title"><i class="fas fa-user-lock cm-mr-sm"></i>Compte utilisateur</h3>
                </div>
                <div class="cm-card__body">
                    <?php if ($compte): ?>
                    <div class="cm-grid-3">
                        <div>
                            <span class="cm-text-muted cm-text-sm">Login</span>
                            <p class="cm-text-semibold"><?= htmlspecialchars($compte['login_utilisateur'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <span class="cm-text-muted cm-text-sm">Groupe</span>
                            <p class="cm-text-semibold"><?= htmlspecialchars($compte['lib_GU'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <span class="cm-text-muted cm-text-sm">Statut</span>
                            <?php
                            $st = $compte['statut_utilisateur'] ?? 'Inactif';
                            $bt = $st === 'Actif' ? 'success' : 'warning';
                            cm_component('ui/badge', ['text' => $st, 'type' => $bt]);
                            ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="cm-text-muted">Aucun compte utilisateur associe.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistiques widgets -->
        <div class="cm-grid-4 cm-mt-md">
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ((int) ($stats['total_actions'] ?? 0)),
                'label' => 'Actions auditees',
                'icon' => 'fa-clipboard-list',
                'color' => 'info',
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ((int) ($stats['candidatures_traitees'] ?? 0)),
                'label' => 'Candidatures traitees',
                'icon' => 'fa-file-signature',
                'color' => 'primary',
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ((int) ($stats['succes'] ?? 0)),
                'label' => 'Actions réussies',
                'icon' => 'fa-circle-check',
                'color' => 'success',
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ((int) ($stats['erreurs'] ?? 0)),
                'label' => 'Actions en erreur',
                'icon' => 'fa-circle-xmark',
                'color' => 'danger',
            ]); ?>
        </div>

        <!-- Candidatures traitees -->
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-file-signature cm-mr-sm"></i>Candidatures traitees</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($candidatures)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucune candidature', 'message' => 'Aucune candidature traitee par ce personnel.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Etudiant</th>
                                    <th class="cm-data-table__th">Statut</th>
                                    <th class="cm-data-table__th">Date candidature</th>
                                    <th class="cm-data-table__th">Date traitement</th>
                                    <th class="cm-data-table__th">Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidatures as $c): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=gestion_candidatures_soutenance&examiner=<?= urlencode((string) ($c['num_etu'] ?? '')) ?>&etape=1">
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars(trim(($c['nom_etu'] ?? '') . ' ' . ($c['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php
                                        $statut = $c['statut_candidature'] ?? 'En attente';
                                        $badgeType = 'info';
                                        if ($statut === 'Validee' || $statut === 'Validée') $badgeType = 'success';
                                        elseif ($statut === 'Rejetee' || $statut === 'Rejetée') $badgeType = 'danger';
                                        cm_component('ui/badge', ['text' => $statut, 'type' => $badgeType]);
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= !empty($c['date_candidature']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $c['date_candidature'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= !empty($c['date_traitement']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $c['date_traitement'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars(mb_substr((string) ($c['commentaire_admin'] ?? '-'), 0, 60), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique des actions -->
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-history cm-mr-sm"></i>Historique des actions</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($historique)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucune action', 'message' => 'Aucune action auditee trouvee.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Action</th>
                                    <th class="cm-data-table__th">Table concernee</th>
                                    <th class="cm-data-table__th">Statut</th>
                                    <th class="cm-data-table__th">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique as $h): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=piste_audit">
                                    <td class="cm-data-table__td"><?= htmlspecialchars($h['action'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars($h['nom_table'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?php
                                        $statutAction = $h['statut_action'] ?? 'Succès';
                                        $btAction = $statutAction === 'Succès' ? 'success' : 'danger';
                                        cm_component('ui/badge', ['text' => $statutAction, 'type' => $btAction]);
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= !empty($h['date_creation']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $h['date_creation'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
