<?php
// NOTE : Ce fichier utilise un endpoint AJAX 'resume_candidature_ajax.php' à créer pour charger dynamiquement le résumé de candidature depuis la base.
// Récupérer les données du contrôleur
$candidatures = $GLOBALS['candidatures_soutenance'] ?? [];
$examiner = $GLOBALS['examiner'] ?? null;
$etape = $GLOBALS['etape'] ?? 1;
$etudiantData = $GLOBALS['etudiantData'] ?? null;
$etapeData = $GLOBALS['etapeData'] ?? null;

// Démarrer la session pour accéder aux étapes validées/rejetées
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$statutFiltre = $_GET['statut'] ?? 'all';
if ($statutFiltre !== 'all') {
    $candidatures = array_filter($candidatures, function($c) use ($statutFiltre) {
        return $c['statut_candidature'] === $statutFiltre;
    });
}

// Charger les résumés de candidatures pour les étudiants traités
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/config/database.php';
$resumes_candidatures = [];
foreach ($candidatures as $c) {
    if ($c['statut_candidature'] !== 'En attente') {
        $resume = (new Etudiant(Database::getConnection()))->getResumeCandidature($c['id_candidature']);
        $resumes_candidatures[$c['id_candidature']] = $resume;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Candidatures de Soutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    :root {
        --primary: #6F42C1;
        /* Purple color from image */
        --secondary: #E9ECEF;
        --success: #10B981;
        --danger: #EF4444;
        --gray: #6B7280;
        --light-gray-bg: #F3F4F6;
        --white-bg: #FFFFFF;
        --border-color: #E5E7EB;
        --text-color-dark: #1F2937;
        --text-color-medium: #4B5563;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--light-gray-bg);
        margin: 0;

        color: var(--text-color-medium);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .main-content {
        background: var(--white-bg);
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        padding: 20px;
    }

    .filters {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 200px;
        /* Minimum width for search input */
    }

    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
    }

    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .filters select {
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        background-color: var(--white-bg);
        cursor: pointer;
    }


    .candidate-list {
        display: grid;
        gap: 15px;
    }

    .candidate-item {
        background: #F9FAFB;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        /* Allow wrapping on smaller screens */
        gap: 10px;
        /* Spacing between flex items */
        transition: box-shadow 0.2s;
    }

    .candidate-item:hover {
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.10);
        border-color: var(--primary);
    }

    .candidate-info h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--text-color-dark);
    }

    .candidate-info p {
        margin: 5px 0 0;
        font-size: 15px;
        color: var(--gray);
    }

    .btn-examine {
        background: var(--primary);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-examine:hover {
        background: #5a37a8;
        /* Darker shade of purple */
    }

    /* Modal */
    .modal {
        display: none;
        /* Hidden by default */
        position: fixed;
        /* Stay in place */
        z-index: 1000;
        /* Sit on top */
        left: 0;
        top: 0;
        width: 100%;
        /* Full width */
        height: 100%;
        /* Full height */
        overflow: auto;
        /* Enable scroll if needed */
        background-color: rgba(0, 0, 0, 0.4);
        /* Black w/ opacity */
        justify-content: center;
        /* Center horizontal */
        align-items: center;
        /* Center vertical */
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 30px;
        border-radius: 10px;
        max-width: 900px;
        width: 95%;
        max-height: 95vh;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .close-button {
        color: #aaa;
        position: absolute;
        top: 15px;
        right: 25px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-button:hover,
    .close-button:focus {
        color: #000;
        text-decoration: none;
    }

    .modal-title {
        text-align: center;
        font-size: 24px;
        font-weight: 600;
        color: var(--text-color-dark);
        margin-bottom: 20px;
    }

    .step-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        position: relative;
    }

    .step-indicator::before {
        content: '';
        position: absolute;
        top: 18px;
        left: 10%;
        /* Adjusted for wider spacing */
        right: 10%;
        /* Adjusted for wider spacing */
        height: 4px;
        background-color: var(--secondary);
        z-index: 1;
    }

    .step {
        flex: 1;
        text-align: center;
        z-index: 2;
        position: relative;
        /* Needed for pseudo-element */
    }

    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        right: -50%;
        /* Position the line to the right of the step */
        width: 100%;
        /* Extend the line to the next step */
        height: 4px;
        background-color: var(--secondary);
        z-index: -1;
        /* Behind the steps */
    }

    .step.completed:not(:last-child)::after {
        background-color: var(--primary);
        /* Highlight line if step is completed */
    }


    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--secondary);
        color: var(--white-bg);
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 5px;
        transition: background-color 0.3s ease;
    }

    .step.active-scolarite .step-icon {
        background-color: #a78bfa !important;
        /* violet */
        color: #fff;
        box-shadow: 0 0 0 3px #f3e8ff;
    }

    .step.active-stage .step-icon {
        background-color: #38bdf8 !important;
        /* bleu */
        color: #fff;
        box-shadow: 0 0 0 3px #e0f2fe;
    }

    .step.active-semestre .step-icon {
        background-color: #fde047 !important;
        /* jaune */
        color: #fff;
        box-shadow: 0 0 0 3px #fef9c3;
    }

    .step.active-resume .step-icon {
        background-color: #22c55e !important;
        /* vert */
        color: #fff;
        box-shadow: 0 0 0 3px #dcfce7;
    }

    .step-label {
        font-size: 12px;
        color: var(--gray);
    }

    .step.active .step-label,
    .step.completed .step-label,
    .step.rejected .step-label {
        color: var(--text-color-dark);
        font-weight: 500;
    }

    .modal-body {
        flex-grow: 1;
        /* Allows body to take up available space */
        overflow-y: scroll;
        /* Force scrollbar to always show */
        max-height: 70vh;
        /* Limit height for scrolling */
        padding-right: 15px;
        /* Add padding for scrollbar */
        scrollbar-width: thin;
        /* For Firefox */
        scrollbar-color: #888 #f1f1f1;
        /* For Firefox */
    }

    /* Custom scrollbar for Webkit browsers */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .step-content {
        display: none;
        /* Hide all step content by default */
    }

    .step-content.active {
        display: block;
        /* Show active step content */
    }

    .info-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #F9FAFB;
        border-radius: 8px;
    }

    .info-section h3 {
        font-size: 18px;
        color: var(--text-color-dark);
        margin-top: 0;
        margin-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 5px;
    }

    .info-item {
        margin-bottom: 10px;
    }

    .info-item strong {
        display: inline-block;
        width: 180px;
        /* Adjust as needed */
        color: var(--text-color-medium);
    }

    .verification-status i {
        margin-right: 5px;
    }

    .verification-status .fa-check-circle {
        color: var(--success);
    }

    .verification-status .fa-times-circle {
        color: var(--danger);
    }

    .verification-status .fa-clock {
        color: var(--gray);
    }


    .modal-footer {
        display: flex;
        justify-content: space-between;
        /* Space out buttons */
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        color: white;
    }

    .btn-secondary {
        background: var(--gray);
    }

    .btn-secondary:hover {
        background: #4B5563;
        /* Darker gray */
    }

    .btn-primary {
        background: var(--primary);
    }

    .btn-primary:hover {
        background: #5a37a8;
    }

    .btn-validate,
    .btn-reject {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        color: white;
    }

    .btn-validate {
        background: var(--success);
    }

    .btn-validate:hover {
        background: #059669;
    }

    .btn-reject {
        background: var(--danger);
    }

    .btn-reject:hover {
        background: #DC2626;
    }

    .modal-footer .left-buttons {
        display: flex;
        gap: 10px;
    }


    /* History Table Styles */
    .history-section {
        margin-top: 30px;
        padding: 20px;
        background: var(--white-bg);
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .history-section h2 {
        margin-top: 0;
        color: var(--text-color-dark);
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .history-table th,
    .history-table td {
        text-align: left;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
    }

    .history-table th {
        background-color: #F9FAFB;
        font-size: 15px;
        color: var(--text-color-dark);
    }

    .history-table td {
        font-size: 15px;
    }

    .history-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-badge.en_attente {
        background-color: #FEF3C7;
        color: #92400E;
    }

    .status-badge.validee {
        background-color: #D1FAE5;
        color: #065F46;
    }

    .status-badge.rejetee {
        background-color: #FEE2E2;
        color: #991B1B;
    }

    .table-container {
        margin-top: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .table-header {
        background-color: #f8fafc;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .history-table th,
    .history-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .history-table th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #475569;
    }

    .history-table tr:hover {
        background-color: #f8fafc;
    }

    .action-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .action-validation {
        background-color: #dcfce7;
        color: #166534;
    }

    .action-rejet {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .action-verification {
        background-color: #fef3c7;
        color: #92400e;
    }

    /* Styles pour le résumé final */
    .resume-final {
        margin-top: 20px;
    }

    .decision-finale {
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .decision-finale.validee {
        background-color: #D1FAE5;
        border: 2px solid var(--success);
        color: #065F46;
    }

    .decision-finale.rejetee {
        background-color: #FEE2E2;
        border: 2px solid var(--danger);
        color: #991B1B;
    }

    .decision-finale h4 {
        margin: 0 0 10px 0;
        font-size: 20px;
        font-weight: 600;
    }

    .decision-finale p {
        margin: 0;
        font-size: 16px;
    }

    .resume-etapes {
        margin-top: 20px;
    }

    .resume-etapes h4 {
        margin-bottom: 15px;
        color: var(--text-color-dark);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 5px;
    }

    .etape-resume {
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid var(--gray);
    }

    .etape-resume.validé {
        background-color: #F0FDF4;
        border-left-color: var(--success);
    }

    .etape-resume.rejeté {
        background-color: #FEF2F2;
        border-left-color: var(--danger);
    }

    .etape-resume h5 {
        margin: 0 0 10px 0;
        color: var(--text-color-dark);
        font-size: 16px;
    }

    .etape-resume h5 i {
        margin-right: 8px;
        color: var(--primary);
    }

    .etape-details p {
        margin: 5px 0;
        font-size: 14px;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge.validé {
        background-color: var(--success);
        color: white;
    }

    .badge.rejeté {
        background-color: var(--danger);
        color: white;
    }

    .badge.non-évalué {
        background-color: var(--gray);
        color: white;
    }

    .email-notification {
        margin-top: 20px;
        padding: 15px;
        background-color: #EFF6FF;
        border: 1px solid #3B82F6;
        border-radius: 8px;
        color: #1E40AF;
    }

    .email-notification p {
        margin: 0;
        font-size: 14px;
    }

    .email-notification i {
        margin-right: 8px;
        color: #3B82F6;
    }

    /* Spécifique à l'étape 4 (Résumé) : agrandir la modal et désactiver le scroll */
    .modal-content.resume-step {
        max-width: 850px;
        max-height: 98vh;
        height: auto;
        overflow-y: scroll;
        display: flex;
        flex-direction: column;
    }

    .modal-content.resume-step .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        min-height: 0;
        padding-right: 15px;
        max-height: none;
    }

    .step-content.active-scolarite {
        background: #f3e8ff;
        /* violet très clair */
    }

    .step-content.active-stage {
        background: #e0f2fe;
        /* bleu très clair */
    }

    .step-content.active-semestre {
        background: #fef9c3;
        /* jaune très clair */
    }

    .step-content.active-resume {
        background: #dcfce7;
        /* vert très clair */
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .main-content,
        .main-content * {
            visibility: visible;
        }

        .filters,
        .btn,
        .btn-examine,
        .btn-details,
        .table-header,
        .modal,
        .modal *,
        .candidate-list,
        .candidate-item,
        #historiqueDetailsModal {
            display: none !important;
        }

        .table-container,
        .history-table {
            box-shadow: none !important;
            background: white !important;
            color: black !important;
        }

        .history-table th,
        .history-table td {
            border: 1px solid #222 !important;
            background: white !important;
            color: #222 !important;
        }

        .status-badge,
        .badge {
            color: #222 !important;
            background: #eee !important;
            border: 1px solid #bbb !important;
        }

        /* Masquer la colonne Action (Voir détails) */
        .history-table th:last-child,
        .history-table td:last-child {
            display: none !important;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="main-content">
            <h1 class="text-2xl font-bold mb-6">Gestion des Candidatures de Soutenance</h1>

            <!-- Message d'erreur pour les étudiants sans informations de stage -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'no_stage_info'): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <strong>Erreur :</strong> Cet étudiant n'a pas rempli ses informations de stage. Il ne peut pas être
                examiné tant qu'il n'a pas complété cette étape.
            </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" style="outline: none;" placeholder="Rechercher un étudiant...">
                </div>
                <form method="get" id="filterForm" style="margin:0;">
                    <input type="hidden" name="page" value="gestion_candidatures_soutenance">
                    <select name="statut" id="statusFilter" style="outline: none;"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?php if(($_GET['statut'] ?? 'all') === 'all') echo 'selected'; ?>>Tous les
                            statuts</option>
                        <option value="En attente"
                            <?php if(($_GET['statut'] ?? '') === 'En attente') echo 'selected'; ?>>En attente</option>
                        <option value="Validée" <?php if(($_GET['statut'] ?? '') === 'Validée') echo 'selected'; ?>>
                            Validée</option>
                        <option value="Rejetée" <?php if(($_GET['statut'] ?? '') === 'Rejetée') echo 'selected'; ?>>
                            Rejetée</option>
                    </select>
                </form>
            </div>

            <!-- Liste des candidatures -->
            <div class="candidate-list">
                <?php if (empty($candidatures)): ?>
                <div class="text-center text-gray-500 py-4">
                    Aucune candidature à afficher
                </div>
                <?php else: ?>
                <?php foreach ($candidatures as $candidature): ?>
                <div class="candidate-item" data-status="<?php echo $candidature['statut_candidature']; ?>">
                    <div class="candidate-info">
                        <h3><?php echo htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']); ?>
                        </h3>
                        <p>Numéro étudiant: <?php echo htmlspecialchars($candidature['num_etu']); ?></p>
                        <p>Date de demande: <?php echo date('d/m/Y', strtotime($candidature['date_candidature'])); ?>
                        </p>
                        <p>Statut: <span class="status-badge <?php echo $candidature['statut_candidature']; ?>">
                                <?php echo ucfirst($candidature['statut_candidature']); ?>
                            </span></p>
                    </div>
                    <?php if ($candidature['statut_candidature'] === 'En attente'): ?>
                    <button class="btn-examine"
                        onclick="window.location.href='?page=gestion_candidatures_soutenance&examiner=<?php echo $candidature['num_etu']; ?>&etape=1'">
                        Examiner
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Table d'historique des candidatures examinées -->
            <div class="table-container" style="margin-top: 2rem;">
                <div class="table-header"
                    style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <h2 class="table-title">Historique des candidatures examinées</h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="searchHistoriqueInput" placeholder="Rechercher dans l'historique..."
                            style="padding: 8px 12px; border-radius: 6px; border: 1px solid #E5E7EB; font-size: 14px;">
                        <button class="btn btn-secondary" onclick="printHistoriqueTable()"><i class="fas fa-print"></i>
                            Imprimer</button>
                        <button class="btn btn-primary" onclick="exportHistoriqueCSV()"><i class="fas fa-file-csv"></i>
                            Exporter</button>
                    </div>
                </div>
                <table class="history-table" id="historiqueCandidaturesTable">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidatures as $candidature): ?>
                        <?php if ($candidature['statut_candidature'] !== 'En attente'): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']); ?>
                            </td>
                            <td><span
                                    class="status-badge <?php echo strtolower($candidature['statut_candidature']); ?>"><?php echo ucfirst($candidature['statut_candidature']); ?></span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($candidature['date_traitement'] ?? $candidature['date_candidature'])); ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-details"
                                    data-idcandidature="<?php echo $candidature['id_candidature']; ?>">Voir
                                    détails</button>
                                <div id="resume-candidature-<?php echo $candidature['id_candidature']; ?>"
                                    style="display:none;">
                                    <?php if (!empty($resumes_candidatures[$candidature['id_candidature']])): 
                                        $resume = $resumes_candidatures[$candidature['id_candidature']]; ?>
                                    <div class="resume-final">
                                        <div
                                            class="decision-finale <?php echo $resume['decision'] === 'Validée' ? 'validee' : 'rejetee'; ?>">
                                            <h4>Décision finale : <?php echo $resume['decision']; ?></h4>
                                            <p>Date de traitement : <?php echo $resume['date_enregistrement']; ?></p>
                                            <?php if ($resume['decision'] === 'Validée'): ?>
                                            <p>🎉 Félicitations ! Candidature validée.</p>
                                            <?php else: ?>
                                            <p>❌ Candidature rejetée. Voir détails ci-dessous.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="resume-etapes">
                                            <h4>Détail par étape :</h4>
                                            <?php $r = $resume['resume_json']; ?>
                                            <?php if (!empty($r['scolarite'])): ?>
                                            <div class="etape-resume <?php echo $r['scolarite']['validation']; ?>">
                                                <h5><i class='fas fa-money-check'></i> Scolarité</h5>
                                                <p><strong>Validation :</strong> <span
                                                        class="badge <?php echo $r['scolarite']['validation']; ?>"><?php echo strtoupper($r['scolarite']['validation']); ?></span>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['stage'])): ?>
                                            <div class="etape-resume <?php echo $r['stage']['validation']; ?>">
                                                <h5><i class='fas fa-briefcase'></i> Stage</h5>
                                                <p><strong>Validation :</strong> <span
                                                        class="badge <?php echo $r['stage']['validation']; ?>"><?php echo strtoupper($r['stage']['validation']); ?></span>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['semestre'])): ?>
                                            <div class="etape-resume <?php echo $r['semestre']['validation']; ?>">
                                                <h5><i class='fas fa-graduation-cap'></i> Semestre</h5>
                                                <p><strong>Validation :</strong> <span
                                                        class="badge <?php echo $r['semestre']['validation']; ?>"><?php echo strtoupper($r['semestre']['validation']); ?></span>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <p>Aucun résumé trouvé pour cet étudiant.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal détails historique -->
            <div id="historiqueDetailsModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width:700px; max-height:80vh; overflow-y:auto;">
                    <span class="close-button" onclick="closeHistoriqueModal()">&times;</span>
                    <h2 class="modal-title">Résumé de l'examen de candidature</h2>
                    <div id="historiqueDetailsContent">
                        <!-- Contenu dynamique à charger côté serveur/JS -->
                        <p>Chargement...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'examen -->
    <?php if ($examiner && $etudiantData): ?>
    <div id="examinationModal" class="modal" style="display: flex;">
        <?php else: ?>
        <div id="examinationModal" class="modal" style="display: none;">
            <?php endif; ?>
            <div class="modal-content<?php echo ($etape == 4 ? ' resume-step' : ''); ?>">
                <a href="?page=gestion_candidatures_soutenance" class="close-button">&times;</a>
                <h2 class="modal-title">Examen de la Candidature -
                    <?php echo htmlspecialchars($etudiantData['nom_etu'] . ' ' . $etudiantData['prenom_etu']); ?></h2>
                <!--partie des step-icon-->
                <div class="step-indicator">
                    <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][1]) ? $_SESSION['etapes_validation'][$examiner][1] : ($etape == 1 ? 'active' : ''); ?>"
                        id="stepScolarite">
                        <div class="step-icon">
                            <i class="fas fa-money-check"></i>
                        </div>
                        <div class="step-label">Scolarité</div>
                    </div>
                    <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][2]) ? $_SESSION['etapes_validation'][$examiner][2] : ($etape == 2 ? 'active' : ''); ?>"
                        id="stepStage">
                        <div class="step-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="step-label">Stage</div>
                    </div>
                    <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][3]) ? $_SESSION['etapes_validation'][$examiner][3] : ($etape == 3 ? 'active' : ''); ?>"
                        id="stepSemestre">
                        <div class="step-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="step-label">Semestre</div>
                    </div>
                    <div class="step <?php echo $etape == 4 ? 'active' : ''; ?>" id="stepResume">
                        <div class="step-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="step-label">Résumé</div>
                    </div>
                </div>

                <?php if ($etapeData): ?>
                <div class="step-content active
                    <?php
                        if ($etape == 1) echo 'active-scolarite';
                        elseif ($etape == 2) echo 'active-stage';
                        elseif ($etape == 3) echo 'active-semestre';
                        elseif ($etape == 4) echo 'active-resume';
                    ?>">
                    <div class="info-section">
                        <?php if ($etape == 4): ?>
                        <!-- Résumé final -->
                        <h3>Résumé de l'évaluation</h3>
                        <div class="resume-final">
                            <?php 
                            $decision = 'Validée';
                            $rejets = 0;
                            foreach ($etapeData as $key => $data) {
                                if ($data['validation'] === 'rejeté') {
                                    $rejets++;
                                    $decision = 'Rejetée';
                                }
                            }
                            ?>
                            <div class="decision-finale <?php echo $decision === 'Validée' ? 'validee' : 'rejetee'; ?>">
                                <h4>Décision finale : <?php echo $decision; ?></h4>
                                <?php if ($decision === 'Validée'): ?>
                                <p>🎉 Félicitations ! Votre candidature a été validée. Vous pouvez procéder à votre
                                    soutenance.</p>
                                <?php else: ?>
                                <p>❌ Votre candidature a été rejetée. Veuillez corriger les problèmes identifiés
                                    ci-dessous.</p>
                                <?php endif; ?>
                            </div>

                            <div class="resume-etapes">
                                <h4>Détail par étape :</h4>



                                <!-- Scolarité -->
                                <div
                                    class="etape-resume <?php echo $etapeData['scolarite']['validation']; ?> flex gap-2 items-center">
                                    <div class="etape-details flex gap-2 items-center">
                                        <h5><i class="fas fa-money-check"></i> Scolarité</h5>
                                        <p><strong>Validation :</strong>
                                            <span class="badge <?php echo $etapeData['scolarite']['validation']; ?>">
                                                <?php echo strtoupper($etapeData['scolarite']['validation']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Stage -->
                                <div
                                    class="etape-resume <?php echo $etapeData['stage']['validation']; ?> flex gap-2 items-center">
                                    <div class="etape-details flex gap-2 items-center mb-2">
                                        <h5><i class="fas fa-briefcase"></i> Stage</h5>
                                        <p><strong>Validation :</strong>
                                            <span class="badge <?php echo $etapeData['stage']['validation']; ?>">
                                                <?php echo strtoupper($etapeData['stage']['validation']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Semestre -->
                                <div
                                    class="etape-resume <?php echo $etapeData['semestre']['validation']; ?> flex gap-2 items-center">

                                    <div class="etape-details flex gap-2 items-center mb-2">
                                        <h5><i class="fas fa-graduation-cap"></i> Semestre</h5>
                                        <p><strong>Validation :</strong>
                                            <span class="badge <?php echo $etapeData['semestre']['validation']; ?>">
                                                <?php echo strtoupper($etapeData['semestre']['validation']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                            </div>
                            <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
                            <div class="email-notification"
                                style="background-color: #D1FAE5; border: 1px solid var(--success); color: #065F46;">
                                <p><i class="fas fa-check-circle"></i> <strong>Email envoyé avec succès !</strong> Les
                                    résultats ont été envoyés à l'étudiant.</p>
                            </div>
                            <?php else: ?>
                            <div class="email-notification"
                                style="background-color: #EFF6FF; border: 1px solid #3B82F6; color: #1E40AF;">
                                <p><i class="fas fa-envelope"></i> Cliquez sur "Envoyer les résultats" pour notifier
                                    l'étudiant de la décision finale.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <!-- Contenu normal des étapes -->
                        <h3>
                            <?php 
                        switch($etape) {
                            case 1: echo 'Vérification de la scolarité'; break;
                            case 2: echo 'Vérification du stage'; break;
                            case 3: echo 'Vérification du semestre'; break;
                        }
                        ?>
                        </h3>

                        <?php if ($etape == 1): ?>
                        <div class="info-item">
                            <strong>Statut des paiements:</strong>
                            <span><?php echo htmlspecialchars($etapeData['status']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Montant total:</strong>
                            <span><?php echo htmlspecialchars($etapeData['montant']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Montant payé:</strong>
                            <span><?php echo htmlspecialchars($etapeData['montant_paye']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Dernier paiement:</strong>
                            <span><?php echo htmlspecialchars($etapeData['dernierPaiement']); ?></span>
                        </div>
                        <?php elseif ($etape == 2): ?>
                        <div class="info-item">
                            <strong>Entreprise :</strong>
                            <span><?php echo htmlspecialchars($etapeData['entreprise']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Sujet :</strong>
                            <span><?php echo htmlspecialchars($etapeData['sujet']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Période :</strong>
                            <span><?php echo htmlspecialchars($etapeData['periode']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Encadrant :</strong>
                            <span><?php echo htmlspecialchars($etapeData['encadrant']); ?></span>
                        </div>
                        <?php elseif ($etape == 3): ?>
                        <div class="info-item">
                            <strong>Semestre actuel:</strong>
                            <span><?php echo htmlspecialchars($etapeData['semestre']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Moyenne générale:</strong>
                            <span class="text-success">
                                <?php echo htmlspecialchars($etapeData['moyenne']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Unités validées:</strong>
                            <span class="text-success">
                                <?php echo htmlspecialchars($etapeData['unites']); ?>
                            </span>
                        </div>
                        <?php if (empty($etapeData['moyenne']) || $etapeData['moyenne'] == '0'): ?>
                        <div class="alert alert-warning mt-3"
                            style="background-color: #FEF3C7; border: 1px solid #F59E0B; color: #92400E; padding: 12px; border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note :</strong> Aucune note n'a été trouvée pour cet étudiant.
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal-footer">
                    <div class="left-buttons">
                        <?php if ($etape > 1 && $etape < 4): ?>
                        <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape - 1; ?>"
                            class="btn btn-secondary">
                            Précédent
                        </a>
                        <?php endif; ?>

                        <?php if ($etape < 4): ?>
                        <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape + 1; ?>"
                            class="btn btn-primary">
                            Suivant
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="right-buttons">
                        <?php if ($etape < 4): ?>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                            style="display: inline;">
                            <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                            <button type="submit" class="btn-reject">Rejeter</button>
                        </form>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                            style="display: inline;">
                            <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                            <button type="submit" class="btn-validate">
                                <?php echo $etape == 3 ? 'Terminer l\'évaluation' : 'Valider'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($etape == 4): ?>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner; ?>"
                            style="display: inline;">
                            <button type="submit" class="btn-validate" style="background-color: #059669;">
                                <i class="fas fa-envelope"></i> Envoyer les résultats
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
    // Fonction de recherche
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.candidate-item');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Recherche dynamique sur la table d'historique
    document.getElementById('searchHistoriqueInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#historiqueCandidaturesTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });

    // Export CSV
    function exportHistoriqueCSV() {
        let csv = 'Étudiant,Statut,Date\n';
        document.querySelectorAll('#historiqueCandidaturesTable tbody tr').forEach(row => {
            if (row.style.display !== 'none') {
                const cols = row.querySelectorAll('td');
                csv += Array.from(cols).slice(0, 3).map(td => '"' + td.textContent.replace(/"/g, '""') + '"')
                    .join(',') + '\n';
            }
        });
        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'historique_candidatures.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Modal Voir détails (AJAX pour charger le résumé)
    document.querySelectorAll('.btn-details').forEach(btn => {
        btn.addEventListener('click', function() {
            const idCandidature = this.getAttribute('data-idcandidature');
            const resumeDiv = document.getElementById('resume-candidature-' + idCandidature);
            document.getElementById('historiqueDetailsContent').innerHTML = resumeDiv ? resumeDiv
                .innerHTML : '<p>Aucun résumé trouvé.</p>';
            document.getElementById('historiqueDetailsModal').style.display = 'flex';
        });
    });

    function closeHistoriqueModal() {
        document.getElementById('historiqueDetailsModal').style.display = 'none';
    }

    function printHistoriqueTable() {
        const printWindow = window.open('', '_blank');
        const table = document.querySelector('#historiqueCandidaturesTable');
        const title = 'Historique des candidatures examinées';
        // Construction des en-têtes sans la colonne Action
        const headers = Array.from(table.querySelectorAll('th')).slice(0, -1).map(th => `<th>${th.textContent}</th>`)
            .join('');
        // Construction des lignes sans la colonne Action
        const rows = Array.from(table.querySelectorAll('tbody tr')).map(row => {
            const cells = Array.from(row.querySelectorAll('td')).slice(0, -1).map(td =>
                `<td>${td.textContent}</td>`).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
        const content = `
            <html>
            <head>
                <title>Impression - ${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border: 1px solid #888; padding: 10px 16px; text-align: left; font-size: 14px; }
                    th { background-color: #f5f5f5; }
                    h1 { text-align: center; color: #333; }
                    @media print {
                        body { margin: 0; padding: 20px; }
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                    }
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                <table>
                    <thead><tr>${headers}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </body>
            </html>
        `;
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.focus();
        printWindow.onload = function() {
            printWindow.print();
            printWindow.close();
        };
    }

    function highlightActiveStep(etape) {
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active-scolarite', 'active-stage', 'active-semestre', 'active-resume');
        });
        if (etape == 1) {
            document.getElementById('stepScolarite').classList.add('active-scolarite');
        } else if (etape == 2) {
            document.getElementById('stepStage').classList.add('active-stage');
        } else if (etape == 3) {
            document.getElementById('stepSemestre').classList.add('active-semestre');
        } else if (etape == 4) {
            document.getElementById('stepResume').classList.add('active-resume');
        }
    }
    highlightActiveStep(<?php echo (int)$etape; ?>);
    </script>
</body>

</html>