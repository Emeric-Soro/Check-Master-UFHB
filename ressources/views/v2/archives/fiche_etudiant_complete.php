<?php
/**
 * Fiche Étudiante Complète
 *
 * Variables attendues via $data :
 *   - profile : array
 *   - matricule : string
 *   - onglet_actif : string
 */
require_once __DIR__ . '/partials/_student_record_helpers.php';

$profile = $data['profile'] ?? [];
$matricule = $data['matricule'] ?? ($_GET['id'] ?? '');
$ongletActif = $data['onglet_actif'] ?? ($_GET['onglet'] ?? 'identite');
$isHubContext = ((string) ($_GET['page'] ?? '') === 'suivi_scolarite')
    || (((string) ($_GET['page'] ?? '') === 'parametres_generaux') && ((string) ($_GET['action'] ?? '') === 'suivi_scolarite'));
$ficheBaseUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=fiche_etudiant_complete'
    : '?page=fiche_etudiant_complete';
$timelineUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=timeline_parcours_etudiant&num_etu=' . urlencode((string) $matricule)
    : '?page=parcours_etudiant&id=' . urlencode((string) $matricule);
$returnUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=historique_inscriptions&num_etu=' . urlencode((string) $matricule)
    : '?page=archives_etudiants';

if (empty($profile)): ?>
    <div class="cm-alert cm-alert-danger">
        <i class="fas fa-exclamation-triangle cm-mr-2" aria-hidden="true"></i>
        Profil étudiant introuvable pour <strong><?= htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8') ?></strong>.
        <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">Retour</a>
    </div>
<?php return;
endif;

$identite = $profile['identite'] ?? [];
$etu = $identite['etudiant'] ?? null;
$niveau = $identite['niveau'] ?? null;
$notesStats = $profile['notes']['note_stats'] ?? [];
$inscriptions = $profile['inscriptions'] ?? [];
$stage = $profile['stage'] ?? null;
$rapportData = $profile['rapport']['rapport'] ?? null;
$candidatures = $profile['candidature'] ?? [];
$soutenances = $profile['soutenance']['soutenances'] ?? [];
$cr = $profile['cr'] ?? null;
$reclamations = $profile['reclamations'] ?? [];
$documents = $profile['documents'] ?? [];

$etudiantNom = $etu ? trim(($etu->nom_etu ?? '') . ' ' . ($etu->prenom_etu ?? '')) : 'Étudiant';
$etudiantCode = $etu->num_carte_etud ?? $matricule;
$promotion = $etu->promotion_etu ?? '';
$niveauLabel = $niveau->lib_niv_etude ?? null;
$email = $etu->email_etu ?? '';
$latestCandidature = $candidatures[0] ?? null;
$latestSoutenance = $soutenances[0] ?? null;
$moyenneGenerale = array_key_exists('moyenne', $notesStats) ? (float) ($notesStats['moyenne'] ?? 0) : null;
$documentsCount = count($documents);
$reclamationsCount = count($reclamations);
$inscriptionsCount = count($inscriptions);

$tabs = [
    'identite' => ['label' => 'Identité', 'icon' => 'fa-id-card', 'count' => 1],
    'inscriptions' => ['label' => 'Inscriptions', 'icon' => 'fa-file-signature', 'count' => $inscriptionsCount],
    'stage' => ['label' => 'Stage', 'icon' => 'fa-briefcase', 'count' => $stage ? 1 : 0],
    'rapport' => ['label' => 'Rapport', 'icon' => 'fa-file-lines', 'count' => $rapportData ? 1 : 0],
    'candidature' => ['label' => 'Candidature', 'icon' => 'fa-envelope-open-text', 'count' => count($candidatures)],
    'soutenance' => ['label' => 'Soutenance', 'icon' => 'fa-microphone-lines', 'count' => count($soutenances)],
    'cr' => ['label' => 'Compte rendu', 'icon' => 'fa-file-contract', 'count' => $cr ? 1 : 0],
    'notes' => ['label' => 'Notes', 'icon' => 'fa-chart-column', 'count' => count($profile['notes']['notes_unifies'] ?? [])],
    'reclamations' => ['label' => 'Réclamations', 'icon' => 'fa-life-ring', 'count' => $reclamationsCount],
    'documents' => ['label' => 'Documents', 'icon' => 'fa-folder-open', 'count' => $documentsCount],
];
?>

