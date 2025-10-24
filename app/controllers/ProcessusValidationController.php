<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EvaluationRapport.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../utils/permissions.php';

class ProcessusValidationController {
    
    private $pdo;
    private $evaluationRapport;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->evaluationRapport = new EvaluationRapport();
    }
    
    /**
     * Récupère les statistiques pour le tableau de bord
     */
    public function getStatistiques() {
        try {
            // Total des rapports approuvés par la chargée de communication
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT a.id_rapport) as total_rapports
                FROM approuver a
                WHERE a.decision = 'approuve'
            ");
            $stmt->execute();
            $totalRapports = $stmt->fetch(PDO::FETCH_ASSOC)['total_rapports'];
            
            // Rapports en cours d'évaluation (approuvés mais pas encore finalisés)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT a.id_rapport) as en_cours
                FROM approuver a
                LEFT JOIN valider v ON a.id_rapport = v.id_rapport
                WHERE a.decision = 'approuve' AND v.id_rapport IS NULL
            ");
            $stmt->execute();
            $enCours = $stmt->fetch(PDO::FETCH_ASSOC)['en_cours'];
            
            // Rapports validés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as valides
                FROM valider v
                WHERE v.decision_validation = 'valider'
            ");
            $stmt->execute();
            $valides = $stmt->fetch(PDO::FETCH_ASSOC)['valides'];
            
            // Rapports rejetés par la commission
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT v.id_rapport) as rejetes
                FROM valider v
                WHERE v.decision_validation = 'rejeter'
            ");
            $stmt->execute();
            $rejetes = $stmt->fetch(PDO::FETCH_ASSOC)['rejetes'];
            
            return [
                'total_rapports' => $totalRapports,
                'en_cours' => $enCours,
                'valides' => $valides,
                'rejetes' => $rejetes
            ];
            
        } catch (Exception $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            return [
                'total_rapports' => 0,
                'en_cours' => 0,
                'valides' => 0,
                'rejetes' => 0
            ];
        }
    }
    
    /**
     * Récupère tous les rapports approuvés avec leurs évaluations
     */
    public function getRapportsAvecEvaluations() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.id_rapport,
                    r.nom_rapport,
                    r.theme_rapport,
                    r.date_rapport,
                    r.etape_validation,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    e.promotion_etu,
                    a.date_approv,
                    a.commentaire_approv,
                    pa.nom_pers_admin,
                    pa.prenom_pers_admin
                FROM approuver a
                JOIN rapport_etudiants r ON a.id_rapport = r.id_rapport
                JOIN etudiants e ON r.num_etu = e.num_etu
                JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
                WHERE a.decision = 'approuve'
                ORDER BY a.date_approv DESC
            ");
            $stmt->execute();
            $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque rapport, récupérer les évaluations
            foreach ($rapports as &$rapport) {
                $rapport['evaluations'] = $this->getEvaluationsRapport($rapport['id_rapport']);
                $rapport['statut_vote'] = $this->getStatutVoteRapport($rapport['id_rapport']);
            }
            
            return $rapports;
            
        } catch (Exception $e) {
            error_log("Erreur récupération rapports: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les évaluations d'un rapport spécifique
     */
    private function getEvaluationsRapport($id_rapport) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    er.id_evaluation,
                    er.id_evaluateur,
                    er.decision_evaluation,
                    er.commentaire,
                    er.date_evaluation,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant
                FROM evaluations_rapports er
                JOIN enseignants e ON er.id_evaluateur = e.id_enseignant
                WHERE er.id_rapport = ?
                ORDER BY er.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération évaluations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère le statut de vote d'un rapport
     */
    private function getStatutVoteRapport($id_rapport) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_votes,
                    COUNT(CASE WHEN decision_evaluation = 'valider' THEN 1 END) as votes_valider,
                    COUNT(CASE WHEN decision_evaluation = 'rejeter' THEN 1 END) as votes_rejeter
                FROM evaluations_rapports 
                WHERE id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalVotes = $result['total_votes'];
            $votesValider = $result['votes_valider'];
            $votesRejeter = $result['votes_rejeter'];
            
            // Vérifier si le rapport a été finalisé
            $stmt = $this->pdo->prepare("
                SELECT decision_validation 
                FROM valider 
                WHERE id_rapport = ? 
                ORDER BY date_validation DESC 
                LIMIT 1
            ");
            $stmt->execute([$id_rapport]);
            $decisionFinale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($decisionFinale) {
                return [
                    'statut' => $decisionFinale['decision_validation'] === 'valider' ? 'valide' : 'rejete',
                    'message' => $decisionFinale['decision_validation'] === 'valider' ? 
                        "Validé ($totalVotes/4 votes)" : "Rejeté ($totalVotes/4 votes)",
                    'total_votes' => $totalVotes,
                    'votes_valider' => $votesValider,
                    'votes_rejeter' => $votesRejeter,
                    'finalise' => true
                ];
            } else {
                if ($totalVotes == 0) {
                    return [
                        'statut' => 'en_cours',
                        'message' => "En attente (0/4 votes)",
                        'total_votes' => 0,
                        'votes_valider' => 0,
                        'votes_rejeter' => 0,
                        'finalise' => false
                    ];
                } elseif($totalVotes == 4){
                    return [
                        'statut' => 'pret_a_finaliser',
                        'message' => "Prêt à finaliser (4/4 votes)",
                        'total_votes' => 4,
                        'votes_valider' => $votesValider,
                        'votes_rejeter' => $votesRejeter,
                        'finalise' => false
                    ];
                } else {
                    return [
                        'statut' => 'en_cours',
                        'message' => "En cours ($totalVotes/4 votes)",
                        'total_votes' => $totalVotes,
                        'votes_valider' => $votesValider,
                        'votes_rejeter' => $votesRejeter,
                        'finalise' => false
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Erreur récupération statut vote: " . $e->getMessage());
            return [
                'statut' => 'erreur',
                'message' => 'Erreur',
                'total_votes' => 0,
                'votes_valider' => 0,
                'votes_rejeter' => 0,
                'finalise' => false
            ];
        }
    }
    
    /**
     * Récupère la liste des membres de la commission
     */
    public function getMembresCommission() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant
                FROM enseignants e
                JOIN utilisateur u ON e.mail_enseignant = u.login_utilisateur
                WHERE u.id_GU = 11 or u.id_GU = 5
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur récupération membres commission: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les données complètes pour la page
     */
    public function getDonneesPage() {
        return [
            'statistiques' => $this->getStatistiques(),
            'rapports' => $this->getRapportsAvecEvaluations(),
            'membres_commission' => $this->getMembresCommission()
        ];
    }
    
    /**
     * Vérifie si un ID enseignant existe dans la table enseignants
     */
    public function verifierIdEnseignant($id_enseignant) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM enseignants WHERE id_enseignant = ?");
            $stmt->execute([$id_enseignant]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Erreur vérification ID enseignant: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Finalise la décision pour un rapport
     */
    public function finaliserRapport($id_rapport, $id_enseignant, $commentaire = null) {
        try {
            // Compter le nombre de validations 'valider'
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM evaluations_rapports WHERE id_rapport = ? AND decision_evaluation = 'valider'");
            $stmt->execute([$id_rapport]);
            $row = $stmt->fetch();
            $totalValide = $row['total'];
            
            // Décision
            $decision = ($totalValide >= 4) ? 'valider' : 'rejeter';
            
            // Préparer le commentaire
            $commentaireFinal = $commentaire ? trim($commentaire) : 'Décision finale automatique';
            if (empty($commentaireFinal)) {
                $commentaireFinal = 'Décision finale automatique';
            }
            
            // Insérer dans la table valider
            $stmtInsert = $this->pdo->prepare("INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation) VALUES (?, ?, NOW(), ?, ?)");
            $stmtInsert->execute([$id_enseignant, $id_rapport, $commentaireFinal, $decision]);
            
            // Mettre à jour le statut du rapport et l'étape de validation
            $etapeValidation = ($decision === 'valider') ? 'valide' : 'desapprouve_commission';
            $stmtUpdate = $this->pdo->prepare("UPDATE rapport_etudiants SET statut_rapport = ?, etape_validation = ? WHERE id_rapport = ?");
            $stmtUpdate->execute([$decision, $etapeValidation, $id_rapport]);
            
            return [
                'success' => true,
                'message' => 'Rapport finalisé avec succès. Décision : ' . ($decision === 'valider' ? 'Validé' : 'Rejeté') . ' (' . $totalValide . '/4 votes favorables)'
            ];
            
        } catch (Exception $e) {
            error_log("Erreur lors de la finalisation: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la finalisation : ' . $e->getMessage()
            ];
        }
    }
} 