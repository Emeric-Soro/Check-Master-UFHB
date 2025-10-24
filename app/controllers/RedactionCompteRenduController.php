<?php

require_once __DIR__ . '/../models/Valider.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/permissions.php';
use Dompdf\Dompdf;

class RedactionCompteRenduController {
    public function index() {
        $GLOBALS['rapports_valides'] = Valider::getRapportsValides();
        require_once __DIR__ . '/../models/Enseignant.php';
        $enseignantModel = new Enseignant(\Database::getConnection());
        $GLOBALS['enseignants'] = $enseignantModel->getAllEnseignants();
        // Ne pas inclure la vue ici, le layout s'en charge
    }

    public function enregistrer() {
        // Vérifier la permission CREATE
        if (!hasPermission('redaction_compte_rendu', 'CREATE')) {
            $_SESSION['error'] = "Vous n'avez pas la permission d'enregistrer des comptes rendus.";
            header('Location: layout.php?page=redaction_compte_rendu');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $num_etu = $_POST['num_etu'] ?? null;
            $nom_CR = $_POST['nom_CR'] ?? '';
            $contenu_CR = $_POST['contenu_CR'] ?? '';
            $rapports = isset($_POST['rapports']) ? $_POST['rapports'] : [];
            $date_CR = date('Y-m-d H:i:s');
            $encadrants = $_POST['encadrant_pedagogique'] ?? [];
            $directeurs = $_POST['directeur_memoire'] ?? [];

            if (empty($num_etu)) {
                $_SESSION['error'] = "Aucun étudiant sélectionné.";
                header('Location: layout.php?page=redaction_compte_rendu');
                exit;
            }

            // TEST MINIMAL POUR LES IMAGES
            $html = '<html><head><meta charset="UTF-8"></head><body>' . $contenu_CR . '</body></html>';
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            // Sauvegarde du PDF
            $pdf_dir = __DIR__ . '/../../ressources/uploads/comptes_rendus/';
            if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0777, true);
            $pdf_name = 'CR_' . date('Ymd_His') . '.pdf';
            $pdf_path = $pdf_dir . $pdf_name;
            file_put_contents($pdf_path, $output);
            $chemin_pdf = 'ressources/uploads/comptes_rendus/' . $pdf_name;

            // Enregistrement en BD
            $id_CR = CompteRendu::creer($num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR, $rapports);
            if ($id_CR) {
                // Enregistrement des affectations encadrant/directeur
                $pdo = \Database::getConnection();
                foreach ($rapports as $id_rapport) {
                    // Encadrant pédagogique
                    if (!empty($encadrants[$id_rapport])) {
                        $id_enseignant = $encadrants[$id_rapport];
                        $stmt = $pdo->prepare("INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'encadrant')");
                        $stmt->execute([$id_enseignant, $id_rapport]);
                    }
                    // Directeur de mémoire
                    if (!empty($directeurs[$id_rapport])) {
                        $id_enseignant = $directeurs[$id_rapport];
                        $stmt = $pdo->prepare("INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'directeur')");
                        $stmt->execute([$id_enseignant, $id_rapport]);
                    }
                }
                $_SESSION['success'] = 'Compte rendu enregistré avec succès !';
                // Envoi d'un email à chaque étudiant concerné
                require_once __DIR__ . '/../models/RapportEtudiant.php';
                require_once __DIR__ . '/../utils/EmailService.php';
                $pdo = \Database::getConnection();
                $rapportModel = new RapportEtudiant($pdo);
                $emailService = new EmailService();
                foreach ($rapports as $id_rapport) {
                    $rapport = $rapportModel->getRapportById($id_rapport);
                    if ($rapport && !empty($rapport['email_etu'])) {
                        $to = $rapport['email_etu'];
                        $nom = $rapport['prenom_etu'] . ' ' . $rapport['nom_etu'];
                        $subject = "Notification de compte rendu de soutenance";
                        $message = "Bonjour $nom,<br><br>Votre rapport (« " . htmlspecialchars($rapport['nom_rapport']) . " ») a été inclus dans le compte rendu « " . htmlspecialchars($nom_CR) . " » le " . date('d/m/Y H:i') . ".<br><br>Vous trouverez en pièce jointe le compte rendu complet de la séance d'évaluation.<br><br>Cordialement,<br>L'équipe pédagogique";
                        
                        // Envoyer l'email avec le PDF en pièce jointe
                        $attachmentName = 'Compte_rendu_' . date('Y-m-d') . '.pdf';
                        $emailService->sendEmailWithAttachment($to, $subject, $message, $pdf_path, $attachmentName, true);
                    }
                }
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'enregistrement du compte rendu.';
            }
            header('Location: layout.php?page=redaction_compte_rendu');
            exit;
        }
    }

    public function exporterPDF() {
        try {
            if (!class_exists('Dompdf\\Dompdf')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }
            if (!class_exists('Dompdf\\Dompdf')) {
                throw new \Exception('Dompdf n\'est pas installé.');
            }
            $contenu = $_POST['contenu_CR'] ?? '';
            $nom_CR = $_POST['nom_CR'] ?? 'compte_rendu';
            if (empty($contenu)) {
                throw new \Exception('Le contenu du compte rendu est vide.');
            }
            $html = '<html><head><meta charset="UTF-8"><style>body{font-family:Times New Roman,serif;line-height:1.6;margin:40px;}</style></head><body>' . $contenu . '</body></html>';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf = $dompdf->output();
            $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_CR) . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdfName . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdf;
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ]);
            exit;
        }
    }
} 