<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;

/**
 * Contrôleur pour la validation administrative et technique des candidatures
 * Écran 1.3.1: Candidature (Validation Administrative et Technique)
 */
class AdminCandidatureController {
    private $db;
    private $etudiant;
    private $scolarite;
    private $emailService;
    private $pers_admin;
    private $auditLog;

    // Paramètres de configuration
    private $VERIF_SCOLARITE_BLOQUANTE = true;
    private $DETTE_TOLERANCE_MAX = 50000; // 50 000 FCFA
    private $STAGE_DUREE_MIN_JOURS = 60;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->etudiant = new Etudiant($this->db);
        $this->scolarite = new Scolarite($this->db);
        $this->emailService = new EmailService();
        $this->pers_admin = new PersAdmin($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    /**
     * Affiche la liste des candidatures avec filtres
     * GET /admin/candidatures
     */
    public function index() {
        Session::start();

        // Récupérer les filtres
        $filtreStatut = $_GET['statut'] ?? 'toutes';
        $filtreAnnee = $_GET['annee_academique'] ?? '';
        $filtreFiliere = $_GET['filiere'] ?? '';
        $recherche = $_GET['recherche'] ?? '';

        // Construire la requête de base - Utilise uniquement les colonnes existantes
        $sql = "
            SELECT 
                cs.id_candidature,
                cs.num_etu,
                cs.date_candidature,
                cs.statut_candidature,
                cs.date_traitement,
                cs.id_pers_admin,
                cs.commentaire_admin,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu,
                n.lib_niv_etude as filiere,
                ia.id_annee_acad,
                ia.date_deb,
                ia.date_fin,
                pa.nom_pers_admin as nom_validateur,
                pa.prenom_pers_admin as prenom_validateur
            FROM candidature_soutenance cs
            INNER JOIN etudiants e ON cs.num_etu = e.num_carte_etud
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
            LEFT JOIN annee_academique ia ON i.id_annee_acad = ia.id_annee_acad
            LEFT JOIN personnel_admin pa ON cs.id_pers_admin = pa.id_pers_admin
            WHERE 1=1
        ";

        $params = [];

        // Appliquer les filtres
        if ($filtreStatut !== 'toutes') {
            $sql .= " AND cs.statut_candidature = ?";
            $params[] = $filtreStatut;
        }

        if (!empty($filtreAnnee)) {
            $sql .= " AND ia.id_annee_acad = ?";
            $params[] = $filtreAnnee;
        }

        if (!empty($filtreFiliere)) {
            $sql .= " AND n.id_niv_etude = ?";
            $params[] = $filtreFiliere;
        }

        if (!empty($recherche)) {
            $sql .= " AND (e.nom_etu LIKE ? OR e.prenom_etu LIKE ? OR cs.num_etu LIKE ?)";
            $searchTerm = "%$recherche%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY cs.date_candidature DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les années académiques pour le filtre
        $annees = $this->getAnneesAcademiques();

        // Récupérer les filières pour le filtre
        $filieres = $this->getFilieres();

        // Statistiques
        $stats = $this->getStatistiquesCandidatures();

        // Passer les données à la vue
        $GLOBALS['candidatures'] = $candidatures;
        $GLOBALS['annees'] = $annees;
        $GLOBALS['filieres'] = $filieres;
        $GLOBALS['stats'] = $stats;
        $GLOBALS['filtres'] = [
            'statut' => $filtreStatut,
            'annee_academique' => $filtreAnnee,
            'filiere' => $filtreFiliere,
            'recherche' => $recherche
        ];
    }

    /**
     * Récupère le détail d'une candidature pour le panneau latéral
     * GET /admin/candidatures/{id}
     */
    public function detail() {
        Session::start();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID candidature manquant']);
            return;
        }

        // Récupérer la candidature
        $sql = "
            SELECT 
                cs.*,
                e.nom_etu, e.prenom_etu, e.email_etu, e.date_naiss_etu,
                n.lib_niv_etude as filiere,
                pa.nom_pers_admin as nom_validateur,
                pa.prenom_pers_admin as prenom_validateur
            FROM candidature_soutenance cs
            INNER JOIN etudiants e ON cs.num_etu = e.num_carte_etud
            LEFT JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
            LEFT JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
            LEFT JOIN personnel_admin pa ON cs.id_pers_admin = pa.id_pers_admin
            WHERE cs.id_candidature = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidature) {
            http_response_code(404);
            echo json_encode(['error' => 'Candidature non trouvée']);
            return;
        }

        // Récupérer les informations de stage
        $stage = $this->etudiant->getInfoStage($candidature['num_etu']);

        // Récupérer l'historique des candidatures
        $historique = $this->getHistoriqueCandidatures($candidature['num_etu']);

        // Vérifier la scolarité
        $scolarite = $this->scolarite->getScolariteEtudiant($candidature['num_etu']);
        $scolariteBloquee = ($this->VERIF_SCOLARITE_BLOQUANTE && $scolarite && $scolarite['reste_a_payer'] > $this->DETTE_TOLERANCE_MAX);

        // Si c'est une requête AJAX, retourner JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'candidature' => $candidature,
                'stage' => $stage,
                'historique' => $historique,
                'scolarite' => $scolarite,
                'scolarite_bloquee' => $scolariteBloquee,
                'dette_max' => $this->DETTE_TOLERANCE_MAX
            ]);
            return;
        }

        // Sinon, passer à la vue
        $GLOBALS['candidatureDetail'] = $candidature;
        $GLOBALS['stageDetail'] = $stage;
        $GLOBALS['historiqueDetail'] = $historique;
        $GLOBALS['scolariteDetail'] = $scolarite;
        $GLOBALS['scolariteBloquee'] = $scolariteBloquee;
    }

    /**
     * Valide une candidature
     * POST /admin/candidatures/{id}/valider
     */
    public function valider() {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=admin_candidatures');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error_message'] = 'ID candidature manquant';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Récupérer la candidature
        $candidature = $this->getCandidatureById($id);
        if (!$candidature) {
            $_SESSION['error_message'] = 'Candidature non trouvée';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Vérifier que le statut est 'En attente' (correspond à soumise dans la BD)
        if ($candidature['statut_candidature'] !== 'En attente') {
            $_SESSION['error_message'] = 'La candidature doit être au statut "En attente" pour être validée';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Vérifier la scolarité si bloquante
        if ($this->VERIF_SCOLARITE_BLOQUANTE) {
            $scolarite = $this->scolarite->getScolariteEtudiant($candidature['num_etu']);
            if ($scolarite && $scolarite['reste_a_payer'] > $this->DETTE_TOLERANCE_MAX) {
                $_SESSION['error_message'] = "Validation impossible: scolarité non soldée (" . number_format($scolarite['reste_a_payer'], 0, ',', ' ') . " FCFA restants)";
                header('Location: ?page=admin_candidatures');
                exit;
            }
        }

        // Récupérer l'ID du validateur
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        $id_validateur = $pers_admin ? $pers_admin->id_pers_admin : null;

        // Mettre à jour la candidature
        $sql = "
            UPDATE candidature_soutenance 
            SET statut_candidature = 'Validée',
                id_pers_admin = ?,
                date_traitement = NOW()
            WHERE id_candidature = ?
        ";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id_validateur, $id]);

        if ($success) {
            // Logger l'action
            $this->auditLog->logValidation($_SESSION['id_utilisateur'] ?? 0, 'candidature', 'Succès');

            // Envoyer l'email de notification
            $this->envoyerEmailValidation($candidature);

            $_SESSION['success_message'] = "Candidature validée. L'étudiant peut maintenant déposer son rapport.";
        } else {
            $this->auditLog->logValidation($_SESSION['id_utilisateur'] ?? 0, 'candidature', 'Erreur');
            $_SESSION['error_message'] = 'Erreur lors de la validation';
        }

        header('Location: ?page=admin_candidatures');
        exit;
    }

    /**
     * Rejette une candidature
     * POST /admin/candidatures/{id}/rejeter
     */
    public function rejeter() {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=admin_candidatures');
            exit;
        }

        $id = $_GET['id'] ?? null;
        $motif = $_POST['motif_rejet'] ?? '';
        $commentaire = $_POST['commentaire_rejet'] ?? '';

        if (!$id) {
            $_SESSION['error_message'] = 'ID candidature manquant';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        if (empty($motif) || empty($commentaire)) {
            $_SESSION['error_message'] = 'Le motif et le commentaire de rejet sont obligatoires';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Récupérer la candidature
        $candidature = $this->getCandidatureById($id);
        if (!$candidature) {
            $_SESSION['error_message'] = 'Candidature non trouvée';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Vérifier que le statut est 'En attente'
        if ($candidature['statut_candidature'] !== 'En attente') {
            $_SESSION['error_message'] = 'La candidature doit être au statut "En attente" pour être rejetée';
            header('Location: ?page=admin_candidatures');
            exit;
        }

        // Récupérer l'ID du validateur
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        $id_validateur = $pers_admin ? $pers_admin->id_pers_admin : null;

        // Mettre à jour la candidature
        $sql = "
            UPDATE candidature_soutenance 
            SET statut_candidature = 'Rejetée',
                id_pers_admin = ?,
                date_traitement = NOW(),
                commentaire_admin = ?
            WHERE id_candidature = ?
        ";

        $commentaireComplet = "Motif: " . $motif . "\n\nCommentaire: " . $commentaire;
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id_validateur, $commentaireComplet, $id]);

        if ($success) {
            // Logger l'action
            $this->auditLog->logRejet($_SESSION['id_utilisateur'] ?? 0, 'candidature', 'Succès');

            // Envoyer l'email de notification
            $this->envoyerEmailRejet($candidature, $motif, $commentaire);

            $_SESSION['success_message'] = "Candidature rejetée. Notification envoyée à l'étudiant avec motif.";
        } else {
            $this->auditLog->logRejet($_SESSION['id_utilisateur'] ?? 0, 'candidature', 'Erreur');
            $_SESSION['error_message'] = 'Erreur lors du rejet';
        }

        header('Location: ?page=admin_candidatures');
        exit;
    }

    /**
     * Récupère les motifs de rejet paramétrables
     */
    public function getMotifsRejet() {
        // Retourner les motifs par défaut (peut être remplacé par une table en DB)
        return [
            'scolarite_non_soldee' => 'Scolarité non soldée',
            'duree_stage_insuffisante' => 'Durée de stage insuffisante',
            'sujet_inapproprie' => 'Sujet de stage inapproprié',
            'entreprise_non_valide' => 'Entreprise non validée',
            'documents_manquants' => 'Documents manquants',
            'autre' => 'Autre (précisez)'
        ];
    }

    // Méthodes privées helpers

    private function getCandidatureById($id) {
        $sql = "SELECT * FROM candidature_soutenance WHERE id_candidature = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getAnneesAcademiques() {
        $sql = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFilieres() {
        $sql = "SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStatistiquesCandidatures() {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut_candidature = 'En attente' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut_candidature = 'Validée' THEN 1 ELSE 0 END) as validee,
                SUM(CASE WHEN statut_candidature = 'Rejetée' THEN 1 ELSE 0 END) as rejetee
            FROM candidature_soutenance
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getHistoriqueCandidatures($num_etu) {
        $sql = "
            SELECT 
                cs.*,
                pa.nom_pers_admin,
                pa.prenom_pers_admin
            FROM candidature_soutenance cs
            LEFT JOIN personnel_admin pa ON cs.id_pers_admin = pa.id_pers_admin
            WHERE cs.num_etu = ?
            ORDER BY cs.date_candidature DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$num_etu]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function envoyerEmailValidation($candidature) {
        // Récupérer l'email de l'étudiant
        $sql = "SELECT email_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$candidature['num_etu']]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant && !empty($etudiant['email_etu'])) {
            $sujet = "Votre candidature a été validée";
            $message = "Bonjour {$etudiant['prenom_etu']} {$etudiant['nom_etu']},\n\n";
            $message .= "Nous avons le plaisir de vous informer que votre candidature pour la soutenance a été validée.\n\n";
            $message .= "Vous pouvez maintenant déposer votre rapport de stage.\n\n";
            $message .= "Cordialement,\nL'équipe de la scolarité";

            $this->emailService->sendEmail($etudiant['email_etu'], $sujet, $message);
        }
    }

    private function envoyerEmailRejet($candidature, $motif, $commentaire) {
        // Récupérer l'email de l'étudiant
        $sql = "SELECT email_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$candidature['num_etu']]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant && !empty($etudiant['email_etu'])) {
            $sujet = "Votre candidature n'a pas été retenue";
            $message = "Bonjour {$etudiant['prenom_etu']} {$etudiant['nom_etu']},\n\n";
            $message .= "Nous vous informons que votre candidature pour la soutenance n'a pas été retenue.\n\n";
            $message .= "Motif : $motif\n";
            $message .= "Commentaire : $commentaire\n\n";
            $message .= "Vous pouvez soumettre une nouvelle candidature après correction.\n\n";
            $message .= "Cordialement,\nL'équipe de la scolarité";

            $this->emailService->sendEmail($etudiant['email_etu'], $sujet, $message);
        }
    }
}
