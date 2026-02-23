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
 * Service métier du tableau de bord du secrétaire
 *
 * Contient toute la logique métier et les requêtes de données pour le tableau de bord du secrétaire :
 * - Statistiques globales (étudiants, enseignants, rapports, réclamations, candidatures, dossiers)
 * - Activités récentes (inscriptions, réclamations, candidatures, rapports)
 * - Évolution des effectifs étudiants par année
 */
class DashboardSecretaireService
{
    /** @var PDO */
    private $db;

    /** @var Etudiant */
    private $etudiantModel;

    /** @var Enseignant */
    private $enseignantModel;

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiantModel = new Etudiant($db);
        $this->enseignantModel = new Enseignant($db);
    }

    /**
     * Récupère l'ensemble des données du tableau de bord du secrétaire
     *
     * @return array Les données contenant 'stats', 'activites' et 'evolutionEffectifs'
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
     * Récupère les statistiques globales
     *
     * @return array Statistiques (étudiants, enseignants, rapports, réclamations, candidatures, dossiers)
     */
    public function getStats()
    {
        // Statistiques des étudiants
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM etudiant WHERE statut = 'actif'");
        $etudiants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Statistiques des enseignants
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM enseignant WHERE statut = 'actif'");
        $enseignants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Statistiques des rapports (si la table existe)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM rapport");
            $rapports = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $rapports = 0;
        }

        // Statistiques des réclamations (si la table existe)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM reclamation WHERE statut != 'resolue'");
            $reclamations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $reclamations = 0;
        }

        // Statistiques des candidatures (si la table existe)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM candidature_soutenance WHERE statut = 'en_attente'");
            $candidatures = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $candidatures = 0;
        }

        // Statistiques des dossiers académiques (si la table existe)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM dossier_academique");
            $dossiers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
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
     *
     * @return array Liste des 10 activités les plus récentes
     */
    public function getActivitesRecentes()
    {
        $activites = [];

        // Dernières inscriptions d'étudiants
        $activites = array_merge($activites, $this->getRecentInscriptions());

        // Dernières réclamations
        $activites = array_merge($activites, $this->getRecentReclamations());

        // Dernières candidatures
        $activites = array_merge($activites, $this->getRecentCandidatures());

        // Derniers rapports
        $activites = array_merge($activites, $this->getRecentRapports());

        // Trier par date et prendre les 10 plus récentes
        usort($activites, function ($a, $b) {
            return strtotime($b['date_activite']) - strtotime($a['date_activite']);
        });

        return array_slice($activites, 0, 10);
    }

    /**
     * Récupère l'évolution des effectifs étudiants par année
     *
     * @return array Liste d'objets ['annee', 'effectif'] depuis 2019
     */
    public function getEvolutionEffectifs()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    YEAR(date_inscription) as annee,
                    COUNT(*) as effectif
                FROM etudiant 
                WHERE date_inscription >= '2019-01-01'
                GROUP BY YEAR(date_inscription)
                ORDER BY annee
            ");

            $evolution = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $evolution[] = [
                    'annee' => $row['annee'],
                    'effectif' => $row['effectif']
                ];
            }

            return $evolution;
        } catch (Exception $e) {
            // Données factices si la table n'existe pas
            return [
                ['annee' => '2019', 'effectif' => 180],
                ['annee' => '2020', 'effectif' => 200],
                ['annee' => '2021', 'effectif' => 220],
                ['annee' => '2022', 'effectif' => 250],
                ['annee' => '2023', 'effectif' => 300],
                ['annee' => '2024', 'effectif' => 320]
            ];
        }
    }

    /**
     * Récupère les inscriptions récentes
     *
     * @return array
     */
    private function getRecentInscriptions()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT 'inscription' as type, nom_etu, prenom_etu, date_inscription as date_activite
                FROM etudiant 
                WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_inscription DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'inscription',
                    'titre' => 'Nouvelle inscription',
                    'description' => $row['nom_etu'] . ' ' . $row['prenom_etu'] . ' s\'est inscrit(e)',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'icone' => 'fa-user-plus',
                    'couleur' => 'green'
                ];
            }
        } catch (Exception $e) {
            // Table ou colonne inexistante
        }
        return $activites;
    }

    /**
     * Récupère les réclamations récentes
     *
     * @return array
     */
    private function getRecentReclamations()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT 'reclamation' as type, sujet, date_creation as date_activite
                FROM reclamation 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_creation DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'reclamation',
                    'titre' => 'Nouvelle réclamation',
                    'description' => substr($row['sujet'], 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_creation'])),
                    'icone' => 'fa-exclamation-triangle',
                    'couleur' => 'red'
                ];
            }
        } catch (Exception $e) {
            // Table inexistante
        }
        return $activites;
    }

    /**
     * Récupère les candidatures récentes
     *
     * @return array
     */
    private function getRecentCandidatures()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT 'candidature' as type, e.nom_etu, e.prenom_etu, cs.date_candidature as date_activite
                FROM candidature_soutenance cs
                JOIN etudiant e ON cs.num_etu = e.num_etu
                WHERE cs.date_candidature >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY cs.date_candidature DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'candidature',
                    'titre' => 'Nouvelle candidature',
                    'description' => $row['nom_etu'] . ' ' . $row['prenom_etu'] . ' a soumis une candidature',
                    'date_activite' => date('d/m/Y', strtotime($row['date_activite'])),
                    'icone' => 'fa-file-signature',
                    'couleur' => 'purple'
                ];
            }
        } catch (Exception $e) {
            // Table inexistante
        }
        return $activites;
    }

    /**
     * Récupère les rapports récents
     *
     * @return array
     */
    private function getRecentRapports()
    {
        $activites = [];
        try {
            $stmt = $this->db->query("
                SELECT 'rapport' as type, titre, date_creation as date_activite
                FROM rapport 
                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date_creation DESC 
                LIMIT 3
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activites[] = [
                    'type' => 'rapport',
                    'titre' => 'Nouveau rapport',
                    'description' => substr($row['titre'], 0, 50) . '...',
                    'date_activite' => date('d/m/Y', strtotime($row['date_creation'])),
                    'icone' => 'fa-file-alt',
                    'couleur' => 'blue'
                ];
            }
        } catch (Exception $e) {
            // Table inexistante
        }
        return $activites;
    }
}