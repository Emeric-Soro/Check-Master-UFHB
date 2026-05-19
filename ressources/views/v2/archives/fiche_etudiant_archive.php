<?php
/**
 * Fiche Étudiant Archive
 */
require_once __DIR__ . '/partials/_student_record_helpers.php';

$etudiant = $data['etudiant'] ?? null;
$parcours = $data['parcours'] ?? [];
$soutenances = $data['soutenances'] ?? [];
$documents = $data['documents'] ?? [];
$reclamations = $data['reclamations'] ?? [];

if (!$etudiant) {
    echo '<div class="cm-alert cm-alert-danger">Étudiant non trouvé</div>';
    return;
}

$onglet = $_GET['onglet'] ?? 'infos';
$latestParcours = $parcours[0] ?? null;
$latestSoutenance = $soutenances[0] ?? null;
$promotion = FormattingUtils::formatPromotion($etudiant->promotion_etu ?? '');
$niveauLabel = $latestParcours->lib_niv_etude ?? null;

$tabs = [
    'infos' => ['label' => 'Identité', 'icon' => 'fa-id-card', 'count' => 1],
    'parcours' => ['label' => 'Parcours', 'icon' => 'fa-route', 'count' => count($parcours)],
    'soutenance' => ['label' => 'Soutenance', 'icon' => 'fa-microphone-lines', 'count' => count($soutenances)],
    'documents' => ['label' => 'Documents', 'icon' => 'fa-folder-open', 'count' => count($documents)],
    'reclamations' => ['label' => 'Réclamations', 'icon' => 'fa-life-ring', 'count' => count($reclamations)],
];
?>

