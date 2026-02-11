<?php
require_once __DIR__ . '/../config/database.php';

class EvaluationRapport
{

    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
    }

    /**
     * Ajoute une évaluation pour un rapport
     */
    public function ajouterEvaluation($id_rapport, $id_evaluateur, $decision, $commentaire)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO evaluations_rapports (id_rapport, id_evaluateur, decision_evaluation, commentaire, date_evaluation)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$id_rapport, $id_evaluateur, $decision, $commentaire]);
        } catch (PDOException $e) {
            error_log("Erreur ajout évaluation rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une évaluation existante
     */
    public function mettreAJourEvaluation($id_evaluation, $decision, $commentaire)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE evaluations_rapports 
                SET decision_evaluation = ?, commentaire = ?, date_modification = NOW()
                WHERE id_evaluation = ?
            ");
            return $stmt->execute([$decision, $commentaire, $id_evaluation]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour évaluation rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un évaluateur a déjà évalué un rapport
     */
    public function evaluationExiste($id_rapport, $id_evaluateur)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_evaluation FROM evaluations_rapports 
                WHERE id_rapport = ? AND id_evaluateur = ?
            ");
            $stmt->execute([$id_rapport, $id_evaluateur]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur vérification évaluation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les évaluations d'un rapport
     */
    public function getEvaluationsRapport($id_rapport)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, 
                       ens.nom_enseignant,
                       ens.prenom_enseignant,
                       ens.mail_enseignant
                FROM evaluations_rapports e
                JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant
                WHERE e.id_rapport = ?
                ORDER BY e.date_evaluation DESC
            ");
            $stmt->execute([$id_rapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération évaluations rapport: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le statut des votes pour un rapport
     */
    public function getStatutVotes($id_rapport, $nombreMembresCommission = 4)
    {
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
            $resultats = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalVotes = $resultats['total_votes'];
            $votesValider = $resultats['votes_valider'];
            $votesRejeter = $resultats['votes_rejeter'];

            // Si tous les membres ont voté
            if ($totalVotes >= $nombreMembresCommission) {
                // Si tous ont validé
                if ($votesValider == $nombreMembresCommission) {
                    return [
                        'statut' => 'unanimite_valider',
                        'message' => 'Tous les membres ont validé le rapport',
                        'peut_finaliser' => true,
                        'decision_finale' => 'valider'
                    ];
                }
                // Si au moins un a rejeté
                elseif ($votesRejeter > 0) {
                    return [
                        'statut' => 'rejet_commission',
                        'message' => 'Le rapport a été rejeté par au moins un membre',
                        'peut_finaliser' => true,
                        'decision_finale' => 'rejeter'
                    ];
                }
            }

            // En cours de vote
            return [
                'statut' => 'en_cours',
                'message' => "Vote en cours ($totalVotes/$nombreMembresCommission membres ont voté)",
                'peut_finaliser' => false,
                'votes_valider' => $votesValider,
                'votes_rejeter' => $votesRejeter,
                'total_votes' => $totalVotes,
                'total_membres' => $nombreMembresCommission
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération statut votes: " . $e->getMessage());
            return [
                'statut' => 'erreur',
                'message' => 'Erreur lors de la récupération du statut',
                'peut_finaliser' => false
            ];
        }
    }

    /**
     * Récupère les rapports avec leur statut de vote
     */
    public function getRapportsAvecStatutVote()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    r.id_rapport,
                    r.nom_rapport,
                    r.theme_rapport,
                    r.date_rapport,
                    r.etape_validation,
                    r.statut_rapport,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    e.promotion_etu,
                    d.date_depot,
                    COUNT(ev.id_evaluation) as total_votes,
                    COUNT(CASE WHEN ev.decision_evaluation = 'valider' THEN 1 END) as votes_valider,
                    COUNT(CASE WHEN ev.decision_evaluation = 'rejeter' THEN 1 END) as votes_rejeter
                FROM rapport_etudiants r
                JOIN etudiants e ON r.num_etu = e.num_carte_etud
                JOIN deposer d ON r.id_rapport = d.id_rapport
                LEFT JOIN evaluations_rapports ev ON r.id_rapport = ev.id_rapport
                WHERE r.etape_validation = 'approuve_communication'
                GROUP BY r.id_rapport, r.nom_rapport, r.theme_rapport, r.date_rapport, 
                         r.etape_validation, r.statut_rapport, e.nom_etu, e.prenom_etu, 
                         e.email_etu, e.promotion_etu, d.date_depot
                ORDER BY d.date_depot DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports avec statut: " . $e->getMessage());
            return [];
        }
    }
}