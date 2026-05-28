<?php
// Vérifier que la page est définie et correspond à celle attendue
if (isset($_GET['page']) && in_array($_GET['page'], ['evaluations_dossiers_soutenance', 'evaluation_dossiers'], true)) {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/EvaluationDossiersController.php';
    $controller = new EvaluationDossiersController(Database::getConnection());
    
    // Si on demande un fichier de rapport
    if (isset($_GET['fichier'])) {
        $id_rapport = (int) $_GET['fichier'];

        // Vérifier si c'est un fichier uploadé (PDF/DOC/DOCX) → rediriger vers DocViewer
        require_once __DIR__ . '/../../app/models/RapportEtudiant.php';
        $rapportModel = new RapportEtudiant(Database::getConnection());
        $rapportInfo = $rapportModel->getRapportById($id_rapport);
        if ($rapportInfo && !empty($rapportInfo['chemin_fichier'])) {
            $ext = strtolower(pathinfo((string) $rapportInfo['chemin_fichier'], PATHINFO_EXTENSION));
            if ($ext !== 'html') {
                header('Location: ?page=docviewer&type=rapport&id=' . $id_rapport . '&action=preview');
                exit;
            }
        }

        // Fichier HTML éditeur → affichage direct
        $cheminFichier = __DIR__ . "/../uploads/rapports/rapport_{$id_rapport}.html";

        if (file_exists($cheminFichier)) {
            readfile($cheminFichier);
            exit;
        } else {
            http_response_code(404);
            echo "Fichier non trouvé";
            exit;
        }
    }
    
    // Si c'est une action AJAX (traitement de décision) - gérer GET et POST
    if (isset($_GET['action']) && $_GET['action'] === 'traiter_decision') {
        header('Content-Type: application/json');
        
        // Debug: log les paramètres reçus
        error_log("DEBUG: Action traiter_decision appelée");
        error_log("DEBUG: Méthode HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("DEBUG: GET params: " . print_r($_GET, true));
        error_log("DEBUG: POST params: " . print_r($_POST, true));
        
        $controller->traiterAction();
        exit;
    }
    
    // Toujours récupérer les statistiques et la liste des dossiers
    $data = $controller->index();
    $stats = $data['stats'];
    $dossiers = $data['dossiers'];
    
    // Si on demande le détail d'un dossier, le récupérer en plus
    if (isset($_GET['detail'])) {
        $detail = $controller->detail($_GET['detail']);
    }
}

// Route pour la page processus de validation
if (isset($_GET['page']) && $_GET['page'] === 'processus_validation') {
    // La page utilise directement le contrôleur ProcessusValidationController
    // et récupère les données dans la vue
} 