<section class="cm-student-record cm-student-record--archive">
    <div class="cm-card cm-student-record__hero">
        <div class="cm-student-record__hero-main">
            <div class="cm-student-record__hero-copy">
                <span class="cm-student-record__eyebrow">Archive étudiante</span>
                <h1 class="cm-student-record__hero-title">
                    <?= htmlspecialchars(trim(($etudiant->nom_etu ?? '') . ' ' . ($etudiant->prenom_etu ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p class="cm-student-record__hero-subtitle">
                    <span class="cm-student-record__code"><?= htmlspecialchars((string) ($etudiant->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </p>

                <div class="cm-student-record__meta">
                    <?php if ($promotion !== ''): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            <?= htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($niveauLabel)): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                            <?= htmlspecialchars((string) $niveauLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($etudiant->email_etu)): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <?= htmlspecialchars((string) $etudiant->email_etu, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="cm-student-record__status-row">
                    <?php if (!empty($latestSoutenance->date_soutenance)): ?>
                        <?php cm_student_record_badge('Soutenance ' . cm_student_record_date($latestSoutenance->date_soutenance), 'primary'); ?>
                    <?php endif; ?>

                    <?php if (!empty($documents)): ?>
                        <?php cm_student_record_badge(count($documents) . ' document' . (count($documents) > 1 ? 's' : ''), 'success'); ?>
                    <?php endif; ?>

                    <?php if (!empty($reclamations)): ?>
                        <?php cm_student_record_badge(count($reclamations) . ' réclamation' . (count($reclamations) > 1 ? 's' : ''), 'warning'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cm-student-record__hero-actions">
                <a href="?page=parcours_etudiant&id=<?= urlencode((string) ($etudiant->num_carte_etud ?? '')) ?>" class="cm-btn is-primary is-sm">
                    <i class="fas fa-route" aria-hidden="true"></i>
                    <span>Parcours complet</span>
                </a>
                <a href="?page=archives_etudiants" class="cm-btn is-light is-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Retour</span>
                </a>
            </div>
        </div>

        <div class="cm-student-record__metrics">
            <?php cm_student_record_metric('Promotion', cm_student_record_value($promotion), 'fa-calendar-alt', 'primary'); ?>
            <?php cm_student_record_metric('Parcours', (string) count($parcours), 'fa-route', count($parcours) > 0 ? 'info' : 'warning'); ?>
            <?php cm_student_record_metric('Documents', (string) count($documents), 'fa-folder-open', count($documents) > 0 ? 'success' : 'warning'); ?>
            <?php cm_student_record_metric('Réclamations', (string) count($reclamations), 'fa-life-ring', count($reclamations) > 0 ? 'warning' : 'info'); ?>
            <?php cm_student_record_metric('Soutenances', (string) count($soutenances), 'fa-microphone-lines', count($soutenances) > 0 ? 'primary' : 'warning'); ?>
        </div>
    </div>

    <nav class="cm-student-record__tabs" role="tablist" aria-label="Navigation archive étudiante">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = $onglet === $key; ?>
            <a href="?page=fiche_etudiant_archive&id=<?= urlencode((string) ($etudiant->num_carte_etud ?? '')) ?>&onglet=<?= urlencode($key) ?>"
               class="cm-student-record__tab<?= $isActive ? ' is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $isActive ? 'true' : 'false' ?>"
               aria-current="<?= $isActive ? 'page' : 'false' ?>">
                <i class="fas <?= htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (($tab['count'] ?? 0) > 0): ?>
                    <span class="cm-student-record__tab-count"><?= (int) $tab['count'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="cm-student-record__content">
        <?php switch ($onglet):
            case 'infos':
                ?>
                <section class="cm-student-record__section">
                    <div class="cm-student-record__metrics">
                        <?php cm_student_record_metric('Genre', cm_student_record_value($etudiant->libelle_genre ?? null), 'fa-venus-mars', 'info'); ?>
                        <?php cm_student_record_metric('Naissance', cm_student_record_date($etudiant->date_naiss_etu ?? null), 'fa-cake-candles', 'primary'); ?>
                        <?php cm_student_record_metric('Dernière inscription', cm_student_record_date($latestParcours->date_inscription ?? null), 'fa-file-signature', 'success'); ?>
                        <?php cm_student_record_metric('Niveau observé', cm_student_record_value($niveauLabel), 'fa-layer-group', 'warning'); ?>
                    </div>

                    <div class="cm-student-record__grid cm-student-record__grid--two">
                        <article class="cm-card cm-student-record__panel">
                            <div class="cm-student-record__panel-head">
                                <h3 class="cm-student-record__panel-title">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                    <span>Coordonnées</span>
                                </h3>
                            </div>
                            <div class="cm-student-record__panel-body">
                                <?php cm_student_record_field_list([
                                    ['label' => 'Matricule', 'value' => $etudiant->num_carte_etud ?? null],
                                    ['label' => 'Identifiant MESRS', 'value' => $etudiant->num_ident_etud ?? null],
                                    ['label' => 'Nom', 'value' => $etudiant->nom_etu ?? null],
                                    ['label' => 'Prénom', 'value' => $etudiant->prenom_etu ?? null],
                                    ['label' => 'Email', 'value' => $etudiant->email_etu ?? null],
                                    ['label' => 'Téléphone', 'value' => $etudiant->tel_etu ?? null],
                                    ['label' => 'Adresse', 'value' => $etudiant->adresse_etu ?? null],
                                ]); ?>
                            </div>
                        </article>

                        <article class="cm-card cm-student-record__panel">
                            <div class="cm-student-record__panel-head">
                                <h3 class="cm-student-record__panel-title">
                                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                                    <span>Repères académiques</span>
                                </h3>
                            </div>
                            <div class="cm-student-record__panel-body">
                                <?php cm_student_record_field_list([
                                    ['label' => 'Promotion', 'value' => $promotion],
                                    ['label' => 'Genre', 'value' => $etudiant->libelle_genre ?? null],
                                    ['label' => 'Date de naissance', 'value' => cm_student_record_date($etudiant->date_naiss_etu ?? null)],
                                    ['label' => 'Niveau observé', 'value' => $niveauLabel],
                                    ['label' => 'Parcours', 'value' => count($parcours) . ' entrée' . (count($parcours) > 1 ? 's' : '')],
                                    ['label' => 'Documents', 'value' => count($documents) . ' fichier' . (count($documents) > 1 ? 's' : '')],
                                    ['label' => 'Réclamations', 'value' => count($reclamations) . ' dossier' . (count($reclamations) > 1 ? 's' : '')],
                                ]); ?>
                            </div>
                        </article>
                    </div>
                </section>
                <?php
                break;

            case 'parcours':
                if (empty($parcours)) {
                    cm_student_record_empty('Aucun parcours', 'Aucune inscription retrouvée pour cet étudiant.', 'fa-route');
                    break;
                }

                $totalFrais = 0.0;
                $totalVerse = 0.0;
                $totalSolde = 0.0;
                foreach ($parcours as $item) {
                    $totalFrais += (float) ($item->frais_inscription ?? 0);
                    $totalVerse += (float) ($item->montant_verser ?? 0);
                    $totalSolde += (float) ($item->solde ?? 0);
                }
                ?>
                <section class="cm-student-record__section">
                    <div class="cm-student-record__metrics">
                        <?php cm_student_record_metric('Inscriptions', (string) count($parcours), 'fa-file-signature', 'primary'); ?>
                        <?php cm_student_record_metric('Total versé', cm_student_record_money($totalVerse), 'fa-wallet', 'success'); ?>
                        <?php cm_student_record_metric('Solde cumulé', cm_student_record_money($totalSolde), 'fa-scale-balanced', $totalSolde <= 0 ? 'success' : 'warning'); ?>
                        <?php cm_student_record_metric('Dernier niveau', cm_student_record_value($latestParcours->lib_niv_etude ?? null), 'fa-layer-group', 'info'); ?>
                    </div>

                    <div class="cm-student-record__timeline">
                        <?php foreach ($parcours as $item): ?>
                            <?php
                            $frais = (float) ($item->frais_inscription ?? 0);
                            $verse = (float) ($item->montant_verser ?? 0);
                            $solde = (float) ($item->solde ?? 0);
                            $progress = cm_student_record_percent($verse, $frais);
                            $statut = $item->statut_inscription ?? ($solde <= 0 ? 'Soldé' : ($verse > 0 ? 'Partiel' : 'Impayé'));
                            ?>
                            <article class="cm-student-record__timeline-item">
                                <div class="cm-student-record__timeline-icon is-info" aria-hidden="true">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                                <div class="cm-student-record__timeline-main">
                                    <h3 class="cm-student-record__timeline-title">
                                        <span><?= htmlspecialchars((string) ($item->lib_niv_etude ?? 'Inscription'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php cm_student_record_badge((string) $statut); ?>
                                    </h3>
                                    <div class="cm-student-record__timeline-meta">
                                        <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($item->date_inscription ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><i class="fas fa-receipt" aria-hidden="true"></i>Versement #<?= (int) ($item->num_versement ?? 1) ?></span>
                                        <span><i class="fas fa-coins" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_money($frais), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <div class="cm-student-record__timeline-block">
                                        <div class="cm-student-record__split">
                                            <span>Versé</span>
                                            <strong><?= htmlspecialchars(cm_student_record_money($verse), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="cm-student-record__progress">
                                            <span class="cm-student-record__progress-bar <?= $solde <= 0 ? 'is-success' : 'is-primary' ?>" style="width: <?= $progress ?>%"></span>
                                        </div>
                                        <div class="cm-student-record__split">
                                            <span>Solde</span>
                                            <strong><?= htmlspecialchars(cm_student_record_money($solde), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div class="cm-student-record__split">
                                            <span>M1 <?= htmlspecialchars(cm_student_record_decimal($item->moyenne_M1 ?? null, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span>M2 <?= htmlspecialchars(cm_student_record_decimal($item->moyenne_M2 ?? null, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php
                break;

            case 'soutenance':
                if (empty($soutenances)) {
                    cm_student_record_empty('Aucune soutenance', 'Aucune soutenance archivée pour cet étudiant.', 'fa-microphone-lines');
                    break;
                }
                ?>
                <section class="cm-student-record__section">
                    <div class="cm-student-record__metrics">
                        <?php cm_student_record_metric('Soutenances', (string) count($soutenances), 'fa-microphone-lines', 'primary'); ?>
                        <?php cm_student_record_metric('Dernière date', cm_student_record_date($latestSoutenance->date_soutenance ?? null), 'fa-calendar-day', 'success'); ?>
                        <?php cm_student_record_metric('Salle', cm_student_record_value($latestSoutenance->lib_salle ?? null), 'fa-door-open', 'info'); ?>
                        <?php cm_student_record_metric('Heure', cm_student_record_time($latestSoutenance->heure_soutenance ?? null), 'fa-clock', 'warning'); ?>
                    </div>

                    <div class="cm-student-record__cards">
                        <?php foreach ($soutenances as $soutenance): ?>
                            <article class="cm-card cm-student-record__panel">
                                <div class="cm-student-record__panel-head">
                                    <h3 class="cm-student-record__panel-title">
                                        <i class="fas fa-microphone-lines" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars((string) ($soutenance->theme_soutenance ?? 'Soutenance'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </h3>
                                    <?php cm_student_record_badge('Archivée', 'primary'); ?>
                                </div>
                                <div class="cm-student-record__panel-body">
                                    <?php cm_student_record_field_list([
                                        ['label' => 'Date', 'value' => cm_student_record_date($soutenance->date_soutenance ?? null)],
                                        ['label' => 'Heure', 'value' => cm_student_record_time($soutenance->heure_soutenance ?? null)],
                                        ['label' => 'Salle', 'value' => $soutenance->lib_salle ?? null],
                                        ['label' => 'Thème', 'value' => $soutenance->theme_soutenance ?? null],
                                    ]); ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php
                break;

            case 'documents':
                if (empty($documents)) {
                    cm_student_record_empty('Aucun document', 'Aucun fichier archivé disponible.', 'fa-folder-open');
                    break;
                }

                $latestDocument = $documents[0] ?? null;
                $documentTypes = [];
                foreach ($documents as $document) {
                    $documentTypes[(string) ($document->type ?? 'document')] = true;
                }
                ?>
                <section class="cm-student-record__section">
                    <div class="cm-student-record__metrics">
                        <?php cm_student_record_metric('Total', (string) count($documents), 'fa-folder-open', 'success'); ?>
                        <?php cm_student_record_metric('Dernier dépôt', cm_student_record_date($latestDocument->date ?? null), 'fa-calendar-day', 'primary'); ?>
                        <?php cm_student_record_metric('Types', (string) count($documentTypes), 'fa-tags', 'info'); ?>
                        <?php cm_student_record_metric('Consultables', (string) count($documents), 'fa-eye', 'warning'); ?>
                    </div>

                    <div class="cm-student-record__cards">
                        <?php foreach ($documents as $document): ?>
                            <?php $type = (string) ($document->type ?? 'document'); ?>
                            <article class="cm-student-record__document">
                                <div class="cm-student-record__document-main">
                                    <span class="cm-student-record__document-icon is-<?= htmlspecialchars(cm_student_record_document_tone($type), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                                        <i class="fas <?= htmlspecialchars(cm_student_record_document_icon($type), ENT_QUOTES, 'UTF-8') ?>"></i>
                                    </span>
                                    <div class="cm-student-record__document-copy">
                                        <h3 class="cm-student-record__document-title"><?= htmlspecialchars((string) ($document->titre ?? 'Document'), ENT_QUOTES, 'UTF-8') ?></h3>
                                        <div class="cm-student-record__document-meta">
                                            <span><?= cm_student_record_badge_html(cm_student_record_document_label($type), cm_student_record_document_tone($type)) ?></span>
                                            <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($document->date ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php cm_student_record_document_actions($type, $document->id ?? '', (string) ($document->titre ?? 'Document')); ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php
                break;

            case 'reclamations':
                if (empty($reclamations)) {
                    cm_student_record_empty('Aucune réclamation', 'Aucun dossier de réclamation archivé.', 'fa-life-ring');
                    break;
                }

                $resolvedCount = 0;
                $inProgressCount = 0;
                foreach ($reclamations as $reclamation) {
                    $statusText = (string) ($reclamation->libelle_statut_reclamation ?? $reclamation->statut_reclamation ?? '');
                    $statusType = cm_student_record_status_type($statusText);
                    if ($statusType === 'success') {
                        $resolvedCount++;
                    } elseif ($statusType === 'warning') {
                        $inProgressCount++;
                    }
                }
                ?>
                <section class="cm-student-record__section">
                    <div class="cm-student-record__metrics">
                        <?php cm_student_record_metric('Total', (string) count($reclamations), 'fa-life-ring', 'warning'); ?>
                        <?php cm_student_record_metric('Résolues', (string) $resolvedCount, 'fa-circle-check', $resolvedCount > 0 ? 'success' : 'info'); ?>
                        <?php cm_student_record_metric('En cours', (string) $inProgressCount, 'fa-spinner', $inProgressCount > 0 ? 'warning' : 'info'); ?>
                        <?php cm_student_record_metric('Dernière date', cm_student_record_date($reclamations[0]->date_creation ?? null), 'fa-calendar-day', 'primary'); ?>
                    </div>

                    <div class="cm-student-record__cards">
                        <?php foreach ($reclamations as $reclamation): ?>
                            <?php
                            $statusText = (string) ($reclamation->libelle_statut_reclamation ?? $reclamation->statut_reclamation ?? 'En attente');
                            $description = $reclamation->description_reclamation ?? $reclamation->description ?? null;
                            ?>
                            <article class="cm-card cm-student-record__panel">
                                <div class="cm-student-record__panel-head">
                                    <h3 class="cm-student-record__panel-title">
                                        <i class="fas fa-life-ring" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars((string) ($reclamation->objet_reclamation ?? $reclamation->titre_reclamation ?? 'Réclamation'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </h3>
                                    <?php cm_student_record_badge($statusText); ?>
                                </div>
                                <div class="cm-student-record__panel-body">
                                    <div class="cm-student-record__timeline-meta">
                                        <span><i class="far fa-calendar" aria-hidden="true"></i><?= htmlspecialchars(cm_student_record_date($reclamation->date_creation ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($reclamation->date_mise_a_jour)): ?>
                                            <span><i class="fas fa-rotate" aria-hidden="true"></i>Mise à jour <?= htmlspecialchars(cm_student_record_date($reclamation->date_mise_a_jour), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="cm-student-record__timeline-text"><?= htmlspecialchars(cm_student_record_value($description), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php
                break;
        endswitch; ?>
    </div>
</section>