<section class="cm-student-record cm-student-record--complete">
    <div class="cm-card cm-student-record__hero">
        <div class="cm-student-record__hero-main">
            <div class="cm-student-record__hero-copy">
                <span class="cm-student-record__eyebrow">Fiche étudiante complète</span>
                <h1 class="cm-student-record__hero-title"><?= htmlspecialchars($etudiantNom, ENT_QUOTES, 'UTF-8') ?></h1>

                <p class="cm-student-record__hero-subtitle">
                    <span class="cm-student-record__code"><?= htmlspecialchars($etudiantCode, ENT_QUOTES, 'UTF-8') ?></span>
                </p>

                <div class="cm-student-record__meta">
                    <?php if ($promotion !== ''): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            <?= htmlspecialchars(FormattingUtils::formatPromotion($promotion), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($niveauLabel)): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                            <?= htmlspecialchars((string) $niveauLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>

                    <?php if (trim((string) $email) !== ''): ?>
                        <span class="cm-student-record__meta-item">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="cm-student-record__status-row">
                    <?php if (!empty($rapportData['statut_rapport'])): ?>
                        <?php cm_student_record_badge('Rapport ' . (string) $rapportData['statut_rapport']); ?>
                    <?php endif; ?>

                    <?php if (!empty($latestCandidature['statut_candidature'])): ?>
                        <?php cm_student_record_badge('Candidature ' . (string) $latestCandidature['statut_candidature']); ?>
                    <?php endif; ?>

                    <?php if (!empty($latestSoutenance['date_soutenance'])): ?>
                        <?php cm_student_record_badge('Soutenance ' . cm_student_record_date($latestSoutenance['date_soutenance']), 'primary'); ?>
                    <?php endif; ?>

                    <?php if ($cr): ?>
                        <?php cm_student_record_badge('Compte rendu disponible', 'success'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cm-student-record__hero-actions">
                <a href="<?= htmlspecialchars($timelineUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-primary is-sm" title="Voir le parcours chronologique">
                    <i class="fas fa-route" aria-hidden="true"></i>
                    <span>Parcours complet</span>
                </a>
                <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Retour</span>
                </a>
            </div>
        </div>

        <div class="cm-student-record__metrics">
            <?php cm_student_record_metric('Niveau', cm_student_record_value($niveauLabel), 'fa-layer-group', 'primary'); ?>
            <?php cm_student_record_metric('Moyenne', cm_student_record_decimal($moyenneGenerale, 2, ' /20'), 'fa-chart-line', cm_student_record_score_tone($moyenneGenerale)); ?>
            <?php cm_student_record_metric('Inscriptions', (string) $inscriptionsCount, 'fa-file-signature', $inscriptionsCount > 0 ? 'info' : 'warning'); ?>
            <?php cm_student_record_metric('Documents', (string) $documentsCount, 'fa-folder-open', $documentsCount > 0 ? 'success' : 'warning'); ?>
            <?php cm_student_record_metric('Réclamations', (string) $reclamationsCount, 'fa-life-ring', $reclamationsCount > 0 ? 'warning' : 'info'); ?>
        </div>
    </div>

    <nav class="cm-student-record__tabs" role="tablist" aria-label="Navigation fiche étudiante">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = $ongletActif === $key; ?>
            <a href="<?= htmlspecialchars($ficheBaseUrl . '&id=' . urlencode((string) $matricule) . '&onglet=' . urlencode($key), ENT_QUOTES, 'UTF-8') ?>"
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
        <?php
        $partialBase = __DIR__ . '/partials/';
        $partialFile = $partialBase . '_onglet_' . $ongletActif . '.php';

        if (file_exists($partialFile)):
            switch ($ongletActif):
                case 'identite':
                    $identite = $profile['identite'] ?? [];
                    include $partialFile;
                    break;
                case 'inscriptions':
                    $inscriptions = $profile['inscriptions'] ?? [];
                    include $partialFile;
                    break;
                case 'stage':
                    $stage = $profile['stage'] ?? null;
                    include $partialFile;
                    break;
                case 'rapport':
                    $rapport = $profile['rapport'] ?? [];
                    include $partialFile;
                    break;
                case 'candidature':
                    $candidatures = $profile['candidature'] ?? [];
                    include $partialFile;
                    break;
                case 'soutenance':
                    $soutenance = $profile['soutenance'] ?? [];
                    include $partialFile;
                    break;
                case 'cr':
                    $cr = $profile['cr'] ?? null;
                    include $partialFile;
                    break;
                case 'notes':
                    $notes = $profile['notes'] ?? [];
                    include $partialFile;
                    break;
                case 'reclamations':
                    $reclamations = $profile['reclamations'] ?? [];
                    include $partialFile;
                    break;
                case 'documents':
                    $documents = $profile['documents'] ?? [];
                    include $partialFile;
                    break;
                default:
                    cm_student_record_empty('Onglet indisponible', 'Le contenu demandé n’existe pas.', 'fa-circle-exclamation');
                    break;
            endswitch;
        else:
            cm_student_record_empty('Onglet indisponible', 'Le contenu demandé n’est pas encore disponible.', 'fa-circle-exclamation');
        endif;
        ?>
    </div>
</section>
