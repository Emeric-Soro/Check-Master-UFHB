<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';

use PDO;
use Exception;

/**
 * Service metier pour la Fiche Commission (P2.1)
 *
 * Agregation : membres commission (type_utilisateur),
 * rapports evalues/en attente, decisions, stats vote, planning seances
 */
class FicheCommissionService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Membres de la commission (id_GU=11)
     */
    public function getMembresCommission(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    e.mail_enseignant,
                    e.tel_enseignant,
                    u.id_utilisateur,
                    u.login_utilisateur,
                    u.statut_utilisateur,
                    gu.lib_GU
                FROM enseignants e
                JOIN utilisateur u ON u.login_utilisateur = e.mail_enseignant
                JOIN groupe_utilisateur gu ON u.id_GU = gu.id_GU
                WHERE u.id_GU = 11
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getMembresCommission - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rapports evalues avec leurs votes
     */
    public function getRapportsEvalues(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    r.id_rapport,
                    r.theme_rapport,
                    r.nom_rapport,
                    r.date_redaction_rapport,
                    r.statut_rapport,
                    e.nom_etu,
                    e.prenom_etu,
                    e.num_carte_etud,
                    e.promotion_etu,
                    COALESCE(v.decision_validation, 'en_attente') AS decision_finale,
                    v.date_validation,
                    v.commentaire_validation,
                    (SELECT COUNT(*) FROM evaluations_rapports er2 WHERE er2.id_rapport = r.id_rapport) AS total_votes,
                    (SELECT COUNT(*) FROM evaluations_rapports er3 WHERE er3.id_rapport = r.id_rapport AND er3.decision_evaluation = 'valider') AS votes_valider,
                    (SELECT COUNT(*) FROM evaluations_rapports er4 WHERE er4.id_rapport = r.id_rapport AND er4.decision_evaluation = 'rejeter') AS votes_rejeter
                FROM rapport_etudiants r
                JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                LEFT JOIN valider v ON v.id_rapport = r.id_rapport
                ORDER BY COALESCE(v.date_validation, r.date_redaction_rapport) DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getRapportsEvalues - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rapports en attente d'evaluation
     */
    public function getRapportsEnAttente(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    r.id_rapport,
                    r.theme_rapport,
                    r.nom_rapport,
                    r.date_redaction_rapport,
                    e.nom_etu,
                    e.prenom_etu,
                    e.num_carte_etud,
                    e.promotion_etu
                FROM rapport_etudiants r
                JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                WHERE r.id_rapport NOT IN (
                    SELECT DISTINCT id_rapport FROM evaluations_rapports
                )
                AND r.statut_rapport = 'en_attente'
                ORDER BY r.date_redaction_rapport ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getRapportsEnAttente - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Statistiques globales des votes
     */
    public function getStatsVote(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN er.decision_evaluation = 'valider' THEN 1 ELSE 0 END), 0) AS total_valider,
                    COALESCE(SUM(CASE WHEN er.decision_evaluation = 'rejeter' THEN 1 ELSE 0 END), 0) AS total_rejeter,
                    COUNT(DISTINCT er.id_rapport) AS rapports_votes,
                    COUNT(DISTINCT er.id_evaluateur) AS evaluateurs_actifs
                FROM evaluations_rapports er
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getStatsVote - ' . $e->getMessage());
            return [
                'total_valider' => 0,
                'total_rejeter' => 0,
                'rapports_votes' => 0,
                'evaluateurs_actifs' => 0,
            ];
        }
    }

    /**
     * Decisions recentes
     */
    public function getDecisionsRecentes(int $limite = 10): array
    {
        try {
            $limite = max(1, $limite);
            $stmt = $this->pdo->prepare("
                SELECT
                    v.id_rapport,
                    v.decision_validation,
                    v.date_validation,
                    v.commentaire_validation,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    r.theme_rapport,
                    et.nom_etu,
                    et.prenom_etu
                FROM valider v
                JOIN enseignants e ON v.id_enseignant = e.id_enseignant
                JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                JOIN etudiants et ON (r.num_etu = et.num_carte_etud OR r.num_etu = et.num_ident_etud)
                ORDER BY v.date_validation DESC
                LIMIT {$limite}
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getDecisionsRecentes - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Planning des seances (via programme_soutenance)
     */
    public function getPlanningSeances(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    ps.num_soutenance,
                    ps.theme_soutenance,
                    ps.date_soutenance,
                    ps.heure_soutenance,
                    s.lib_salle,
                    e.nom_etu,
                    e.prenom_etu
                FROM programmer_soutenance ps
                JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC
                LIMIT 20
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FicheCommissionService::getPlanningSeances - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Donnees completes pour la vue
     */
    public function getDonneesPage(): array
    {
        return [
            'membres' => $this->getMembresCommission(),
            'rapports_evalues' => $this->getRapportsEvalues(),
            'rapports_attente' => $this->getRapportsEnAttente(),
            'stats_vote' => $this->getStatsVote(),
            'decisions' => $this->getDecisionsRecentes(),
            'planning' => $this->getPlanningSeances(),
        ];
    }
}
