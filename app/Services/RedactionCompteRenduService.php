<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Valider.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';


class RedactionCompteRenduService
{
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function getRapportsValidesForSelectedYear(): array
    {
        $selectedYearId = $this->getSelectedYearId();

        $sql = "
            SELECT r.id_rapport, r.num_etu, r.theme_rapport, e.prenom_etu, e.nom_etu, v2.decision_validation, e.id_annee_acad
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_carte_etud
            JOIN (
                SELECT id_rapport, MAX(date_validation) AS last_validation
                FROM valider
                GROUP BY id_rapport
            ) v1 ON r.id_rapport = v1.id_rapport
            JOIN valider v2 ON v2.id_rapport = v1.id_rapport AND v2.date_validation = v1.last_validation
            LEFT JOIN compte_rendu_rapport crr ON r.id_rapport = crr.id_rapport
            WHERE v2.decision_validation IN ('valider', 'rejeter')
              AND crr.id_rapport IS NULL
        ";

        $params = [];
        if ($selectedYearId !== null && $selectedYearId > 0) {
            $sql .= " AND e.id_annee_acad = :id_annee_acad";
            $params[':id_annee_acad'] = $selectedYearId;
        }

        $sql .= " ORDER BY v2.decision_validation DESC, r.theme_rapport";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupérer les données nécessaires à la vue index.
     *
     * @return array ['rapports_valides' => array, 'enseignants' => array]
     */
    public function getIndexData(): array
    {
        $rapportsValides = $this->getRapportsValidesForSelectedYear();
        $enseignantModel = new \Enseignant($this->pdo);
        $enseignants = $enseignantModel->getAllEnseignants();

        return [
            'rapports_valides' => $rapportsValides,
            'enseignants'      => $enseignants,
        ];
    }

    /**
     * Enregistrer un compte rendu complet : PDF, BD, affectations, emails.
     *
     * @param array $data Clés attendues : num_etu, nom_CR, contenu_CR, rapports,
     *                     encadrant_pedagogique, directeur_memoire
     * @return array ['success' => bool, 'message' => string]
     */
    public function enregistrer(array $data): array
    {
        $num_etu   = $data['num_etu'] ?? null;
        $nom_CR    = $data['nom_CR'] ?? '';
        $contenu_CR = $data['contenu_CR'] ?? '';
        $rapports  = $data['rapports'] ?? [];
        $date_CR   = date('Y-m-d H:i:s');
        $encadrants = $data['encadrant_pedagogique'] ?? [];
        $directeurs = $data['directeur_memoire'] ?? [];

        if (empty($num_etu)) {
            return ['success' => false, 'message' => "Aucun étudiant sélectionné."];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getSelectedYearId(), 'un compte rendu');
        if (!$writeGuard['success']) {
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        // Génération du PDF avec PdfGeneratorService (TCPDF)
        $html = '<html><head><meta charset="UTF-8"></head><body>' . $contenu_CR . '</body></html>';
        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage',
            __DIR__ . '/../../public/assets/img/logo.png'
        );
        $pdf = $pdfGen->createDocument('P', 'A4', 'Compte Rendu');
        $pdf->AddPage();
        $pdfGen->writeHtml($pdf, $html);
        $output = $pdf->Output('compte_rendu.pdf', 'S');

        // Sauvegarde du PDF sur disque
        $pdf_dir = __DIR__ . '/../../ressources/uploads/comptes_rendus/';
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        $pdf_name  = 'CR_' . date('Ymd_His') . '.pdf';
        $pdf_path  = $pdf_dir . $pdf_name;
        file_put_contents($pdf_path, $output);
        $chemin_pdf = 'ressources/uploads/comptes_rendus/' . $pdf_name;

        // Enregistrement en BD
        $id_CR = \CompteRendu::creer($num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR, $rapports);

        if (!$id_CR) {
            return ['success' => false, 'message' => "Erreur lors de l'enregistrement du compte rendu."];
        }

        // Affectations encadrant / directeur
        $this->saveAffectations($rapports, $encadrants, $directeurs);

        // Envoi des emails
        $this->sendNotificationEmails($rapports, $nom_CR, $pdf_path);

        return ['success' => true, 'message' => 'Compte rendu enregistré avec succès !'];
    }

    /**
     * Exporter un contenu en PDF et retourner les données brutes.
     *
     * @param string $contenu_CR Contenu HTML
     * @param string $nom_CR     Nom du compte rendu
     * @return array ['pdf' => string, 'filename' => string]
     * @throws \Exception Si le contenu est vide ou si Dompdf est indisponible
     */
    public function exporterPdf(string $contenu_CR, string $nom_CR): array
    {
        if (empty($contenu_CR)) {
            throw new \Exception('Le contenu du compte rendu est vide.');
        }

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

        return ['pdf' => $pdfOutput, 'filename' => $pdfName];
    }
    // -----------------------------------------------------------------------
    //  Private helpers
    // -----------------------------------------------------------------------

    /**
     * Insérer les affectations encadrant/directeur pour chaque rapport.
     */
    private function saveAffectations(array $rapports, array $encadrants, array $directeurs): void
    {
        foreach ($rapports as $id_rapport) {
            if (!empty($encadrants[$id_rapport])) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'encadrant')"
                );
                $stmt->execute([$encadrants[$id_rapport], $id_rapport]);
            }
            if (!empty($directeurs[$id_rapport])) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'directeur')"
                );
                $stmt->execute([$directeurs[$id_rapport], $id_rapport]);
            }
        }
    }

    /**
     * Envoyer un email de notification à chaque étudiant des rapports sélectionnés.
     */
    private function sendNotificationEmails(array $rapports, string $nom_CR, string $pdf_path): void
    {
        $rapportModel = new \RapportEtudiant($this->pdo);
        $emailService = new \EmailService();

        foreach ($rapports as $id_rapport) {
            $rapport = $rapportModel->getRapportById($id_rapport);
            if ($rapport && !empty($rapport['email_etu'])) {
                $to   = $rapport['email_etu'];
                $nom  = $rapport['prenom_etu'] . ' ' . $rapport['nom_etu'];
                $subject = "Notification de compte rendu de soutenance";
                $message = "Bonjour $nom,<br><br>Votre rapport (« " . htmlspecialchars($rapport['nom_rapport']) . " ») a été inclus dans le compte rendu « " . htmlspecialchars($nom_CR) . " » le " . date('d/m/Y H:i') . ".<br><br>Vous trouverez en pièce jointe le compte rendu complet de la séance d'évaluation.<br><br>Cordialement,<br>L'équipe pédagogique";

                $attachmentName = 'Compte_rendu_' . date('Y-m-d') . '.pdf';
                $emailService->sendEmailWithAttachment($to, $subject, $message, $pdf_path, $attachmentName, true);
            }
        }
    }
}
