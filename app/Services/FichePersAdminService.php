<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';

use PDO;
use Exception;

/**
 * Service metier pour la Fiche Personnel Administratif (P2.2)
 *
 * Identite, poste, compte, actions realisees (candidatures traitees),
 * historique pister
 */
class FichePersAdminService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Informations d'identite du personnel admin
     */
    public function getIdentite(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    pa.id_pers_admin,
                    pa.nom_pers_admin,
                    pa.prenom_pers_admin,
                    pa.email_pers_admin,
                    pa.tel_pers_admin,
                    pa.poste,
                    pa.date_embauche,
                    pa.id_genre,
                    g.libelle_genre
                FROM personnel_admin pa
                LEFT JOIN genre g ON pa.id_genre = g.id_genre
                WHERE pa.id_pers_admin = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('FichePersAdminService::getIdentite - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compte utilisateur associe
     */
    public function getCompteUtilisateur(int $idPersAdmin): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    u.id_utilisateur,
                    u.login_utilisateur,
                    u.statut_utilisateur,
                    u.id_GU,
                    gu.lib_GU,
                    tu.lib_type_utilisateur
                FROM utilisateur u
                JOIN personnel_admin pa ON pa.email_pers_admin = u.login_utilisateur
                JOIN groupe_utilisateur gu ON u.id_GU = gu.id_GU
                JOIN type_utilisateur tu ON u.id_type_utilisateur = tu.id_type_utilisateur
                WHERE pa.id_pers_admin = ?
                LIMIT 1
            ");
            $stmt->execute([$idPersAdmin]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('FichePersAdminService::getCompteUtilisateur - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Candidatures traitees par ce personnel admin
     */
    public function getCandidaturesTraitees(int $idPersAdmin, int $limite = 20): array
    {
        try {
            $limite = max(1, $limite);
            $stmt = $this->pdo->prepare("
                SELECT
                    cs.id_candidature,
                    cs.num_etu,
                    cs.date_candidature,
                    cs.statut_candidature,
                    cs.date_traitement,
                    cs.commentaire_admin,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM candidature_soutenance cs
                JOIN etudiants e ON (cs.num_etu = e.num_carte_etud OR cs.num_etu = e.num_ident_etud)
                WHERE cs.id_pers_admin = :id_pers_admin
                ORDER BY cs.date_traitement DESC, cs.date_candidature DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':id_pers_admin', $idPersAdmin, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FichePersAdminService::getCandidaturesTraitees - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actions auditees (pister) pour ce personnel admin
     */
    public function getHistoriqueActions(int $idPersAdmin, int $limite = 30): array
    {
        try {
            $limite = max(1, $limite);
            // Resoudre l'utilisateur d'abord
            $compte = $this->getCompteUtilisateur($idPersAdmin);
            $idUtilisateur = $compte['id_utilisateur'] ?? null;
            if (!$idUtilisateur) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    p.id_piste,
                    p.action,
                    p.statut_action,
                    p.nom_table,
                    p.date_creation
                FROM pister p
                WHERE p.id_utilisateur = :id_utilisateur
                ORDER BY p.date_creation DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':id_utilisateur', $idUtilisateur, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FichePersAdminService::getHistoriqueActions - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resume des actions par type
     */
    public function getStatsActions(int $idPersAdmin): array
    {
        try {
            $compte = $this->getCompteUtilisateur($idPersAdmin);
            $idUtilisateur = $compte['id_utilisateur'] ?? null;
            if (!$idUtilisateur) {
                return ['total_actions' => 0, 'candidatures_traitees' => 0, 'succes' => 0, 'erreurs' => 0];
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) AS total_actions,
                    SUM(CASE WHEN statut_action = 'Succès' THEN 1 ELSE 0 END) AS succes,
                    SUM(CASE WHEN statut_action = 'Erreur' THEN 1 ELSE 0 END) AS erreurs
                FROM pister
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([$idUtilisateur]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Compter les candidatures traitees
            $stmt2 = $this->pdo->prepare("SELECT COUNT(*) AS total FROM candidature_soutenance WHERE id_pers_admin = ?");
            $stmt2->execute([$idPersAdmin]);
            $candidatures = $stmt2->fetch(PDO::FETCH_ASSOC);

            return [
                'total_actions' => (int) ($stats['total_actions'] ?? 0),
                'candidatures_traitees' => (int) ($candidatures['total'] ?? 0),
                'succes' => (int) ($stats['succes'] ?? 0),
                'erreurs' => (int) ($stats['erreurs'] ?? 0),
            ];
        } catch (Exception $e) {
            error_log('FichePersAdminService::getStatsActions - ' . $e->getMessage());
            return ['total_actions' => 0, 'candidatures_traitees' => 0, 'succes' => 0, 'erreurs' => 0];
        }
    }

    /**
     * Donnees completes pour la vue
     */
    public function getFicheComplete(int $id): array
    {
        $identite = $this->getIdentite($id);
        if (!$identite) {
            return [];
        }

        return [
            'identite' => $identite,
            'compte' => $this->getCompteUtilisateur($id),
            'candidatures' => $this->getCandidaturesTraitees($id),
            'historique' => $this->getHistoriqueActions($id),
            'stats' => $this->getStatsActions($id),
        ];
    }
}
