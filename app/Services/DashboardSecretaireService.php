<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Enseignant.php';

use Etudiant;
use Enseignant;
use PDO;
use Exception;

/**
 * Service métier du tableau de bord du secrétaire — CORRIGÉ
 *
 * Corrections appliquées :
 * - Tables inexistantes remplacées par les vraies tables
 * - Colonnes corrigées pour correspondre au schéma réel
 * - Tous les try/catch conservés pour robustesse
 */
class DashboardSecretaireService
{
    /** @var PDO */
    private $db;

    /** @var Etudiant */
    private $etudiantModel;

    /** @var Enseignant */
    private $enseignantModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiantModel = new Etudiant($db);
        $this->enseignantModel = new Enseignant($db);
    }

    /**
     * Récupère l'ensemble des données du tableau de bord du secrétaire
     */
    public function getDashboardData()
    {
        return [
            'stats' => $this->getStats(),
            'activites' => $this->getActivitesRecentes(),
            'evolutionEffectifs' => $this->getEvolutionEffectifs()
        ];
    }

    /**
     * Récupère les statistiques globales — CORRIGÉ
     */
    public function getStats()
    {
        // Statistiques des étudiants (table: etudiants, pas de statut)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM etudiants");
            $etudiants = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $etudiants = 0;
        }

        // Statistiques des enseignants (table: enseignants, pas de statut)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM enseignants");
            $enseignants = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $enseignants = 0;
        }

        // Rapports (table: rapport_etudiants)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM rapport_etudiants");
            $rapports = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $rapports = 0;
        }

        // Réclamations non résolues (table: reclamations, colonne: statut)
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as total
                FROM reclamations r
                LEFT JOIN statut_reclamation sr ON sr.id_statut_reclamation = r.statut_reclamation
                WHERE LOWER(COALESCE(sr.libelle_statut_reclamation, '')) NOT IN ('résolue', 'resolue', 'traitée', 'traitee')
            ");
            $reclamations = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $reclamations = 0;
        }

        // Candidatures en attente (table: candidature_soutenance, colonne: statut_candidature)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM candidature_soutenance WHERE statut_candidature = 'En attente'");
            $candidatures = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $candidatures = 0;
        }

        // Dossiers académiques
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM dossier_academique");
            $dossiers = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $dossiers = 0;
        }

        return [
            'etudiants' => $etudiants,
            'enseignants' => $enseignants,
            'rapports' => $rapports,
            'reclamations' => $reclamations,
            'candidatures' => $candidatures,
            'dossiers' => $dossiers
        ];
    }

    /**
     * Récupère les activités récentes (30 derniers jours)
     */
    public function getActivitesRecentes()
    {
        $activites = [];
        $activites = array_merge($activites, $this->getRecentInscriptions());
        $activites = array_merge($activites, $this->getRecentReclamations());
        $activites = array_merge($activites, $this->getRecentCandidatures());
        $activites = array_merge($activites, $this->getRecentRapports());

        usort($activites, function ($a, $b) {
            return strtotime((string) ($b['date_sort'] ?? $b['date_activite'] ?? '')) - strtotime((string) ($a['date_sort'] ?? $a['date_activite'] ?? ''));
        });

        return array_slice($activites, 0, 10);
    }

    /**
     * Évolution effectifs par année — CORRIGÉ
     */
    public function getEvolutionEffectifs()
    {
        try {
            // Utiliser promotions_etu comme proxy de l'année d'inscription
            $stmt = $this->db->query("
                SELECT 
                    promotion_etu as annee,
                    COUNT(*) as effectif
                FROM etudiants
                WHERE promotion_etu IS NOT NULL AND promotion_etu != ''
                GROUP BY promotion_etu
                ORDER BY promotion_etu DESC
                LIMIT 10
            ");
            $evolution = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $evolution[] = [
                    'annee' => $row['annee'],
                    'effectif' => (int) $row['effectif']
                ];
            }
            return $evolution;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Inscriptions récentes — CORRIGÉ (via table inscriptions)
     */
    private function getRecentInscriptions()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT i.date_inscription as date_activite, e.nom_etu, e.prenom_etu
                FROM inscriptions i
                JOIN etudiants e ON (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud)
                WHERE i.date_inscription >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY i.date_inscription DESC
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'inscription',
                    'titre' => 'Nouvelle inscription',
                    'description' => ($row['nom_etu'] ?? '') . ' ' . ($row['prenom_etu'] ?? '') . ' s\'est inscrit(e)',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'date_sort' => $row['date_activite'],
                    'icone' => 'fa-user-plus',
                    'couleur' => 'green'
                ];
            }
        } catch (Exception $e) {
            error_log('DashboardSecretaireService::getRecentInscriptions: ' . $e->getMessage());
        }
        return $activites;
    }

    /**
     * Réclamations récentes — CORRIGÉ
     */
    private function getRecentReclamations()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT objet_reclamation AS objet, date_creation as date_activite
                FROM reclamations
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_creation DESC
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'reclamation',
                    'titre' => 'Nouvelle réclamation',
                    'description' => substr($row['objet'] ?? '', 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'date_sort' => $row['date_activite'],
                    'icone' => 'fa-exclamation-triangle',
                    'couleur' => 'red'
                ];
            }
        } catch (Exception $e) {
            error_log('DashboardSecretaireService::getRecentReclamations: ' . $e->getMessage());
        }
        return $activites;
    }

    /**
     * Candidatures récentes — CORRIGÉ
     */
    private function getRecentCandidatures()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT cs.date_candidature as date_activite, e.nom_etu, e.prenom_etu
                FROM candidature_soutenance cs
                JOIN etudiants e ON (cs.num_etu = e.num_carte_etud OR cs.num_etu = e.num_ident_etud)
                WHERE cs.date_candidature >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY cs.date_candidature DESC
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'candidature',
                    'titre' => 'Nouvelle candidature',
                    'description' => ($row['nom_etu'] ?? '') . ' ' . ($row['prenom_etu'] ?? '') . ' a soumis une candidature',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'date_sort' => $row['date_activite'],
                    'icone' => 'fa-file-signature',
                    'couleur' => 'purple'
                ];
            }
        } catch (Exception $e) {
            error_log('DashboardSecretaireService::getRecentCandidatures: ' . $e->getMessage());
        }
        return $activites;
    }

    /**
     * Rapports récents — CORRIGÉ (table: rapport_etudiants)
     */
    private function getRecentRapports()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT COALESCE(theme_rapport, nom_rapport, CONCAT('Rapport #', id_rapport)) AS titre,
                       date_redaction_rapport as date_activite
                FROM rapport_etudiants
                WHERE date_redaction_rapport >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_redaction_rapport DESC
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'rapport',
                    'titre' => 'Nouveau rapport',
                    'description' => substr($row['titre'] ?? '', 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'date_sort' => $row['date_activite'],
                    'icone' => 'fa-file-alt',
                    'couleur' => 'blue'
                ];
            }
        } catch (Exception $e) {
            error_log('DashboardSecretaireService::getRecentRapports: ' . $e->getMessage());
        }
        return $activites;
    }
}
