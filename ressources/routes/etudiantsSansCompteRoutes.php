<?php
/**
 * Route pour les étudiants sans compte
 * ?page=etudiants_sans_compte
 */
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/utils/permissions_helper.php';

if (isset($_GET['page']) && $_GET['page'] === 'etudiants_sans_compte') {
    // La vue gère directement l'affichage sans contrôleur intermédiaire
    // car les données sont chargées via le service Utilisateur
    $db = Database::getConnection();
    require_once __DIR__ . '/../../app/models/Utilisateur.php';
    $utilisateurModel = new Utilisateur($db);
    $etudiantsSansCompte = $utilisateurModel->getEtudiantsNonUtilisateurs();
    $data = ['etudiants' => $etudiantsSansCompte];

    // Traitement POST pour création en masse
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_comptes'])) {
        if (!canEdit('gestion_utilisateurs')) {
            $_SESSION['error_message'] = "Accès refusé.";
        } else {
            $selectedIds = $_POST['selected_ids'] ?? [];
            if (!empty($selectedIds)) {
                $result = $utilisateurModel->ajouterUtilisateursEnMasse($selectedIds);
                if ($result) {
                    $_SESSION['success_message'] = count($selectedIds) . " comptes créés avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la création des comptes.";
                }
            }
            header('Location: layout.php?page=etudiants_sans_compte');
            exit;
        }
    }
}
