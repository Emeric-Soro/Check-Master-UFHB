<?php
/**
 * ArchiveSoutenanceController - Archives soutenances et jurys
 */
require_once __DIR__ . '/../models/Soutenance.php';
require_once __DIR__ . '/../models/Jury.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveSoutenanceController
{
    private $db;
    private $soutenanceModel;
    private $juryModel;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->soutenanceModel = new Soutenance($this->db);
        $this->juryModel = new Jury($this->db);
    }

    /**
     * Liste des soutenances archivées
     */
    public function index()
    {
        if (!canView('archives_soutenances')) {
            $_SESSION['error_message'] = "Accès refusé aux archives soutenances.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $soutenances = $this->getSoutenancesArchives($anneeId, $dateFrom, $dateTo);
        $salles = $this->getSalles();

        return [
            'soutenances' => $soutenances,
            'salles' => $salles,
        ];
    }

    /**
     * Fiche détaillée d'une soutenance
     */
    public function fiche($numSoutenance)
    {
        if (!canView('archives_soutenances')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $soutenance = $this->getSoutenanceComplete($numSoutenance);
        if (!$soutenance) {
            $_SESSION['error_message'] = "Soutenance non trouvée.";
            header('Location: layout.php?page=archives_soutenances');
            exit;
        }

        $jury = $this->getJurySoutenance($numSoutenance);
        $evaluations = $this->getEvaluationsSoutenance($soutenance->num_etud);

        return [
            'soutenance' => $soutenance,
            'jury' => $jury,
            'evaluations' => $evaluations,
        ];
    }

    /**
     * Archives des jurys - statistiques
     */
    public function jurys()
    {
        if (!canView('archives_soutenances')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $statsJurys = $this->getStatsJurys($anneeId);

        return [
            'stats_jurys' => $statsJurys,
        ];
    }

    /**
     * Export PDF des soutenances archivees
     */
    public function exportPdf()
    {
        if (!canCreate('archives_soutenances')) {
            http_response_code(403);
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $soutenances = $this->getSoutenancesArchives($anneeId, $dateFrom, $dateTo);

        require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';

        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage/documents',
            __DIR__ . '/../../public/image/logo_ufhb.png'
        );

        $pdf = $pdfGen->createDocument('P', 'A4', 'Archives des soutenances - ' . date('Y'));

        // Construire le HTML du tableau
        $html = '<h1 style="text-align:center; font-size:16pt;">Archives des soutenances</h1>';
        $html .= '<p style="text-align:center; font-size:10pt; color:#666;">Genere le ' . date('d/m/Y H:i') . '</p>';
        $html .= '<hr>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; font-size:9pt; border-collapse:collapse;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color:#1a5276; color:white; font-weight:bold; text-align:center;">';
        $html .= '<th>N°</th><th>Etudiant</th><th>Date</th><th>Heure</th><th>Theme</th><th>Salle</th>';
        $html .= '</tr>';
        $html .= '</thead><tbody>';

        if (empty($soutenances)) {
            $html .= '<tr><td colspan="6" style="text-align:center; color:#999;">Aucune soutenance archivee.</td></tr>';
        } else {
            foreach ($soutenances as $i => $s) {
                $html .= '<tr>';
                $html .= '<td style="text-align:center;">' . ($i + 1) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($s->etudiant ?? '-')) . '</td>';
                $html .= '<td style="text-align:center;">' . date('d/m/Y', strtotime((string) ($s->date_soutenance ?? ''))) . '</td>';
                $html .= '<td style="text-align:center;">' . htmlspecialchars((string) ($s->heure_soutenance ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($s->theme_soutenance ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($s->lib_salle ?? '-')) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $pdfGen->addPage($pdf, false);
        $pdfGen->addHeader($pdf, 'Archives des soutenances');
        $pdfGen->writeHtml($pdf, $html);

        // Envoyer le PDF au navigateur (telechargement)
        $pdf->Output('archives_soutenances_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    // Méthodes privées

    private function getSoutenancesArchives($anneeId, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT 
                    ps.num_soutenance,
                    ps.date_soutenance,
                    ps.heure_soutenance,
                    ps.theme_soutenance,
                    s.lib_salle,
                    e.num_carte_etud,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as etudiant,
                    e.promotion_etu,
                    d.lib_domaine,
                    se.lib_session
                FROM programmer_soutenance ps
                JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                LEFT JOIN domaine d ON ps.id_domaine = d.id_domaine
                LEFT JOIN session se ON ps.id_session = se.id_session
                JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                WHERE i.id_annee_acad = ?";

        $params = [$anneeId];

        if ($dateFrom) {
            $sql .= " AND ps.date_soutenance >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND ps.date_soutenance <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY ps.date_soutenance DESC, ps.heure_soutenance ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getSoutenanceComplete($numSoutenance)
    {
        $sql = "SELECT ps.*, s.lib_salle, d.lib_domaine, se.lib_session,
                       e.num_carte_etud, e.nom_etu, e.prenom_etu
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                LEFT JOIN domaine d ON ps.id_domaine = d.id_domaine
                LEFT JOIN session se ON ps.id_session = se.id_session
                JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                WHERE ps.num_soutenance = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numSoutenance]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    private function getJurySoutenance($numSoutenance)
    {
        $sql = "SELECT ej.*, 
                       CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant) as nom_complet,
                       ens.mail_enseignant,
                       qj.lib_role,
                       g.lib_grade
                FROM enseignant_jury ej
                JOIN enseignants ens ON ej.id_enseignant = ens.id_enseignant
                JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury
                LEFT JOIN avoir a ON ens.id_enseignant = a.id_enseignant
                LEFT JOIN grade g ON a.id_grade = g.id_grade
                WHERE ej.num_soutenance = ?
                ORDER BY qj.id_role_jury";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numSoutenance]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getEvaluationsSoutenance($numEtud)
    {
        $sql = "SELECT ev.*, ce.lib_critere, bc.bareme
                FROM evaluer ev
                JOIN critere_evaluation ce ON ev.id_critere = ce.id_critere
                LEFT JOIN bareme_critere bc ON ce.id_critere = bc.id_critere
                WHERE ev.num_etudiant = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numEtud]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getStatsJurys($anneeId)
    {
        $sql = "SELECT 
                    ens.id_enseignant,
                    CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant) as nom_complet,
                    g.lib_grade,
                    COUNT(DISTINCT ej.num_soutenance) as total_soutenances,
                    SUM(CASE WHEN qj.id_role_jury = 'PJ' THEN 1 ELSE 0 END) as nb_president,
                    SUM(CASE WHEN qj.id_role_jury = 'EX' THEN 1 ELSE 0 END) as nb_examinateur,
                    SUM(CASE WHEN qj.id_role_jury = 'DM' THEN 1 ELSE 0 END) as nb_directeur,
                    SUM(CASE WHEN qj.id_role_jury = 'EN' THEN 1 ELSE 0 END) as nb_encadrant,
                    AVG(ev.note) as moyenne_notes
                FROM enseignants ens
                LEFT JOIN enseignant_jury ej ON ens.id_enseignant = ej.id_enseignant
                LEFT JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury
                LEFT JOIN programmer_soutenance ps ON ej.num_soutenance = ps.num_soutenance
                LEFT JOIN evaluer ev ON ps.num_etud = ev.num_etudiant
                LEFT JOIN avoir a ON ens.id_enseignant = a.id_enseignant
                LEFT JOIN grade g ON a.id_grade = g.id_grade
                JOIN inscriptions i ON (ps.num_etud = i.num_carte_etud OR EXISTS (SELECT 1 FROM etudiants e2 WHERE e2.num_carte_etud = i.num_carte_etud AND e2.num_ident_etud = ps.num_etud))
                WHERE i.id_annee_acad = ?
                GROUP BY ens.id_enseignant
                ORDER BY total_soutenances DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getSalles()
    {
        $stmt = $this->db->query("SELECT id_salle, lib_salle FROM salles ORDER BY lib_salle");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
