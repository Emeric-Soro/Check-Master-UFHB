<?php

$file = 'c:/wamp64/www/checkmaster.ufrmi-ufhb-ci/ressources/views/v2/archives/hub_historique.php';
$content = file_get_contents($file);

// 1. Add variables at top
$varReplacement = <<<PHP

\$juries = \$data['juries'] ?? [];

\$soutenances = \$data['soutenances'] ?? [];
\$soutenancesTotal = \$data['soutenances_total'] ?? 0;
\$soutenancesPage = \$data['soutenances_page'] ?? 1;
\$soutenancesTotalPages = \$data['soutenances_total_pages'] ?? 1;

\$documents = \$data['documents'] ?? [];
\$documentsTotal = \$data['documents_total'] ?? 0;
\$documentsPage = \$data['documents_page'] ?? 1;
\$documentsTotalPages = \$data['documents_total_pages'] ?? 1;

\$candidatures = \$data['candidatures'] ?? [];
\$candidaturesTotal = \$data['candidatures_total'] ?? 0;
\$candidaturesPage = \$data['candidatures_page'] ?? 1;
\$candidaturesTotalPages = \$data['candidatures_total_pages'] ?? 1;

\$reclamations = \$data['reclamations'] ?? [];
\$reclamationsTotal = \$data['reclamations_total'] ?? 0;
\$reclamationsPage = \$data['reclamations_page'] ?? 1;
\$reclamationsTotalPages = \$data['reclamations_total_pages'] ?? 1;
PHP;
$content = str_replace("\$juries = \$data['juries'] ?? [];", $varReplacement, $content);

