<?php

require_once __DIR__ . '/../../app/controllers/ArchivesDossiersSoutenanceController.php';

/**
 * Routes pour les archives des dossiers de soutenance
 */

if (isset($_GET['page']) && $_GET['page'] === 'archives_dossiers_soutenance') {
    $controller = new ArchivesDossiersSoutenanceController();

    // Route pour exporter les archives
    if (isset($_GET['export'])) {
        $filtres = [
            'statut' => $_GET['statut'] ?? '',
            'annee' => $_GET['annee'] ?? '',
            'etudiant' => $_GET['etudiant'] ?? '',
            'date_debut' => $_GET['date_debut'] ?? '',
            'date_fin' => $_GET['date_fin'] ?? ''
        ];

        // Logique d'export (CSV, Excel, etc.)
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="archives_rapports_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // En-têtes CSV
        fputcsv($output, [
            'ID Rapport',
            'Statut',
            'Nom Rapport',
            'Thème',
            'Étudiant',
            'Date Dépôt',
            'Date Validation',
            'Temps Traitement',
            'Commentaire'
        ]);

        // Données
        foreach ($archives['rapports_archives'] as $rapport) {
            fputcsv($output, [
                $rapport['id_rapport'],
                $rapport['decision_validation'],
                $rapport['nom_rapport'] ?? '',
                $rapport['theme_rapport'] ?? '',
                ($rapport['prenom_etu'] ?? '') . ' ' . ($rapport['nom_etu'] ?? ''),
                $rapport['date_rapport'] ?? '',
                $rapport['date_validation'] ?? '',
                $rapport['temps_traitement'] ?? 0,
                $rapport['commentaire_validation'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    // Gestion des actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'details_rapport':
                if (isset($_GET['id'])) {
                    $rapportDetails = $controller->getRapportDetails($_GET['id']);

                    if ($rapportDetails) {
                        $GLOBALS['rapportDetails'] = $rapportDetails;
                    } else {
                        $_SESSION['error'] = "Rapport non trouvé";
                    }
                }
                break;

            default:
                // Action non reconnue, afficher la page principale
                break;
        }
    }

    // Route principale pour afficher les archives
    $controller->index();
    $contentFile = __DIR__ . '/../views/archives_dossiers_soutenance_content.php';
}