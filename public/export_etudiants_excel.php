<?php
/**
 * Export des étudiants au format Excel avec PhpSpreadsheet
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Accès non autorisé');
}

// Charger les configurations et connexions nécessaires
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/Core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Récupérer les étudiants
    $query = "
        SELECT 
            e.identifiant_mesrs,
            e.num_carte_etud,
            e.nom_etu,
            e.prenom_etu,
            e.date_naiss_etu,
            CASE 
                WHEN e.genre_etu = '1' THEN 'Masculin'
                WHEN e.genre_etu = '2' THEN 'Féminin'
                WHEN e.genre_etu = '3' THEN 'Neutre'
                ELSE e.genre_etu
            END as genre,
            e.email_etu,
            e.promotion_etu
        FROM etudiants e
        ORDER BY e.promotion_etu DESC, e.nom_etu ASC
    ";
    
    $stmt = $db->query($query);
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un nouveau Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Étudiants');
    
    // Définir les en-têtes
    $headers = ['ID MESRS', 'N° Carte Étud.', 'Nom', 'Prénom', 'Date Nais.', 'Genre', 'Email', 'Promotion'];
    $columnLetter = 'A';
    
    foreach ($headers as $header) {
        $sheet->setCellValue($columnLetter . '1', $header);
        $columnLetter++;
    }
    
    // Style des en-têtes
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    
    // Définir la largeur des colonnes
    $sheet->getColumnDimension('A')->setWidth(18); // ID MESRS
    $sheet->getColumnDimension('B')->setWidth(18); // N° Carte
    $sheet->getColumnDimension('C')->setWidth(25); // Nom
    $sheet->getColumnDimension('D')->setWidth(25); // Prénom
    $sheet->getColumnDimension('E')->setWidth(15); // Date
    $sheet->getColumnDimension('F')->setWidth(12); // Genre
    $sheet->getColumnDimension('G')->setWidth(35); // Email
    $sheet->getColumnDimension('H')->setWidth(15); // Promotion
    
    // Remplir les données
    $row = 2;
    foreach ($etudiants as $etudiant) {
        $sheet->setCellValue('A' . $row, $etudiant['identifiant_mesrs']);
        $sheet->setCellValue('B' . $row, $etudiant['num_carte_etud']);
        $sheet->setCellValue('C' . $row, $etudiant['nom_etu']);
        $sheet->setCellValue('D' . $row, $etudiant['prenom_etu']);
        $sheet->setCellValue('E' . $row, $etudiant['date_naiss_etu']);
        $sheet->setCellValue('F' . $row, $etudiant['genre']);
        $sheet->setCellValue('G' . $row, $etudiant['email_etu']);
        $sheet->setCellValue('H' . $row, $etudiant['promotion_etu']);
        $row++;
    }
    
    // Style des données
    if ($row > 2) {
        $dataRange = 'A2:H' . ($row - 1);
        
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $sheet->getStyle($dataRange)->applyFromArray($dataStyle);
        
        // Centrer certaines colonnes
        $centerColumns = ['A', 'B', 'E', 'F', 'H']; // ID MESRS, N° Carte, Date, Genre, Promotion
        foreach ($centerColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . ($row - 1))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
    
    // Figer la première ligne
    $sheet->freezePane('A2');
    
    // Génération du fichier
    $fileName = 'Liste_Etudiants_' . date('Y-m-d') . '.xlsx';
    
    // Headers pour le téléchargement
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    // Écrire le fichier
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur lors de l\'export: ' . $e->getMessage());
}
