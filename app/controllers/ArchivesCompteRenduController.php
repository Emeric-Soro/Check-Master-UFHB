<?php

require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/HashIdService.php';

class ArchivesCompteRenduController {
    private $hashids;

    public function __construct() {
        $this->hashids = new \App\Services\HashIdService();
    }
    
    public function index() {
        // Récupérer les paramètres de filtrage
        $search = $_GET['search'] ?? null;
        $year = $_GET['year'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Récupérer les archives
        $archives = CompteRendu::getAllArchives($limit, $offset, $search, $year);
        
        // Hasher les IDs
        foreach ($archives as &$archive) {
            $archive['id_hashed'] = $this->hashids->encode($archive['id_CR']);
        }
        
        $stats = CompteRendu::getStatsArchives();
        
        // Calculer le nombre total de pages
        $totalArchives = CompteRendu::getAllArchives(null, 0, $search, $year);
        $totalPages = ceil(count($totalArchives) / $limit);
        
        // Passer les données à la vue
        $GLOBALS['archives'] = $archives;
        $GLOBALS['stats'] = $stats;
        $GLOBALS['currentPage'] = $page;
        $GLOBALS['totalPages'] = $totalPages;
        $GLOBALS['search'] = $search;
        $GLOBALS['year'] = $year;
        
        // Setup for layout
        $_GET['page'] = 'archive_comptes_rendus';
        $GLOBALS['skip_legacy_routing'] = true;
        require_once __DIR__ . '/../../layout.php';
    }
    
    public function viewArchive($id) {
        $id_CR = $this->hashids->decode($id);
        
        if (!$id_CR) {
            $_SESSION['error'] = "ID du compte rendu manquant.";
            header('Location: /compte-rendu/archives');
            exit;
        }
        
        $archive = CompteRendu::getArchiveById($id_CR);
        
        if (!$archive) {
            $_SESSION['error'] = "Compte rendu non trouvé.";
            header('Location: /compte-rendu/archives');
            exit;
        }
        
        $GLOBALS['archive'] = $archive;
    }
    
    public function deleteArchive() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }
        
        $id_hashed = $_POST['id_CR'] ?? null;
        $id_CR = $this->hashids->decode($id_hashed);
        
        if (!$id_CR) {
            echo json_encode(['success' => false, 'message' => 'ID du compte rendu manquant.']);
            exit;
        }
        
        $success = CompteRendu::deleteArchive($id_CR);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Archive supprimée avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'archive.']);
        }
        exit;
    }
    
    public function exportArchives() {
        $search = $_GET['search'] ?? null;
        $year = $_GET['year'] ?? null;
        
        $archives = CompteRendu::getAllArchives(null, 0, $search, $year);
        
        // Générer un fichier CSV
        $filename = 'archives_comptes_rendus_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($output, [
            'ID', 'Nom du CR', 'Étudiant', 'Email', 'Date de création', 
            'Nombre de rapports', 'Chemin PDF'
        ]);
        
        // Données
        foreach ($archives as $archive) {
            $rapports = CompteRendu::getArchiveById($archive['id_CR']);
            $nbRapports = count($rapports['rapports'] ?? []);
            
            fputcsv($output, [
                $archive['id_CR'],
                $archive['nom_CR'],
                $archive['prenom_etu'] . ' ' . $archive['nom_etu'],
                $archive['email_etu'],
                $archive['date_CR'],
                $nbRapports,
                $archive['chemin_fichier_pdf']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    public function searchArchives() {
        $search = $_GET['q'] ?? '';
        $year = $_GET['year'] ?? null;
        
        $archives = CompteRendu::getAllArchives(20, 0, $search, $year);
        
        // Hasher les IDs
        foreach ($archives as &$archive) {
            $archive['id_hashed'] = $this->hashids->encode($archive['id_CR']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'archives' => $archives,
            'count' => count($archives)
        ]);
        exit;
    }

    public function telecharger($id) {
        $decodedId = $this->hashids->decode($id);
        if (!$decodedId) {
            die("ID invalide");
        }
        $id = $decodedId;

        $archive = CompteRendu::getArchiveById($id);
        if (!$archive || empty($archive['chemin_fichier_pdf'])) {
            die("Fichier non trouvé");
        }
        $chemin = $archive['chemin_fichier_pdf'];

        // Sécurisation du chemin
        $chemin = realpath(__DIR__ . '/../../' . $chemin);
        $uploads = realpath(__DIR__ . '/../../ressources/uploads/comptes_rendus/');
        if (strpos($chemin, $uploads) !== 0 || !file_exists($chemin)) {
            http_response_code(404);
            echo 'Fichier non trouvé ou accès interdit';
            exit;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($chemin).'"');
        header('Content-Length: ' . filesize($chemin));
        readfile($chemin);
        exit;
    }
} 