<?php

require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../utils/CSRFProtection.php';
class ArchivesCompteRenduController {
    
    public function index() {
        // Récupérer les paramètres de filtrage
        $search = $_GET['search'] ?? null;
        $year = $_GET['year'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Récupérer les archives
        $archives = CompteRendu::getAllArchives($limit, $offset, $search, $year);
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
    }
    
    public function viewArchive() {
        $id_CR = $_GET['id'] ?? null;
        
        if (!$id_CR) {
            $_SESSION['error'] = "ID du compte rendu manquant.";
            header('Location: layout.php?page=archives_compte_rendu');
            exit;
        }
        
        $archive = CompteRendu::getArchiveById($id_CR);
        
        if (!$archive) {
            $_SESSION['error'] = "Compte rendu non trouvé.";
            header('Location: layout.php?page=archives_compte_rendu');
            exit;
        }
        
        $GLOBALS['archive'] = $archive;
    }
    
    public function deleteArchive() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }
        
        // Vérifier le token CSRF
        CSRFProtection::verifyRequest();
        
        $id_CR = $_POST['id_CR'] ?? null;
        
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
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'archives' => $archives,
            'count' => count($archives)
        ]);
        exit;
    }

    public function telechargerPDF($chemin) {
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