<?php
/**
 * P2.16 — Export en masse ZIP
 * Slug: export_masse_documents | Permission: documents
 *
 * Interface pour exporter en masse des documents sous forme d'archive ZIP
 * filtrée par année académique, filière et type de document.
 */

// @todo REFACTOR: Vue auto-contenue avec service + logique POST (couplage vue ↔ métier).
// Déplacer la logique dans layout.php (case 'export_masse_documents') et/ou un contrôleur dédié.
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/ExportMasseDocumentsService.php';

use CheckMaster\Services\ExportMasseDocumentsService;

$service = new ExportMasseDocumentsService(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;

// Traitement du formulaire d'export
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_zip') {
    try {
        cm_csrf_verify($_POST['csrf_token'] ?? '');
    } catch (Exception $e) {
        $_SESSION['error'] = 'Session expirée.';
        header('Location: ?page=export_masse_documents');
        exit;
    }

    if (!\canView('outils_direction') && !\canView('documents')) {
        $_SESSION['error'] = 'Accès non autorisé.';
        header('Location: ?page=export_masse_documents');
        exit;
    }

    $selectedAnnee = !empty($_POST['annee']) ? (int) $_POST['annee'] : null;
    $selectedFiliere = !empty($_POST['filiere']) ? (int) $_POST['filiere'] : null;
    $selectedType = (string) ($_POST['type_document'] ?? '');

    $result = $service->generateZip($selectedAnnee, $selectedFiliere, $selectedType);

    if ($result['success'] && $result['path'] && file_exists($result['path'])) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['path']));
        readfile($result['path']);
        unlink($result['path']); // Nettoyage
        exit;
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: ?page=export_masse_documents');
        exit;
    }
}

// Données pour les filtres
$annees = $service->getAnneesAcademiques();
$typesDocuments = $service->getDocumentTypes();

// Compter les documents disponibles pour chaque combinaison
$totalCount = $service->countDocuments();
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper" style="max-width: 800px; margin: 0 auto;">

        <?php cm_component('crud/form-pole', [
            'title' => 'Export en masse de documents',
            'icon'  => 'fa-file-archive',
            'content' => '<p class="cm-text-muted">Sélectionnez les critères pour générer une archive ZIP des documents (rapports, comptes rendus, etc.).</p>',
        ]); ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $_SESSION['success']]); ?>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $_SESSION['error']]); ?>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="cm-card">
            <div class="cm-card__body">
                <form method="POST" action="?page=export_masse_documents">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="action" value="generate_zip">

                    <div class="cm-form-group">
                        <label class="cm-form-label">Année académique</label>
                        <select name="annee" class="cm-form-control cm-form-select">
                            <option value="">-- Toutes les années --</option>
                            <?php foreach ($annees as $a): ?>
                            <option value="<?= (int) $a['id_annee_acad'] ?>"
                                <?= ((int) $a['id_annee_acad'] === $id_annee) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Filière</label>
                        <select name="filiere" class="cm-form-control cm-form-select">
                            <option value="">-- Toutes les filières --</option>
                            <?php foreach ($filieres = $service->getFilieres($id_annee) as $f): ?>
                            <option value="<?= (int) $f['id_filiere'] ?>">
                                <?= htmlspecialchars($f['lib_filiere'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Type de document</label>
                        <select name="type_document" class="cm-form-control cm-form-select">
                            <option value="">-- Tous les types --</option>
                            <?php foreach ($typesDocuments as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cm-form-group cm-mt-md">
                        <div class="cm-alert is-info cm-mb-md">
                            <i class="fas fa-info-circle"></i>
                            <span>Total documents disponibles : <strong><?= $totalCount ?></strong></span>
                        </div>
                    </div>

                    <?php cm_component('crud/form-actions', [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'type' => 'submit',
                                'label' => 'Générer et télécharger ZIP',
                                'icon' => 'fa-file-archive',
                                'class' => 'cm-btn is-primary is-lg cm-w-full',
                            ],
                        ],
                    ]); ?>
                </form>
            </div>
        </div>

        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">Information</h3>
            </div>
            <div class="cm-card__body cm-text-sm cm-text-muted">
                <p>L'export inclut les documents suivants :</p>
                <ul class="cm-ml-md cm-mt-sm" style="list-style:disc;">
                    <li>Rapports de stage (PDF, DOC, DOCX)</li>
                    <li>Comptes rendus de soutenance</li>
                    <li>Documents annexes uploadés</li>
                </ul>
                <p class="cm-mt-sm">Les fichiers sont organisés par étudiant dans l'archive ZIP.</p>
            </div>
        </div>
    </div>
</section>
