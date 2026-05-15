<?php
// Récupérer les données des rapports depuis le contrôleur
$rapports = $GLOBALS['rapports'] ?? [];
$nbRapports = $GLOBALS['nbRapports'] ?? 0;
$statsRapports = $GLOBALS['statsRapports'] ?? [];
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$academicYearLabels = [];
foreach (\AcademicYear::fetchAll(Database::getConnection()) as $academicYear) {
    $academicYearLabels[(int) ($academicYear['id'] ?? 0)] = (string) ($academicYear['label'] ?? '');
}

// Charger le modèle Valider pour les décisions
require_once __DIR__ . '/../../app/models/Valider.php';

// Fonction pour obtenir la classe CSS du statut
function getStatutClass($statut)
{
    switch ($statut) {
        case 'valide':
            return 'text-green-500 ';
        case 'rejete':
            return 'text-red-500 ';
        case 'en_cours':
            return 'text-blue-500 ';
        case 'en_attente':
            return 'text-yellow-500 ';
        default:
            return 'text-gray-500 ';
    }
}

// Fonction pour traduire le statut
function traduireStatut($statut)
{
    switch ($statut) {
        case 'valide':
            return 'Validé';
        case 'rejete':
            return 'Rejeté';
        case 'en_cours':
            return 'En cours';
        case 'en_attente':
            return 'En attente';
        default:
            return ucfirst($statut);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des rapports étudiants</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1a5276 0%, #2471a3 100%);
            --success-gradient: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            --warning-gradient: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
            --danger-gradient: linear-gradient(135deg, #e74c3c 0%, #cb4335 100%);
            --info-gradient: linear-gradient(135deg, #3498db 0%, #2e86c1 100%);
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        body {
            min-height: 100vh;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 0.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.yellow {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        }

        .stat-card.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-card.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .search-container {
            position: relative;
            background: rgba(223, 242, 255, 0.92);
            border-radius: 14px;
            padding: 0.3rem 0.4rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 460px;
        }

        .search-input {
            width: 100%;
            min-height: 36px;
            padding: 0.55rem 0.75rem 0.55rem 2.25rem;
            border: none;
            border-radius: 10px;
            background: transparent;
            font-size: 0.86rem;
            color: #374151;
            outline: none;
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .search-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 0.92rem;
        }

        .verification-comment {
            width: 100%;
            min-height: 72px;
            padding: 0.45rem 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.84rem;
            resize: vertical;
        }

        .table-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table-header {
            color: white;
            padding: 1rem;
        }

        .table-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .table-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background: linear-gradient(135deg, #DFF2FF 0%, #e2e8f0 100%);
            color: #374151;
            font-weight: 600;
            padding: 1rem;
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.9rem;
            text-transform: capitalize;
        }

        .table td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, #DFF2FF 0%, #e2e8f0 100%);
            transform: scale(1.01);
        }


        .action-btn {
            padding: 0.45rem 0.78rem;
            border: none;
            border-radius: 12px;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 84px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-validate {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-validate:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        .btn-detail {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-detail:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
        }

        .btn-traiter {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-traiter:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .modal {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(223, 242, 255, 0.97);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        /* Désactiver le scroll quand les modals sont ouvertes */
        body.modal-open {
            overflow: hidden;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.3rem;
            }

            .action-btn {
                min-width: 80px;
                padding: 0.5rem 1rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body class="min-h-screen p-4 md:p-8" style="background-color: #DFF2FF;">
    <?php
    // Afficher les messages de session
    if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $messageType = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);

        echo '<div id="notification" class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ' .
            (($messageType === 'success') ? 'bg-green-500 text-white' :
                (($messageType === 'error') ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white')) . '">';
        echo '<div class="flex items-center">';
        echo '<i class="fas ' . (($messageType === 'success') ? 'fa-check-circle' :
            (($messageType === 'error') ? 'fa-exclamation-circle' :
                'fa-info-circle')) . ' mr-2"></i>';
        echo '<span>' . htmlspecialchars($message) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<script>
            setTimeout(function() {
                const notification = document.getElementById("notification");
                if (notification) {
                    notification.style.transform = "translateX(full)";
                    setTimeout(function() {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 3000);
        </script>';
    }
    ?>
    <div class="max-w-7xl mx-auto cm-prd3-screen cm-prd3-crud-screen">
        <!-- Header Section -->
        <div class="glass-card rounded-2xl p-6 md:p-8 mb-8 fade-in">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clipboard-check text-2xl text-white"></i>
                    </div>
                    <div>

                        <p class="text-gray-600 mt-2 text-lg">
                            Gérez et validez les rapports soumis par les étudiants
                        </p>
                    </div>
                </div>

                <!-- Statistics Cards -->

            </div>
        </div>

        <!-- Search Section -->
        <?php if ($allYearsSelected): ?>
            <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                Affichage global sur toutes les années. Les décisions restent limitées à l'année active
                <strong><?= htmlspecialchars($writableYearLabel, ENT_QUOTES, 'UTF-8') ?></strong>.
            </div>
        <?php endif; ?>

        <?php cm_toolbar([
            'screen' => 'verification_candidatures',
            'id_prefix' => 'verif_cand',
            'search_value' => $_GET['search'] ?? '',
            'limit' => 10,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>


        <!-- Table Section -->
        <div class="table-container fade-in">
            <div class="table-header bg-green-600">
                
                <p>Vérifiez, validez ou rejetez les rapports soumis par les étudiants</p>
            </div>

            <div class="cm-table-wrapper">
                <table id="rapportsTable" class="table cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th"><i class="fas fa-user-graduate mr-2"></i>Étudiant</th>
                            <th class="cm-data-table__th"><i class="fas fa-file-lines mr-2"></i>Rapport</th>
                            <th class="cm-data-table__th"><i class="fas fa-lightbulb mr-2"></i>Thème</th>
                            <th class="cm-data-table__th"><i class="fas fa-calendar-alt mr-2"></i>Promotion</th>
                            <th class="cm-data-table__th"><i class="fas fa-calendar-day mr-2"></i>Date de dépôt</th>
                            <th class="cm-data-table__th"><i class="fas fa-check-circle mr-2"></i>Approbation</th>
                            <th class="cm-data-table__th is-center"><i class="fas fa-cogs mr-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rapports)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-file-circle-xmark"></i>

                                        <p class="text-gray-500">Les rapports soumis par les étudiants apparaîtront ici</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rapports as $i => $rapport): ?>
                                <?php
                                $promotionLabel = trim((string) ($rapport->promotion_etu ?? ''));
                                if ($promotionLabel === '' && !empty($rapport->id_annee_acad)) {
                                    $promotionLabel = $academicYearLabels[(int) $rapport->id_annee_acad] ?? '-';
                                }
                                $promotionLabel = \FormattingUtils::formatPromotion($promotionLabel);
                                ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <div class="font-semibold">
                                                    <?= htmlspecialchars($rapport->nom_etu . ' ' . $rapport->prenom_etu) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">Étudiant</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($rapport->nom_rapport) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">Rapport de master</div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="italic text-blue-700 max-w-xs truncate">
                                            <?= htmlspecialchars($rapport->theme_rapport) ?>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="font-semibold text-gray-700">
                                            <?= htmlspecialchars($promotionLabel !== '' ? $promotionLabel : '-', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($rapport->date_depot)) ?></span>
                                        </div>
                                    </td>
                                    <?php
                                    // Récupérer l'approbation la plus récente si elle existe
                                    $approb = null;
                                    try {
                                        $apprList = Valider::getByRapport($rapport->id_rapport);
                                        if (!empty($apprList)) {
                                            $last = end($apprList);
                                            $approb = is_array($last) ? ($last['decision_validation'] ?? null) : ($last->decision_validation ?? null);
                                        }
                                    } catch (Exception $e) {
                                        $approb = null;
                                    }
                                    ?>
                                    <td class="text-center">
                                        <?php if ($approb === 'valider'): ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-semibold">
                                                <i class="fas fa-check mr-2"></i> Approuvé
                                            </span>
                                        <?php elseif ($approb === 'desapprouve' || $approb === 'rejete'): ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-800 text-sm font-semibold">
                                                <i class="fas fa-times mr-2"></i> Désapprouvé
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-sm font-semibold">
                                                <i class="fas fa-clock mr-2"></i> En attente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cm-data-table__td is-center"><div class="flex items-center justify-center gap-2 action-buttons">
<?php if (canEdit()): ?>
                                            <button onclick="toggleInlineForm(<?= $rapport->id_rapport ?>, 'valider')" class="action-btn btn-validate" title="Approuver">
                                                <i class="fas fa-check mr-1"></i> Approuver
                                            </button>
                                            <button onclick="toggleInlineForm(<?= $rapport->id_rapport ?>, 'rejeter')" class="action-btn btn-reject" title="Rejeter">
                                                <i class="fas fa-times mr-1"></i> Rejeter
                                            </button>
<?php endif; ?>
                                            <a href="?page=gestion_dossiers_candidatures&action=telecharger_pdf&id_rapport=<?= urlencode((string) $rapport->id_rapport) ?>"
                                                class="action-btn btn-pdf" title="PDF">
                                                <i class="fas fa-file-pdf mr-1"></i> PDF
                                            </a>
                                            <a href="?page=gestion_dossiers_candidatures&id_rapport=<?= urlencode((string) $rapport->id_rapport) ?>"
                                                class="action-btn btn-traiter" title="Traiter dans gestion_dossiers_candidatures">
                                                <i class="fas fa-pen mr-1"></i> Traiter
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Formulaire inline pour validation/rejet -->
                                <tr id="inline-form-<?= $rapport->id_rapport ?>" class="hidden">
                                    <td colspan="7" class="p-4 bg-gray-50">
                                        <!-- Formulaire de validation -->
                                        <form id="valider-form-<?= $rapport->id_rapport ?>" method="POST" action="?page=verification_candidatures_soutenance" class="hidden mb-0">
                                            <input type="hidden" name="valider" value="1">
                                            <input type="hidden" name="id_rapport" value="<?= $rapport->id_rapport ?>">
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-check-circle text-green-600"></i>
                                                    <span class="font-semibold text-green-700">Approuver le rapport</span>
                                                </div>
                                                <textarea name="commentaire" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm" placeholder="Commentaire (obligatoire)..." required></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                                        <i class="fas fa-check mr-1"></i> Confirmer l'approbation
                                                    </button>
                                                    <button type="button" onclick="closeInlineForm(<?= $rapport->id_rapport ?>)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                                                        Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        <!-- Formulaire de rejet -->
                                        <form id="rejeter-form-<?= $rapport->id_rapport ?>" method="POST" action="?page=verification_candidatures_soutenance" class="hidden mb-0">
                                            <input type="hidden" name="rejeter" value="1">
                                            <input type="hidden" name="id_rapport" value="<?= $rapport->id_rapport ?>">
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-times-circle text-red-600"></i>
                                                    <span class="font-semibold text-red-700">Rejeter le rapport</span>
                                                </div>
                                                <textarea name="commentaire" rows="2" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm" placeholder="Commentaire (obligatoire)..." required></textarea>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                                        <i class="fas fa-times mr-1"></i> Confirmer le rejet
                                                    </button>
                                                    <button type="button" onclick="closeInlineForm(<?= $rapport->id_rapport ?>)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                                                        Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Toolbar handles search, select-all, deselect-all, print, export internally.
        // This view only needs to handle inline forms and toolbar events.

        // Gestion des formulaires inline
        var currentOpenFormId = null;

        function toggleInlineForm(idRapport, action) {
            var formRow = document.getElementById('inline-form-' + idRapport);
            var validerForm = document.getElementById('valider-form-' + idRapport);
            var rejeterForm = document.getElementById('rejeter-form-' + idRapport);

            // Fermer le formulaire precedemment ouvert
            if (currentOpenFormId && currentOpenFormId !== idRapport) {
                closeInlineForm(currentOpenFormId);
            }

            // Basculer la visibilite
            if (formRow.classList.contains('hidden')) {
                formRow.classList.remove('hidden');
                currentOpenFormId = idRapport;
            }

            // Afficher le bon formulaire
            if (action === 'valider') {
                validerForm.classList.remove('hidden');
                rejeterForm.classList.add('hidden');
            } else {
                validerForm.classList.add('hidden');
                rejeterForm.classList.remove('hidden');
            }
        }

        function closeInlineForm(idRapport) {
            var formRow = document.getElementById('inline-form-' + idRapport);
            var validerForm = document.getElementById('valider-form-' + idRapport);
            var rejeterForm = document.getElementById('rejeter-form-' + idRapport);

            formRow.classList.add('hidden');
            validerForm.classList.add('hidden');
            rejeterForm.classList.add('hidden');

            if (currentOpenFormId === idRapport) {
                currentOpenFormId = null;
            }
        }

        // Toolbar delete event
        document.addEventListener('cm:toolbar:delete', function (event) {
            if (!event.detail || !event.detail.toolbar) return;
            if (event.detail.toolbar.id !== 'verif_cand_toolbar') return;
            var tableBody = document.querySelector('#rapportsTable tbody');
            if (!tableBody) return;
            var checkedRows = Array.from(tableBody.querySelectorAll('tr')).filter(function (row) {
                var cb = row.querySelector('input[type="checkbox"]');
                return cb && cb.checked && !row.querySelector('textarea');
            });
            if (checkedRows.length === 0) return;
            checkedRows.forEach(function (row) { row.remove(); });
        });

        // Toolbar limit change event (server-side pagination)
        document.addEventListener('cm:toolbar:limit:change', function (event) {
            if (!event.detail || !event.detail.toolbar) return;
            if (event.detail.toolbar.id !== 'verif_cand_toolbar') return;
            event.preventDefault();
            var limit = event.detail.limit || '10';
            var url = new URL(window.location.href);
            url.searchParams.set('limit', limit);
            url.searchParams.set('p', '1');
            if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                window.CM.ajax.load(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    </script>
</body>

</html>

