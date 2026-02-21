<?php
/**
 * Configuration du conteneur de dépendances PHP-DI
 * 
 * Ce fichier centralise toutes les définitions de dépendances pour l'injection.
 * Objectif: supprimer l'instanciation manuelle des modèles/services dans les contrôleurs.
 * 
 * @package CheckMaster
 * @since 1.0.0
 */

declare(strict_types=1);

// Charger l'autoloader Composer pour PHP-DI
require_once __DIR__ . '/../../vendor/autoload.php';

use DI\Container;
use DI\ContainerBuilder;
use function DI\factory;
use function DI\autowire;
use function DI\get;

// Charger la configuration de la base de données
require_once __DIR__ . '/database.php';

/**
 * Construit et retourne l'instance du conteneur PHP-DI
 * 
 * @return Container
 */
function buildContainer(): Container
{
    $containerBuilder = new ContainerBuilder();
    
    // Activer l'autowiring (injection automatique basée sur les types)
    $containerBuilder->useAutowiring(true);
    
    // Activer les attributs PHP 8+
    $containerBuilder->useAttributes(true);
    
    // Définitions des dépendances
    $containerBuilder->addDefinitions([
        
        // =================================================================
        // INSTANCE PDO - Singleton de connexion à la base de données
        // =================================================================
        PDO::class => factory(function (): PDO {
            // Utiliser la classe Database existante pour obtenir la connexion
            return Database::getConnection();
        }),
        
        // =================================================================
        // MODÈLES / REPOSITORIES
        // Chaque modèle reçoit automatiquement l'instance PDO
        // =================================================================
        
        // RapportEtudiant Model
        RapportEtudiant::class => factory(function (Container $c): RapportEtudiant {
            require_once __DIR__ . '/../Models/RapportEtudiant.php';
            return new RapportEtudiant($c->get(PDO::class));
        }),
        
        // Etudiant Model
        Etudiant::class => factory(function (Container $c): Etudiant {
            require_once __DIR__ . '/../Models/Etudiant.php';
            return new Etudiant($c->get(PDO::class));
        }),
        
        // AuditLog Model
        AuditLog::class => factory(function (Container $c): AuditLog {
            require_once __DIR__ . '/../Models/AuditLog.php';
            return new AuditLog($c->get(PDO::class));
        }),
        
        // PersAdmin Model
        PersAdmin::class => factory(function (Container $c): PersAdmin {
            require_once __DIR__ . '/../Models/PersAdmin.php';
            return new PersAdmin($c->get(PDO::class));
        }),
        
        // Enseignant Model
        Enseignant::class => factory(function (Container $c): Enseignant {
            require_once __DIR__ . '/../Models/Enseignant.php';
            return new Enseignant($c->get(PDO::class));
        }),
        
        // Ue Model
        Ue::class => factory(function (Container $c): Ue {
            require_once __DIR__ . '/../Models/Ue.php';
            return new Ue($c->get(PDO::class));
        }),
        
        // Ecue Model
        Ecue::class => factory(function (Container $c): Ecue {
            require_once __DIR__ . '/../Models/Ecue.php';
            return new Ecue($c->get(PDO::class));
        }),
        
        // NiveauEtude Model
        NiveauEtude::class => factory(function (Container $c): NiveauEtude {
            require_once __DIR__ . '/../Models/NiveauEtude.php';
            return new NiveauEtude($c->get(PDO::class));
        }),
        
        // EvaluationRapport Model
        EvaluationRapport::class => factory(function (Container $c): EvaluationRapport {
            require_once __DIR__ . '/../Models/EvaluationRapport.php';
            return new EvaluationRapport($c->get(PDO::class));
        }),
        
        // Note Model
        Note::class => factory(function (Container $c): Note {
            require_once __DIR__ . '/../Models/Note.php';
            return new Note($c->get(PDO::class));
        }),
        
        // Semestre Model
        Semestre::class => factory(function (Container $c): Semestre {
            require_once __DIR__ . '/../Models/Semestre.php';
            return new Semestre($c->get(PDO::class));
        }),
        
        // =================================================================
        // SERVICES - Couche métier
        // Les services encapsulent la logique métier et reçoivent les modèles
        // =================================================================
        
        \CheckMaster\Services\RapportService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class)),
        
        \CheckMaster\Services\AuthService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class)),
        
        \CheckMaster\Services\DashboardService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class)),
        
        \CheckMaster\Services\CandidatureSoutenanceService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class)),
        
        \CheckMaster\Services\GestionUtilisateurService::class => autowire()
            ->constructorParameter('utilisateur', get(Utilisateur::class))
            ->constructorParameter('typeUtilisateur', get(TypeUtilisateur::class))
            ->constructorParameter('groupeUtilisateur', get(GroupeUtilisateur::class))
            ->constructorParameter('niveauAcces', get(NiveauAccesDonnees::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        \CheckMaster\Services\GestionEtudiantService::class => autowire()
            ->constructorParameter('etudiant', get(Etudiant::class))
            ->constructorParameter('auditLog', get(AuditLog::class))
            ->constructorParameter('db', get(PDO::class)),
        
        \CheckMaster\Services\InscriptionService::class => autowire()
            ->constructorParameter('scolarite', get(Scolarite::class))
            ->constructorParameter('anneeAcademique', get(AnneeAcademique::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        // Batch 3 Services
        \CheckMaster\Services\AuditService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        \CheckMaster\Services\ArchiveService::class => autowire()
            ->constructorParameter('pdo', get(PDO::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        \CheckMaster\Services\SauvegardeRestaurationService::class => autowire()
            ->constructorParameter('auditLog', get(AuditLog::class))
            ->constructorParameter('db', get(PDO::class)),
        
        // Batch 4 Services
        \CheckMaster\Services\EvaluationDossiersService::class => autowire()
            ->constructorParameter('db', get(PDO::class)),
        
        \CheckMaster\Services\EvaluationSoutenanceService::class => autowire()
            ->constructorParameter('db', get(PDO::class)),
        
        \CheckMaster\Services\ProgrammationSoutenanceService::class => autowire()
            ->constructorParameter('db', get(PDO::class)),
        
        // Batch 5 Services
        \CheckMaster\Services\DashboardSecretaireService::class => autowire()
            ->constructorParameter('db', get(PDO::class))
            ->constructorParameter('etudiantModel', get(Etudiant::class))
            ->constructorParameter('enseignantModel', get(Enseignant::class)),
        
        \CheckMaster\Services\DashboardEnseignantService::class => autowire()
            ->constructorParameter('enseignant', get(Enseignant::class))
            ->constructorParameter('etudiant', get(Etudiant::class))
            ->constructorParameter('ue', get(Ue::class))
            ->constructorParameter('ecue', get(Ecue::class))
            ->constructorParameter('niveauEtude', get(NiveauEtude::class)),
        
        \CheckMaster\Services\DashboardCommissionService::class => autowire()
            ->constructorParameter('db', get(PDO::class))
            ->constructorParameter('rapportEtudiant', get(RapportEtudiant::class))
            ->constructorParameter('evaluationRapport', get(EvaluationRapport::class)),
        
        // Batch 6 Services
        \CheckMaster\Services\NotesService::class => autowire()
            ->constructorParameter('db', get(PDO::class))
            ->constructorParameter('noteModel', get(Note::class))
            ->constructorParameter('etudiantModel', get(Etudiant::class))
            ->constructorParameter('niveauModel', get(NiveauEtude::class))
            ->constructorParameter('semestreModel', get(Semestre::class))
            ->constructorParameter('ueModel', get(Ue::class))
            ->constructorParameter('ecueModel', get(Ecue::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        \CheckMaster\Services\NotesResultatsService::class => autowire()
            ->constructorParameter('db', get(PDO::class))
            ->constructorParameter('noteModel', get(Note::class))
            ->constructorParameter('etudiantModel', get(Etudiant::class))
            ->constructorParameter('niveauModel', get(NiveauEtude::class))
            ->constructorParameter('semestreModel', get(Semestre::class))
            ->constructorParameter('ueModel', get(Ue::class))
            ->constructorParameter('ecueModel', get(Ecue::class)),
        
        \CheckMaster\Controllers\GestionRapportController::class => autowire()
            ->constructorParameter('rapportService', get(\CheckMaster\Services\RapportService::class))
            ->constructorParameter('auditLog', get(AuditLog::class)),
        
        \CheckMaster\Controllers\AuthController::class => autowire()
            ->constructorParameter('authService', get(\CheckMaster\Services\AuthService::class)),
        
        \CheckMaster\Controllers\DashboardController::class => autowire()
            ->constructorParameter('dashboardService', get(\CheckMaster\Services\DashboardService::class)),
        
        \CheckMaster\Controllers\CandidatureSoutenanceController::class => autowire()
            ->constructorParameter('candidatureService', get(\CheckMaster\Services\CandidatureSoutenanceService::class)),
        
        \CheckMaster\Controllers\GestionUtilisateurController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\GestionUtilisateurService::class)),
        
        \CheckMaster\Controllers\GestionEtudiantController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\GestionEtudiantService::class)),
        
        \CheckMaster\Controllers\InscriptionController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\InscriptionService::class)),
        
        // Batch 3 Controllers
        \CheckMaster\Controllers\AuditController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\AuditService::class)),
        
        \CheckMaster\Controllers\ArchiveController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\ArchiveService::class)),
        
        \CheckMaster\Controllers\SauvegardeRestaurationController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\SauvegardeRestaurationService::class)),
        
        // Batch 4 Controllers
        \CheckMaster\Controllers\EvaluationDossiersController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\EvaluationDossiersService::class)),
        
        \CheckMaster\Controllers\EvaluationSoutenanceController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\EvaluationSoutenanceService::class)),
        
        \CheckMaster\Controllers\ProgrammationSoutenanceController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\ProgrammationSoutenanceService::class)),
        
        // Batch 5 Controllers
        \CheckMaster\Controllers\DashboardSecretaireController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\DashboardSecretaireService::class)),
        
        \CheckMaster\Controllers\DashboardEnseignantController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\DashboardEnseignantService::class)),
        
        \CheckMaster\Controllers\DashboardCommissionController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\DashboardCommissionService::class)),
        
        // Batch 6 Controllers
        \CheckMaster\Controllers\NotesController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\NotesService::class)),
        
        \CheckMaster\Controllers\NotesResultatsController::class => autowire()
            ->constructorParameter('service', get(\CheckMaster\Services\NotesResultatsService::class)),
        
    ]);
    
    return $containerBuilder->build();
}

// Instance globale du conteneur (singleton)
$container = null;

/**
 * Récupère l'instance du conteneur (lazy initialization)
 * 
 * @return Container
 */
function getContainer(): Container
{
    global $container;
    
    if ($container === null) {
        $container = buildContainer();
    }
    
    return $container;
}
