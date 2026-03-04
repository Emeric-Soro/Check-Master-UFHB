<?php
/**
 * Export PDF du planning des soutenances pour un enseignant
 */

// Initialiser la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'authentification
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['login_utilisateur'])) {
    http_response_code(401);
    die('Non autorisé');
}

// Charger les dépendances
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/models/Enseignant.php';
require_once __DIR__ . '/../../../app/Services/Document/PdfGeneratorService.php';

try {
    $pdo = Database::getConnection();

    // Vérifier si l'utilisateur est administrateur
    $isAdmin = false;
    $libGU = strtolower(trim((string) ($_SESSION['lib_GU'] ?? '')));
    if (strpos($libGU, 'admin') !== false) {
        $isAdmin = true;
    }

    $enseignantModel = new Enseignant($pdo);

    // Déterminer quel enseignant exporter
    $enseignantSelectionne = isset($_GET['id_enseignant_selected']) && $_GET['id_enseignant_selected'] !== '' ? (int) $_GET['id_enseignant_selected'] : null;

    if ($isAdmin && $enseignantSelectionne !== null) {
        // Admin exporte les données d'un enseignant sélectionné
        $enseignant = $enseignantModel->getEnseignantById($enseignantSelectionne);
    } else {
        // Enseignant connecté ou admin sans sélection
        $enseignant = $enseignantModel->getEnseignantByLogin((string) $_SESSION['login_utilisateur']);
    }


    $teacherId = (string) $enseignant->id_enseignant;
    $teacherName = trim($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant);

    // Récupérer les filtres
    $filtreSession = isset($_GET['id_session']) && $_GET['id_session'] !== '' ? (int) $_GET['id_session'] : null;
    $filtreQualiteJury = isset($_GET['id_qualite_jury']) && $_GET['id_qualite_jury'] !== '' ? (int) $_GET['id_qualite_jury'] : null;

    // Déterminer les noms de tables
    $juryTable = $pdo->query("SHOW TABLES LIKE 'enseignant_jury'")->fetchColumn() ? 'enseignant_jury' : 'composer_jury';
    $rolesTable = $pdo->query("SHOW TABLES LIKE 'qualite_jury'")->fetchColumn() ? 'qualite_jury' : 'roles_jury';
    $progTable = $pdo->query("SHOW TABLES LIKE 'programmer_soutenance'")->fetchColumn() ? 'programmer_soutenance' : 'programmer';

    // Construire la requête des soutenances
    $whereSoutenances = ["CAST(ej.id_enseignant AS CHAR) = :id_enseignant", "ps.date_soutenance IS NOT NULL"];
    $paramsSoutenances = [':id_enseignant' => $teacherId];

    if ($filtreSession !== null) {
        $whereSoutenances[] = "ps.id_session = :id_session";
        $paramsSoutenances[':id_session'] = $filtreSession;
    }

    if ($filtreQualiteJury !== null) {
        $whereSoutenances[] = "ej.id_qualite_jury = :id_qualite_jury";
        $paramsSoutenances[':id_qualite_jury'] = $filtreQualiteJury;
    }

    $whereSoutenancesClause = implode(' AND ', $whereSoutenances);

    $sqlSoutenances = "SELECT DISTINCT
                        ps.date_soutenance,
                        ps.heure_soutenance,
                        e.num_carte_etud,
                        CONCAT(e.nom_etud, ' ', e.prenom_etud) AS nom_complet_etudiant,
                        ps.theme_soutenance,
                        qj.lib_role,
                        qj.code_qltjury,
                        s.nom_salle,
                        sess.lib_session
                    FROM {$juryTable} ej
                    JOIN {$progTable} ps ON ps.num_soutenance = ej.num_soutenance
                    JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                    JOIN {$rolesTable} qj ON qj.id_role_jury = ej.id_qualite_jury
                    LEFT JOIN salles s ON s.id_salle = ps.id_salle
                    LEFT JOIN session sess ON sess.id_session = ps.id_session
                    WHERE {$whereSoutenancesClause}
                    ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC";

    $stmtSoutenances = $pdo->prepare($sqlSoutenances);
    $stmtSoutenances->execute($paramsSoutenances);
    $soutenances = $stmtSoutenances->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (empty($soutenances)) {
        throw new Exception('Aucune soutenance programmée pour les critères sélectionnés');
    }

    // Générer le contenu HTML du PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Helvetica", "Arial", sans-serif;
                font-size: 10pt;
                line-height: 1.4;
                margin: 0;
                padding: 20px;
            }
            h1 {
                text-align: center;
                color: #2c3e50;
                font-size: 18pt;
                margin-bottom: 5px;
            }
            h2 {
                text-align: center;
                color: #7f8c8d;
                font-size: 12pt;
                font-weight: normal;
                margin-top: 0;
                margin-bottom: 20px;
            }
            .info {
                text-align: center;
                margin-bottom: 20px;
                color: #34495e;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th {
                background-color: #3498db;
                color: white;
                padding: 8px;
                text-align: left;
                font-weight: bold;
                font-size: 9pt;
            }
            td {
                padding: 6px 8px;
                border-bottom: 1px solid #ddd;
                font-size: 9pt;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 8pt;
                color: #95a5a6;
            }
            .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 8pt;
                font-weight: bold;
                color: white;
            }
            .badge-primary { background-color: #3498db; }
            .badge-info { background-color: #17a2b8; }
            .badge-warning { background-color: #f39c12; }
            .badge-success { background-color: #27ae60; }
            .badge-danger { background-color: #e74c3c; }
        </style>
    </head>
    <body>
        <h1>Planning des Soutenances</h1>
        <h2>UFRMI - CheckMaster</h2>
        <div class="info">
            <strong>Enseignant:</strong> ' . htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Date d\'édition:</strong> ' . date('d/m/Y à H:i') . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 8%;">Heure</th>
                    <th style="width: 12%;">N° Étudiant</th>
                    <th style="width: 20%;">Étudiant</th>
                    <th style="width: 30%;">Thème</th>
                    <th style="width: 12%;">Rôle</th>
                    <th style="width: 8%;">Salle</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($soutenances as $soutenance) {
        $dateFormatee = date('d/m/Y', strtotime($soutenance['date_soutenance'] ?? ''));
        $heureFormatee = date('H:i', strtotime($soutenance['heure_soutenance'] ?? '00:00:00'));
        $numEtud = htmlspecialchars($soutenance['num_carte_etud'] ?? '', ENT_QUOTES, 'UTF-8');
        $nomEtud = htmlspecialchars($soutenance['nom_complet_etudiant'] ?? '', ENT_QUOTES, 'UTF-8');
        $theme = htmlspecialchars($soutenance['theme_soutenance'] ?? '', ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($soutenance['lib_role'] ?? '', ENT_QUOTES, 'UTF-8');
        $salle = htmlspecialchars($soutenance['nom_salle'] ?? '-', ENT_QUOTES, 'UTF-8');

        $html .= '
                <tr>
                    <td>' . $dateFormatee . '</td>
                    <td>' . $heureFormatee . '</td>
                    <td>' . $numEtud . '</td>
                    <td>' . $nomEtud . '</td>
                    <td>' . $theme . '</td>
                    <td>' . $role . '</td>
                    <td>' . $salle . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            Document généré automatiquement par CheckMaster UFRMI<br>
            Ce planning est susceptible de modifications
        </div>
    </body>
    </html>';

    // Générer le PDF
    $pdfGen = new \App\Services\Document\PdfGeneratorService(
        __DIR__ . '/../../../storage',
        __DIR__ . '/../../../public/assets/img/logo.png'
    );

    $pdf = $pdfGen->createDocument('L', 'A4', 'Planning des soutenances - ' . $teacherName);
    $pdf->AddPage();
    $pdfGen->writeHtml($pdf, $html);

    // Envoyer le PDF au navigateur
    $filename = 'planning_soutenances_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'I');

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erreur lors de la génération du PDF: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    error_log('Export planning PDF error: ' . $e->getMessage());
}
