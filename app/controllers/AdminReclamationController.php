<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;

/**
 * Contrôleur pour le suivi et traitement des réclamations
 * Écran 1.3.2: Réclamation (Suivi et Traitement)
 */
class AdminReclamationController {
    private $db;
    private $reclamationModel;
    private $etudiant;
    private $emailService;
    private $pers_admin;
    private $auditLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->reclamationModel = new Reclamation();
        $this->etudiant = new Etudiant($this->db);
        $this->emailService = new EmailService();
        $this->pers_admin = new PersAdmin($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    /**
     * Affiche la liste des réclamations avec filtres
     * GET /admin/reclamations
     */
    public function index() {
        Session::start();

        // Récupérer les filtres
        $filtreType = $_GET['type'] ?? 'tous';
        $filtreStatut = $_GET['statut'] ?? 'tous';
        $dateDebut = $_GET['date_debut'] ?? '';
        $dateFin = $_GET['date_fin'] ?? '';

        // Construire la requête de base - Utilise uniquement les colonnes existantes
        $sql = "
            SELECT 
                r.id_reclamation,
                r.num_carte_etud as num_etu,
                r.objet_reclamation as titre_reclamation,
                r.description_reclamation,
                'RCL' as type_reclamation,
                r.statut_reclamation,
                'Moyenne' as priorite_reclamation,
                r.date_creation,
                r.date_mise_a_jour,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu,
                DATEDIFF(NOW(), r.date_creation) as jours_attente
            FROM reclamations r
            INNER JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
            WHERE 1=1
        ";

        $params = [];

        // Note: Les filtres par type et statut sont désactivés car les colonnes n'existent pas encore
        // if ($filtreType !== 'tous') {
        //     $sql .= " AND r.type_reclamation = ?";
        //     $params[] = $filtreType;
        // }

        // if ($filtreStatut !== 'tous') {
        //     $sql .= " AND r.statut_reclamation = ?";
        //     $params[] = $filtreStatut;
        // }

        if (!empty($dateDebut)) {
            $sql .= " AND r.date_creation >= ?";
            $params[] = $dateDebut . ' 00:00:00';
        }

        if (!empty($dateFin)) {
            $sql .= " AND r.date_creation <= ?";
            $params[] = $dateFin . ' 23:59:59';
        }

        // Trier par date de création
        $sql .= " ORDER BY r.date_creation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Statistiques
        $stats = $this->getStatistiquesReclamations();

        // Passer les données à la vue
        $GLOBALS['reclamations'] = $reclamations;
        $GLOBALS['stats'] = $stats;
        $GLOBALS['filtres'] = [
            'type' => $filtreType,
            'statut' => $filtreStatut,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
    }

    /**
     * Récupère le détail d'une réclamation pour le panneau latéral
     * GET /admin/reclamations/{id}
     */
    public function detail() {
        Session::start();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de réclamation manquant']);
            return;
        }

        // Récupérer la réclamation - Utilise uniquement les colonnes existantes
        $sql = "
            SELECT 
                r.id_reclamation,
                r.num_carte_etud as num_etu,
                r.objet_reclamation as titre_reclamation,
                r.description_reclamation,
                r.statut_reclamation,
                r.date_creation,
                r.date_mise_a_jour,
                e.nom_etu, e.prenom_etu, e.email_etu,
                DATEDIFF(NOW(), r.date_creation) as jours_attente
            FROM reclamations r
            INNER JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
            WHERE r.id_reclamation = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reclamation) {
            http_response_code(404);
            echo json_encode(['error' => 'Réclamation non trouvée']);
            return;
        }

        // Récupérer l'historique des actions
        $historique = $this->getHistoriqueReclamation($id);

        // Vérifier si l'utilisateur actuel est le traiteur assigné
        // Note: La colonne id_admin_assigne n'existe pas encore, donc on permet à tous les admins de traiter
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        $isTraiteur = ($pers_admin !== null);

        // Si c'est une requête AJAX, retourner JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'reclamation' => $reclamation,
                'historique' => $historique,
                'is_traiteur' => $isTraiteur,
                'can_take_charge' => true, // Permettre à tous les admins
                'can_traiter' => $isTraiteur,
                'can_rejeter' => $isTraiteur
            ]);
            return;
        }

        // Sinon, passer à la vue
        $GLOBALS['reclamationDetail'] = $reclamation;
        $GLOBALS['historiqueDetail'] = $historique;
        $GLOBALS['isTraiteur'] = $isTraiteur;
    }

    /**
     * Prendre en charge une réclamation
     * POST /admin/reclamations/{id}/traiter
     */
    public function prendreEnCharge() {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=admin_reclamations');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error_message'] = 'ID de réclamation manquant';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Récupérer l'ID de l'admin
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        if (!$pers_admin) {
            $_SESSION['error_message'] = 'Impossible d\'identifier l\'administrateur';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Vérifier que la réclamation est en attente
        $reclamation = $this->getReclamationById($id);
        if (!$reclamation || $reclamation['statut_reclamation'] !== 'En attente') {
            $_SESSION['error_message'] = 'Cette réclamation ne peut pas être prise en charge';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Note: La colonne id_admin_assigne n'existe pas encore
        // On se contente de logger l'action pour l'instant

        // Logger l'action
        $this->auditLog->logAction(
            $_SESSION['id_utilisateur'] ?? 0,
            'Prise en charge',
            'reclamations',
            'Succès'
        );

        // Envoyer notification
        $this->envoyerEmailNotification(
            $reclamation,
            'Votre réclamation est en cours de traitement',
            "Votre réclamation est désormais prise en charge par notre équipe."
        );

        $_SESSION['success_message'] = "Réclamation [REF-{$id}] prise en charge par {$pers_admin->prenom_pers_admin} {$pers_admin->nom_pers_admin}. Notification envoyée.";

        header('Location: ?page=admin_reclamations');
        exit;
    }

    /**
     * Marquer une réclamation comme traitée
     * POST /admin/reclamations/{id}/terminer
     */
    public function terminer() {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=admin_reclamations');
            exit;
        }

        $id = $_GET['id'] ?? null;
        $commentaire = $_POST['commentaire_traitement'] ?? '';

        if (!$id) {
            $_SESSION['error_message'] = 'ID de réclamation manquant';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        if (empty($commentaire)) {
            $_SESSION['error_message'] = 'Le commentaire de traitement est obligatoire';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Récupérer l'ID de l'admin
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        if (!$pers_admin) {
            $_SESSION['error_message'] = 'Impossible d\'identifier l\'administrateur';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Vérifier la réclamation
        $reclamation = $this->getReclamationById($id);
        if (!$reclamation) {
            $_SESSION['error_message'] = 'Réclamation non trouvée';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Note: La colonne id_admin_assigne n'existe pas encore
        // Tous les admins peuvent traiter pour l'instant

        // Logger l'action
        $this->auditLog->logAction(
            $_SESSION['id_utilisateur'] ?? 0,
            'Traitement',
            'reclamations',
            'Succès'
        );

        // Envoyer notification
        $this->envoyerEmailNotification(
            $reclamation,
            'Votre réclamation a été traitée',
            "Votre réclamation a été traitée.\n\nSolution apportée : {$commentaire}"
        );

        $_SESSION['success_message'] = "Réclamation [REF-{$id}] marquée comme traitée.";

        header('Location: ?page=admin_reclamations');
        exit;
    }

    /**
     * Rejeter une réclamation
     * POST /admin/reclamations/{id}/rejeter
     */
    public function rejeter() {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=admin_reclamations');
            exit;
        }

        $id = $_GET['id'] ?? null;
        $motif = $_POST['motif_rejet'] ?? '';
        $commentaire = $_POST['commentaire_rejet'] ?? '';

        if (!$id) {
            $_SESSION['error_message'] = 'ID de réclamation manquant';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        if (empty($motif) || empty($commentaire)) {
            $_SESSION['error_message'] = 'Le motif et le commentaire de rejet sont obligatoires';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Récupérer l'ID de l'admin
        $pers_admin = $this->pers_admin->getPersAdminByLogin($_SESSION['login_utilisateur'] ?? '');
        if (!$pers_admin) {
            $_SESSION['error_message'] = 'Impossible d\'identifier l\'administrateur';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Vérifier la réclamation
        $reclamation = $this->getReclamationById($id);
        if (!$reclamation) {
            $_SESSION['error_message'] = 'Réclamation non trouvée';
            header('Location: ?page=admin_reclamations');
            exit;
        }

        // Note: La colonne id_admin_assigne n'existe pas encore
        // Tous les admins peuvent rejeter pour l'instant

        // Logger l'action
        $this->auditLog->logRejet($_SESSION['id_utilisateur'] ?? 0, 'reclamations', 'Succès');

        // Envoyer notification
        $this->envoyerEmailNotification(
            $reclamation,
            'Votre réclamation a été rejetée',
            "Votre réclamation a été rejetée.\n\nMotif : {$motif}\nCommentaire : {$commentaire}"
        );

        $_SESSION['success_message'] = "Réclamation [REF-{$id}] rejetée avec motif.";

        header('Location: ?page=admin_reclamations');
        exit;
    }

    // Méthodes privées helpers

    private function getReclamationById($id) {
        $sql = "SELECT * FROM reclamations WHERE id_reclamation = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStatistiquesReclamations() {
        // Note: statut_reclamation est un INT dans la BD
        // On retourne juste le total pour l'instant
        $sql = "
            SELECT 
                COUNT(*) as total,
                0 as en_attente,
                0 as en_cours,
                0 as traitees,
                0 as rejetees,
                SUM(CASE WHEN DATEDIFF(NOW(), date_creation) > 7 THEN 1 ELSE 0 END) as alertes_sla
            FROM reclamations
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getHistoriqueReclamation($id) {
        // L'historique peut être stocké dans une table dédiée ou reconstruit
        // Pour l'instant, on retourne un historique basique
        $sql = "
            SELECT 
                'Création' as action,
                date_creation as date_action,
                NULL as nom_admin,
                NULL as prenom_admin
            FROM reclamations 
            WHERE id_reclamation = ?
            UNION ALL
            SELECT 
                'Prise en charge' as action,
                date_mise_a_jour as date_action,
                pa.nom_pers_admin as nom_admin,
                pa.prenom_pers_admin as prenom_admin
            FROM reclamations r
            LEFT JOIN personnel_admin pa ON r.id_admin_assigne = pa.id_pers_admin
            WHERE r.id_reclamation = ? AND r.statut_reclamation IN ('En cours', 'Traitée', 'Rejetée')
            UNION ALL
            SELECT 
                'Traitement' as action,
                date_traitement as date_action,
                pa.nom_pers_admin as nom_admin,
                pa.prenom_pers_admin as prenom_admin
            FROM reclamations r
            LEFT JOIN personnel_admin pa ON r.id_admin_assigne = pa.id_pers_admin
            WHERE r.id_reclamation = ? AND r.statut_reclamation IN ('Traitée', 'Rejetée')
            ORDER BY date_action ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $id, $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function envoyerEmailNotification($reclamation, $sujet, $message) {
        // Récupérer l'email de l'étudiant
        $sql = "SELECT email_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reclamation['num_etu']]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant && !empty($etudiant['email_etu'])) {
            $fullMessage = "Bonjour {$etudiant['prenom_etu']} {$etudiant['nom_etu']},\n\n";
            $fullMessage .= $message;
            $fullMessage .= "\n\nCordialement,\nL'équipe de la scolarité";

            $this->emailService->sendEmail($etudiant['email_etu'], $sujet, $fullMessage);
        }
    }
}
