<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
use Etudiant;
use Scolarite;
use Inscription;
use RapportEtudiant;
use CompteRendu;
use Note;
use PDO;
use Exception;

/**
 * Service métier pour l'édition des bulletins.
 */
class EditionBulletinService
{
    private $pdo;
    private $etudiantModel;
    private $scolariteModel;
    private $inscriptionModel;
    private $rapportModel;
    private $compteRenduModel;
    private $notesModel;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->etudiantModel = new Etudiant($this->pdo);
        $this->scolariteModel = new Scolarite($this->pdo);
        $this->inscriptionModel = new Inscription($this->pdo);
        $this->rapportModel = new RapportEtudiant($this->pdo);
        $this->compteRenduModel = new CompteRendu($this->pdo);
        $this->notesModel = new Note($this->pdo);
    }

    /**
     * Récupérer les données pour la vue d'édition des bulletins.
     * @return array ['etudiants' => array, 'anneesAcademiques' => array, ...]
     */
    public function getIndexData(): array
    {
        $anneesAcademiques = $this->getAnneesAcademiques();
        $etudiants = $this->getEtudiantsWithBulletinStatus();

        return [
            'etudiants' => $etudiants,
            'anneesAcademiques' => $anneesAcademiques,
            // Add other data as needed by the view
        ];
    }

    /**
     * Récupérer les années académiques disponibles.
     * @return array
     */
    public function getAnneesAcademiques(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_annee_acad, date_deb, date_fin,
                       CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS lib_annee
                FROM annee_academique
                ORDER BY date_deb DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAnneesAcademiques: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer la liste des étudiants avec leur statut de bulletin.
     * @return array
     */
    public function getEtudiantsWithBulletinStatus(): array
    {
        try {
            // Get all students
            $etudiants = $this->etudiantModel->getAllEtudiants();
            $result = [];

            foreach ($etudiants as $etudiant) {
                $numEtu = $etudiant->num_carte_etud ?? $etudiant->num_ident_etud ?? '';
                if ($numEtu === '') {
                    continue;
                }

                // Check eligibility
                $eligible = $this->estEligiblePourBulletin($numEtu);
                $status = $eligible ? 'eligible' : 'non_eligible';

                // Check if bulletin has been generated
                $hasBulletin = $this->existeBulletinPourEtudiant($numEtu);
                if ($hasBulletin) {
                    $status = 'genere';
                    // Check if published (we consider generated as published for now)
                    // In the future, we could have a published flag
                }

                // Get student info
                $info = $this->etudiantModel->getEtudiantByNumEtu($numEtu);
                $nom = $info['nom_etu'] ?? '';
                $prenom = $info['prenom_etu'] ?? '';
                $promotion = $info['promotion_etu'] ?? '';

                // Determine level from promotion
                $niveau = '-';
                if (stripos($promotion, 'M2') !== false) {
                    $niveau = 'M2';
                } elseif (stripos($promotion, 'M1') !== false) {
                    $niveau = 'M1';
                }

                // Semestre (hardcoded to S1 for now, as in the original view)
                $semestre = 'S1';

                // Get average score if available (from soutenance evaluation)
                $moyenne = $this->getMoyenneSoutenance($numEtu);
                $mention = $this->calculerMention($moyenne);

                $result[] = [
                    'num_etu' => $numEtu,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'promotion' => $promotion,
                    'niveau' => $niveau,
                    'semestre' => $semestre,
                    'moyenne' => $moyenne,
                    'mention' => $mention,
                    'eligible' => $eligible,
                    'has_bulletin' => $hasBulletin,
                    'status' => $status,
                ];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsWithBulletinStatus: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si un étudiant est éligible pour le bulletin.
     * Conditions:
     *   - scolarité soldée
     *   - semestre requis validé
     *   - rapport validé communication
     *   - décision commission finalisée favorable
     *   - évaluation soutenance complète
     * @param string $numEtu
     * @return bool
     */
    public function estEligiblePourBulletin(string $numEtu): bool
    {
        try {
            // 1. Scolarité soldée
            $scolarite = $this->scolariteModel->getScolariteEtudiant($numEtu);
            if (!$scolarite || $scolarite['reste_a_payer'] > 0) {
                return false;
            }

            // 2. Semestre requis validé (S1 du Master 2)
            $semestreValide = $this->notesModel->estSemestreValide($numEtu, 'S1', 'Master 2');
            if (!$semestreValide) {
                return false;
            }

            // 3. Rapport validé communication
            $rapportValide = $this->rapportModel->estRapportValideCommunication($numEtu);
            if (!$rapportValide) {
                return false;
            }

            // 4. Décision commission finalisée favorable
            $decisionCommission = $this->rapportModel->getDerniereDecisionCommission($numEtu);
            if ($decisionCommission !== 'favorable') {
                return false;
            }

            // 5. Évaluation soutenance complète
            $evaluationComplete = $this->rapportModel->estEvaluationSoutenanceComplete($numEtu);
            if (!$evaluationComplete) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log('Erreur estEligiblePourBulletin: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un bulletin existe déjà pour un étudiant.
     * @param string $numEtu
     * @return bool
     */
    public function existeBulletinPourEtudiant(string $numEtu): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM compte_rendu 
                WHERE num_etu = ? AND nom_CR LIKE 'BULLETIN_%'
            ");
            $stmt->execute([$numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log('Erreur existeBulletinPourEtudiant: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Générer le bulletin pour un étudiant.
     * @param string $numEtu
     * @return array ['success' => bool, 'message' => string, 'bulletinId' => int|null]
     */
    public function genererBulletin(string $numEtu): array
    {
        if (!$this->estEligiblePourBulletin($numEtu)) {
            return ['success' => false, 'message' => "L'étudiant n'est pas éligible pour le bulletin."];
        }

        // Generate bulletin content (HTML)
        $contenu = $this->genererContenuBulletin($numEtu);
        if (empty($contenu)) {
            return ['success' => false, 'message' => "Impossible de générer le contenu du bulletin."];
        }

        // Generate PDF
        $nom_CR = 'BULLETIN_' . $numEtu . '_' . date('Y-m-d_His');
        $pdfResult = $this->exporterPdf($contenu, $nom_CR);
        if (!$pdfResult['success']) {
            return ['success' => false, 'message' => "Erreur lors de la génération du PDF : " . $pdfResult['message']];
        }

        // Save to database (compte_rendu)
        $date_CR = date('Y-m-d H:i:s');
        $chemin_pdf = $pdfResult['path']; // Assuming exporterPdf returns path
        $id_CR = $this->compteRenduModel->creer(
            $numEtu,
            $nom_CR,
            $contenu,
            $chemin_pdf,
            $date_CR,
            [] // No rapports linked for bulletin
        );

        if (!$id_CR) {
            return ['success' => false, 'message' => "Erreur lors de l'enregistrement du bulletin."];
        }

        return [
            'success' => true,
            'message' => 'Bulletin généré avec succès.',
            'bulletinId' => $id_CR
        ];
    }

    /**
     * Générer le contenu HTML du bulletin pour un étudiant.
     * @param string $numEtu
     * @return string
     */
    public function genererContenuBulletin(string $numEtu): string
    {
        // Get student info
        $etudiant = $this->etudiantModel->getEtudiantByNumEtu($numEtu);
        $nom = $etudiant['nom_etu'] ?? 'Inconnu';
        $prenom = $etudiant['prenom_etu'] ?? '';
        $promotion = $etudiant['promotion_etu'] ?? '';

        // Get soutenance info
        $soutenance = $this->rapportModel->getDerniereSoutenance($numEtu);
        $theme = $soutenance['theme_rapport'] ?? 'Non défini';
        $dateSoutenance = $soutenance['date_soutenance'] ?? date('Y-m-d');

        // Get average score (combine soutenance + cycle Master)
        $moyenneSoutenance = $this->getMoyenneSoutenance($numEtu);
        $moyenneMaster = $this->notesModel->getMoyenneGenerale($numEtu);

        if ($moyenneMaster && (float)$moyenneMaster->moyenne_generale > 0) {
            // Moyenne combinée : (cycle Master + soutenance) / 2
            $moyenne = round(((float)$moyenneMaster->moyenne_generale + $moyenneSoutenance) / 2, 2);
        } else {
            // Fallback : uniquement la moyenne de soutenance (backward compat)
            $moyenne = round($moyenneSoutenance, 2);
        }
        $mention = $this->calculerMention($moyenne);

        // Pré-calcul pour l'affichage (les heredocs PHP ne supportent pas les expressions)
        $moyenneSoutenanceDisplay = round($moyenneSoutenance, 2);
        $moyenneMasterDisplay = $moyenneMaster ? round($moyenneMaster->moyenne_generale, 2) : 'N/A';

        // Get academic year info
        $anneeAcad = $this->getAnneAcademiqueEtudiant($numEtu);
        $libAnnee = $anneeAcad['lib_annee'] ?? '';

        // Build HTML (simplified)
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Bulletin de $prenom $nom</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .section { margin-bottom: 20px; }
                .label { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Bulletin de l'étudiant</h1>
                <h2>$prenom $nom</h2>
                <p>Promotion : $promotion</p>
                <p>Année académique : $libAnnee</p>
            </div>
            <div class="section">
                <p><span class="label">Numéro étudiant :</span> $numEtu</p>
            </div>
            <div class="section">
                <p><span class="label">Theme de soutenance :</span> $theme</p>
                <p><span class="label">Date de soutenance :</span> $dateSoutenance</p>
            </div>
            <div class="section">
                <p><span class="label">Moyenne de soutenance :</span> $moyenneSoutenanceDisplay / 20</p>
                <p><span class="label">Moyenne du cycle Master :</span> $moyenneMasterDisplay / 20</p>
                <p><span class="label">Moyenne générale :</span> $moyenne / 20</p>
                <p><span class="label">Mention :</span> $mention</p>
            </div>
            <div class="section">
                <p><span class="label">Date de génération :</span> ' . date('d/m/Y H:i') . '</p>
            </div>
        </body>
        </html>
        HTML;

        return $html;
    }

    /**
     * Récupérer la moyenne de soutenance pour un étudiant.
     * @param string $numEtu
     * @return float
     */
    private function getMoyenneSoutenance(string $numEtu): float
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(ev.note) as moyenne
                FROM evaluer ev
                WHERE ev.num_etudiant = ?
            ");
            $stmt->execute([$numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['moyenne'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur getMoyenneSoutenance: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculer la mention à partir de la moyenne.
     * @param float $moyenne
     * @return string
     */
    private function calculerMention(float $moyenne): string
    {
        if ($moyenne >= 18) {
            return 'Honorable';
        } elseif ($moyenne >= 16) {
            return 'Tres Bien';
        } elseif ($moyenne >= 14) {
            return 'Bien';
        } elseif ($moyenne >= 12) {
            return 'Assez Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    /**
     * Récupérer l'année académique de l'étudiant (dernière inscription).
     * @param string $numEtu
     * @return array|null
     */
    private function getAnneAcademiqueEtudiant(string $numEtu): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT aa.id_annee_acad, aa.date_deb, aa.date_fin,
                       CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS lib_annee
                FROM inscriptions i
                JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
                WHERE i.num_carte_etud = ?
                ORDER BY i.date_inscription DESC
                LIMIT 1
            ");
            $stmt->execute([$numEtu]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAnneAcademiqueEtudiant: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Exporter un contenu en PDF et retourner les données brutes.
     * @param string $contenu_CR Contenu HTML
     * @param string $nom_CR Nom du compte rendu
     * @return array ['success' => bool, 'pdf' => string, 'path' => string, 'message' => string]
     */
    public function exporterPdf(string $contenu_CR, string $nom_CR): array
    {
        if (empty($contenu_CR)) {
            return ['success' => false, 'message' => 'Le contenu du bulletin est vide.'];
        }

        try {
            // HTML complet ou enveloppement
            if (strpos($contenu_CR, '<!DOCTYPE html>') !== false || strpos($contenu_CR, '<html') !== false) {
                $html = $contenu_CR;
            } else {
                $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:"Times New Roman",serif;line-height:1.6;margin:40px;}</style></head><body>' . $contenu_CR . '</body></html>';
            }

            // Générer le PDF avec PdfGeneratorService (TCPDF)
            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../../storage',
                __DIR__ . '/../../public/assets/img/logo.png'
            );
            $pdf = $pdfGen->createDocument('P', 'A4', $nom_CR);
            $pdf->AddPage();
            $pdfGen->writeHtml($pdf, $html);

            $pdfOutput = $pdf->Output($nom_CR . '.pdf', 'S');
            $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_CR) . '.pdf';

            // Sauvegarde du PDF sur disque
            $pdf_dir = __DIR__ . '/../../ressources/uploads/bulletins/';
            if (!is_dir($pdf_dir)) {
                mkdir($pdf_dir, 0777, true);
            }
            $pdf_path = $pdf_dir . $pdfName;
            file_put_contents($pdf_path, $pdfOutput);

            return [
                'success' => true,
                'pdf' => $pdfOutput,
                'path' => $pdf_path,
                'message' => 'PDF généré avec succès.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer l'historique des bulletins pour un étudiant.
     * @param string $numEtu
     * @return array
     */
    public function getHistoriqueBulletins(string $numEtu): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_CR, nom_CR, date_CR, chemin_fichier_pdf
                FROM compte_rendu
                WHERE num_etu = ? AND nom_CR LIKE 'BULLETIN_%'
                ORDER BY date_CR DESC
            ");
            $stmt->execute([$numEtu]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getHistoriqueBulletins: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer un bulletin (en réalité, on ne supprime pas, on archive en changeant le statut?)
     * Mais comme on ne peut pas supprimer, on ne fait rien ou on marque comme supprimé?
     * Pour l'instant, on ne supporte pas la suppression.
     * @param int $id_CR
     * @return array ['success' => bool, 'message' => string]
     */
    public function supprimerBulletin(int $id_CR): array
    {
        // Nous ne supprimons pas les bulletins pour conserver l'historique.
        return ['success' => false, 'message' => 'La suppression des bulletins n\'est pas autorisée pour conserver l\'historique.'];
    }

    /**
     * Récupérer un bulletin par son ID.
     * @param int $id_CR
     * @return array|null
     */
    public function getBulletinById(int $id_CR): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_CR, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR
                FROM compte_rendu
                WHERE id_CR = ? AND nom_CR LIKE 'BULLETIN_%'
            ");
            $stmt->execute([$id_CR]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getBulletinById: ' . $e->getMessage());
            return null;
        }
    }
}