// 2. Add tabs in HTML
$tabReplacement = <<<HTML
            <a class="cm-btn <?= \$activeTab === 'etudiants' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=etudiants', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                <span>Étudiants</span>
                <?php if ((\$stats['etudiants'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-info cm-ml-1"><?= number_format(\$stats['etudiants']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= \$activeTab === 'soutenances' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=soutenances', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                <span>Soutenances</span>
                <?php if ((\$stats['soutenances'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-success cm-ml-1"><?= number_format(\$stats['soutenances']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= \$activeTab === 'jurys' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=jurys', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Jurys</span>
                <?php if ((\$stats['jurys'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-warning cm-ml-1"><?= number_format(\$stats['jurys']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= \$activeTab === 'documents' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=documents', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-folder-open" aria-hidden="true"></i>
                <span>Documents</span>
                <?php if ((\$stats['documents'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-primary cm-ml-1"><?= number_format(\$stats['documents']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= \$activeTab === 'candidatures' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=candidatures', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                <span>Candidatures</span>
            </a>
            <a class="cm-btn <?= \$activeTab === 'reclamations' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=reclamations', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span>Réclamations</span>
            </a>
HTML;

// Search for the etudiants and jurys tabs to replace with all tabs
$searchTabsStart = '<a class="cm-btn <?= $activeTab === \'etudiants\' ? \'is-info\' : \'is-light\' ?>" href="<?= htmlspecialchars($tabBase . \'&tab=etudiants\', ENT_QUOTES, \'UTF-8\') ?>">';
$searchTabsEnd = '<?php endif; ?>
            </a>'; // The one after jurys

// Actually let's just use string replace with the exact text found early.
$oldTabs = <<<HTML
            <a class="cm-btn <?= \$activeTab === 'etudiants' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=etudiants', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                <span>Étudiants</span>
                <?php if ((\$stats['etudiants'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-info cm-ml-1"><?= number_format(\$stats['etudiants']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= \$activeTab === 'jurys' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars(\$tabBase . '&tab=jurys', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Jurys</span>
                <?php if ((\$stats['jurys'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-warning cm-ml-1"><?= number_format(\$stats['jurys']) ?></span>
                <?php endif; ?>
            </a>
HTML;

$content = str_replace($oldTabs, $tabReplacement, $content);

// 3. Add tab content panels
$newPanels = <<<HTML

            <?php elseif (\$activeTab === 'soutenances'): ?>
            <!-- =============== ONGLET: SOUTENANCES =============== -->
            <?php
            \$soutenancesPager = cm_paginate(max(\$soutenancesTotal, 1), 20, \$soutenancesPage);
            \$soutenancesPager['last'] = \$soutenancesTotalPages;
            \$soutenancesPagerBase = \$tabBase . '&tab=soutenances';
            ?>
            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0">
                    <strong><?= number_format(\$soutenancesTotal) ?></strong> soutenance<?= \$soutenancesTotal > 1 ? 's' : '' ?>
                </p>
            </div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">#</th>
                            <th class="cm-data-table__th">Matricule</th>
                            <th class="cm-data-table__th">Nom & Prénoms</th>
                            <th class="cm-data-table__th">Thème</th>
                            <th class="cm-data-table__th">Date de soutenance</th>
                            <th class="cm-data-table__th">Salle</th>
                            <th class="cm-data-table__th is-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty(\$soutenances)): ?>
                        <tr><td colspan="7" class="cm-data-table__td is-center cm-p-5">Aucune soutenance trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach (\$soutenances as \$idx => \$s): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-text-muted"><?= \$idx + 1 ?></td>
                                <td class="cm-data-table__td"><code><?= htmlspecialchars(\$s['num_carte_etud'] ?? '') ?></code></td>
                                <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars(\$s['etudiant'] ?? '') ?></td>
                                <td class="cm-data-table__td"><span class="cm-text-ellipsis" title="<?= htmlspecialchars(\$s['theme_soutenance'] ?? '') ?>"><?= htmlspecialchars(\$s['theme_soutenance'] ?? '') ?></span></td>
                                <td class="cm-data-table__td"><?= !empty(\$s['date_soutenance']) ? date('d/m/Y', strtotime(\$s['date_soutenance'])) : '-' ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(\$s['lib_salle'] ?? '-') ?></td>
                                <td class="cm-data-table__td is-center">
                                    <a class="cm-btn-action is-edit" href="?page=admin_historique&action=fiche_soutenance&id=<?= urlencode(\$s['num_soutenance'] ?? '') ?>" title="Consulter la soutenance">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php cm_component('crud/pagination', ['pagination' => \$soutenancesPager, 'base_url' => \$soutenancesPagerBase, 'param_name' => 'p']); ?>

            <?php elseif (\$activeTab === 'documents'): ?>
            <!-- =============== ONGLET: DOCUMENTS =============== -->
            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0"><strong><?= number_format(\$documentsTotal) ?></strong> document<?= \$documentsTotal > 1 ? 's' : '' ?></p>
            </div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Type</th>
                            <th class="cm-data-table__th">Titre</th>
                            <th class="cm-data-table__th">Étudiant</th>
                            <th class="cm-data-table__th">Date</th>
                            <th class="cm-data-table__th is-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty(\$documents)): ?>
                        <tr><td colspan="5" class="cm-data-table__td is-center cm-p-5">Aucun document trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach (\$documents as \$d): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => \$d['type_doc'], 'type' => 'secondary']); ?></td>
                                <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars(\$d['titre'] ?? '') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(\$d['etudiant'] ?? '') ?></td>
                                <td class="cm-data-table__td"><?= !empty(\$d['date_rapport']) ? date('d/m/Y', strtotime(\$d['date_rapport'])) : '-' ?></td>
                                <td class="cm-data-table__td is-center">
                                    <a class="cm-btn-action is-edit" href="?page=admin_historique&action=visionneuse_document&id=<?= urlencode(\$d['id_doc'] ?? '') ?>&type=<?= urlencode(\$d['type_doc'] ?? '') ?>" title="Visualiser">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif (\$activeTab === 'candidatures'): ?>
            <!-- =============== ONGLET: CANDIDATURES =============== -->
            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0"><strong><?= number_format(\$candidaturesTotal) ?></strong> candidature<?= \$candidaturesTotal > 1 ? 's' : '' ?></p>
            </div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Étudiant</th>
                            <th class="cm-data-table__th">Promotion</th>
                            <th class="cm-data-table__th">Date candidature</th>
                            <th class="cm-data-table__th">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty(\$candidatures)): ?>
                        <tr><td colspan="4" class="cm-data-table__td is-center cm-p-5">Aucune candidature trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach (\$candidatures as \$c): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars(\$c['etudiant'] ?? '') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(\$c['promotion_etu'] ?? '') ?></td>
                                <td class="cm-data-table__td"><?= !empty(\$c['date_candidature']) ? date('d/m/Y', strtotime(\$c['date_candidature'])) : '-' ?></td>
                                <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => \$c['statut_candidature'] ?? 'soumis', 'type' => 'info']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif (\$activeTab === 'reclamations'): ?>
            <!-- =============== ONGLET: RECLAMATIONS =============== -->
            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0"><strong><?= number_format(\$reclamationsTotal) ?></strong> réclamation<?= \$reclamationsTotal > 1 ? 's' : '' ?></p>
            </div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Étudiant</th>
                            <th class="cm-data-table__th">Motif / Type</th>
                            <th class="cm-data-table__th">Statut</th>
                            <th class="cm-data-table__th">Date de création</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty(\$reclamations)): ?>
                        <tr><td colspan="4" class="cm-data-table__td is-center cm-p-5">Aucune réclamation trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach (\$reclamations as \$r): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars(\$r['etudiant'] ?? '') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(\$r['type_reclamation'] ?? 'Générale') ?></td>
                                <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => \$r['statut_libelle'] ?? 'Nouveau', 'type' => 'warning']); ?></td>
                                <td class="cm-data-table__td"><?= !empty(\$r['date_creation']) ? date('d/m/Y H:i', strtotime(\$r['date_creation'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

HTML;

$content = str_replace("<?php elseif (\$activeTab === 'statistiques'): ?>", $newPanels . "\n            <?php elseif (\$activeTab === 'statistiques'): ?>", $content);

file_put_contents($file, $content);
echo "Modification done!\n";
