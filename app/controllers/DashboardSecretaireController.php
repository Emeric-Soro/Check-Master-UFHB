<?php

require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../utils/permissions.php';

class DashboardSecretaireController {
    private $pdo;
    private $etudiantModel;
    private $enseignantModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->etudiantModel = new Etudiant($pdo);
        $this->enseignantModel = new Enseignant($pdo);
    }

    public function index() {
        $stats = $this->getStats();
        $activites = $this->getActivitesRecentes();
        $evolutionEffectifs = $this->getEvolutionEffectifs();
        
        return [
            'stats' => $stats,
            'activites' => $activites,
            'evolutionEffectifs' => $evolutionEffectifs
        ];
    }

    private function getStats() {
        // Statistiques des étudiants
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM etudiant WHERE statut = 'actif'");
        $etudiants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Statistiques des enseignants
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM enseignant WHERE statut = 'actif'");
        $enseignants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Statistiques des rapports (si la table existe)
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM rapport");
            $rapports = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $rapports = 0;
        }

        // Statistiques des réclamations (si la table existe)
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reclamation WHERE statut != 'resolue'");
            $reclamations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $reclamations = 0;
        }

        // Statistiques des candidatures (si la table existe)
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM candidature_soutenance WHERE statut = 'en_attente'");
            $candidatures = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $candidatures = 0;
        }

        // Statistiques des dossiers académiques (si la table existe)
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM dossier_academique");
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

    private function getActivitesRecentes() {
        $activites = [];

        // Dernières inscriptions d'étudiants
        try {
            $stmt = $this->pdo->query("
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

        // Dernières réclamations
        try {
            $stmt = $this->pdo->query("
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

        // Dernières candidatures
        try {
            $stmt = $this->pdo->query("
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

        // Derniers rapports
        try {
            $stmt = $this->pdo->query("
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

        // Trier par date et prendre les 10 plus récentes
        usort($activites, function($a, $b) {
            return strtotime($b['date_activite']) - strtotime($a['date_activite']);
        });

        return array_slice($activites, 0, 10);
    }

    private function getEvolutionEffectifs() {
        try {
            $stmt = $this->pdo->query("
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
} 