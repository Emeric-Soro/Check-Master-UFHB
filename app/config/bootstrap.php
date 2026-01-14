<?php
/**
 * Bootstrap - Conteneur d'Injection de Dépendances
 * 
 * Ce fichier est le cerveau de l'application. Il configure toutes les 
 * librairies (Logging, Hashids, DB, CSRF) au même endroit et les rend
 * disponibles via injection de dépendances.
 * 
 * @package CheckMaster
 * @author Projet UFHB
 */

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Hashids\Hashids;
use ParagonIE\AntiCSRF\AntiCSRF;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

$builder = new ContainerBuilder();

$builder->addDefinitions([
    // ================================================================
    // 1. CONFIGURATION BASE DE DONNÉES (PDO)
    // ================================================================
    PDO::class => function () {
        $host = 'localhost';
        $db   = 'ufrmi1802974_2q2mpf';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ];
        
        return new PDO($dsn, $user, $pass, $options);
    },

    // ================================================================
    // 2. LOGGING (MONOLOG) - Interface PSR-3
    // ================================================================
    LoggerInterface::class => function () {
        $logger = new Logger('CheckMaster');
        
        // Format personnalisé pour les logs
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        
        // Créer le dossier logs s'il n'existe pas
        $logsDir = __DIR__ . '/../../logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        // Fichier de log rotatif (nouveau fichier chaque jour)
        $rotatingHandler = new RotatingFileHandler(
            $logsDir . '/app.log',
            30, // Garde 30 jours de logs
            Logger::DEBUG
        );
        $rotatingHandler->setFormatter($formatter);
        $logger->pushHandler($rotatingHandler);
        
        // Handler séparé pour les erreurs critiques
        $errorHandler = new StreamHandler(
            $logsDir . '/error.log',
            Logger::ERROR
        );
        $errorHandler->setFormatter($formatter);
        $logger->pushHandler($errorHandler);
        
        return $logger;
    },

    // Alias pour compatibilité
    Logger::class => function (ContainerInterface $c) {
        return $c->get(LoggerInterface::class);
    },

    // ================================================================
    // 3. SÉCURITÉ DES ID (HASHIDS)
    // ================================================================
    Hashids::class => function () {
        // SALT SECRET : Changez cette chaîne par une phrase unique et gardez-la secrète !
        $salt = 'SEL_PROTECTION_CHECK_MASTER_2025_UFHB_SECURE_KEY';
        $minLength = 10;
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        
        return new Hashids($salt, $minLength, $alphabet);
    },

    // ================================================================
    // 4. GESTION DES DATES (CARBON)
    // ================================================================
    'CarbonSetup' => function () {
        \Carbon\Carbon::setLocale('fr');
        return true;
    },

    // ================================================================
    // 5. PROTECTION CSRF
    // ================================================================
    AntiCSRF::class => function () {
        return new AntiCSRF();
    },

    // ================================================================
    // 6. VALIDATION (VALITRON) - Factory
    // ================================================================
    'ValidatorFactory' => function () {
        \Valitron\Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang');
        \Valitron\Validator::lang('fr');
        
        return function(array $data, array $rules = []) {
            $v = new \Valitron\Validator($data);
            foreach ($rules as $field => $fieldRules) {
                foreach ((array)$fieldRules as $rule) {
                    if (is_array($rule)) {
                        $v->rule($rule[0], $field, ...array_slice($rule, 1));
                    } else {
                        $v->rule($rule, $field);
                    }
                }
            }
            return $v;
        };
    },

    // ================================================================
    // 7. SERVICE UTILITAIRE DE SECURITE
    // ================================================================
    \App\Utils\SecurityUtils::class => function (ContainerInterface $c) {
        return new \App\Utils\SecurityUtils(
            $c->get(Hashids::class),
            $c->get(PDO::class)
        );
    },

    // ================================================================
    // 8. MODÈLES (MODELS)
    // ================================================================
    \App\Models\AuditLog::class => function (ContainerInterface $c) {
        return new \App\Models\AuditLog(
            $c->get(PDO::class)
        );
    },

    \App\Models\Action::class => function (ContainerInterface $c) {
        return new \App\Models\Action(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Traitement::class => function (ContainerInterface $c) {
        return new \App\Models\Traitement(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Utilisateur::class => function (ContainerInterface $c) {
        return new \App\Models\Utilisateur(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Etudiant::class => function (ContainerInterface $c) {
        return new \App\Models\Etudiant(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Enseignant::class => function (ContainerInterface $c) {
        return new \App\Models\Enseignant(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\PersAdmin::class => function (ContainerInterface $c) {
        return new \App\Models\PersAdmin(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Scolarite::class => function (ContainerInterface $c) {
        return new \App\Models\Scolarite(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\AnneeAcademique::class => function (ContainerInterface $c) {
        return new \App\Models\AnneeAcademique(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Ecue::class => function (ContainerInterface $c) {
        return new \App\Models\Ecue(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Fonction::class => function (ContainerInterface $c) {
        return new \App\Models\Fonction(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Grade::class => function (ContainerInterface $c) {
        return new \App\Models\Grade(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\GroupeUtilisateur::class => function (ContainerInterface $c) {
        return new \App\Models\GroupeUtilisateur(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\NiveauAccesDonnees::class => function (ContainerInterface $c) {
        return new \App\Models\NiveauAccesDonnees(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\NiveauApprobation::class => function (ContainerInterface $c) {
        return new \App\Models\NiveauApprobation(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Specialite::class => function (ContainerInterface $c) {
        return new \App\Models\Specialite(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\StatutJury::class => function (ContainerInterface $c) {
        return new \App\Models\StatutJury(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\TypeUtilisateur::class => function (ContainerInterface $c) {
        return new \App\Models\TypeUtilisateur(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Ue::class => function (ContainerInterface $c) {
        return new \App\Models\Ue(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\NiveauEtude::class => function (ContainerInterface $c) {
        return new \App\Models\NiveauEtude(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Semestre::class => function (ContainerInterface $c) {
        return new \App\Models\Semestre(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Entreprise::class => function (ContainerInterface $c) {
        return new \App\Models\Entreprise(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Message::class => function (ContainerInterface $c) {
        return new \App\Models\Message(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Attribution::class => function (ContainerInterface $c) {
        return new \App\Models\Attribution(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\CritereEvaluation::class => function (ContainerInterface $c) {
        return new \App\Models\CritereEvaluation(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\EvaluationSoutenance::class => function (ContainerInterface $c) {
        return new \App\Models\EvaluationSoutenance(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\DossierAcademique::class => function (ContainerInterface $c) {
        return new \App\Models\DossierAcademique(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\RapportEtudiant::class => function (ContainerInterface $c) {
        return new \App\Models\RapportEtudiant(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Valider::class => function (ContainerInterface $c) {
        return new \App\Models\Valider(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\EvaluationRapport::class => function (ContainerInterface $c) {
        return new \App\Models\EvaluationRapport(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\Archive::class => function (ContainerInterface $c) {
        return new \App\Models\Archive(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Models\InfoStage::class => function (ContainerInterface $c) {
        return new \App\Models\InfoStage(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    // ================================================================
    // 8.5 SERVICES UTILITAIRES (UTILS)
    // ================================================================
    \App\Utils\ExcelImportService::class => function (ContainerInterface $c) {
        return new \App\Utils\ExcelImportService(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Utils\DocumentGeneratorService::class => function (ContainerInterface $c) {
        return new \App\Utils\DocumentGeneratorService();
    },

    // ================================================================
    // 9. CONTROLEURS (CONTROLLERS)
    // ================================================================
    \App\Controllers\MenuController::class => function (ContainerInterface $c) {
        return new \App\Controllers\MenuController(
            $c->get(PDO::class),
            $c->get(\App\Models\Traitement::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\AuditController::class => function (ContainerInterface $c) {
        return new \App\Controllers\AuditController(
            $c->get(PDO::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Models\Action::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\GestionEtudiantController::class => function (ContainerInterface $c) {
        return new \App\Controllers\GestionEtudiantController(
            $c->get(PDO::class),
            $c->get(\App\Models\Etudiant::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\DashboardController::class => function (ContainerInterface $c) {
        return new \App\Controllers\DashboardController(
            $c->get(PDO::class),
            $c->get(\App\Models\Utilisateur::class),
            $c->get(\App\Models\Etudiant::class),
            $c->get(\App\Models\Enseignant::class),
            $c->get(\App\Models\PersAdmin::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\GestionScolariteController::class => function (ContainerInterface $c) {
        return new \App\Controllers\GestionScolariteController(
            $c->get(PDO::class),
            $c->get(\App\Models\Scolarite::class),
            $c->get(\App\Models\AnneeAcademique::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\ParametreController::class => function (ContainerInterface $c) {
        return new \App\Controllers\ParametreController(
            $c->get(PDO::class),
            $c->get(\App\Models\Action::class),
            $c->get(\App\Models\AnneeAcademique::class),
            $c->get(\App\Models\Ecue::class),
            $c->get(\App\Models\Fonction::class),
            $c->get(\App\Models\Grade::class),
            $c->get(\App\Models\GroupeUtilisateur::class),
            $c->get(\App\Models\NiveauAccesDonnees::class),
            $c->get(\App\Models\NiveauApprobation::class),
            $c->get(\App\Models\Specialite::class),
            $c->get(\App\Models\StatutJury::class),
            $c->get(\App\Models\TypeUtilisateur::class),
            $c->get(\App\Models\Ue::class),
            $c->get(\App\Models\NiveauEtude::class),
            $c->get(\App\Models\Semestre::class),
            $c->get(\App\Models\Traitement::class),
            $c->get(\App\Models\Entreprise::class),
            $c->get(\App\Models\Message::class),
            $c->get(\App\Models\Attribution::class),
            $c->get(\App\Models\Enseignant::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\CriteresEvaluationController::class => function (ContainerInterface $c) {
        return new \App\Controllers\CriteresEvaluationController(
            $c->get(PDO::class),
            $c->get(\App\Models\CritereEvaluation::class),
            $c->get(\App\Models\AnneeAcademique::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\ArchiveController::class => function (ContainerInterface $c) {
        return new \App\Controllers\ArchiveController(
            $c->get(\App\Models\Archive::class),
            $c->get(\App\Utils\ExcelImportService::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class),
            $c->get(PDO::class)
        );
    },

    \App\Controllers\ArchivesCompteRenduController::class => function (ContainerInterface $c) {
        return new \App\Controllers\ArchivesCompteRenduController(
            $c->get(PDO::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\ArchivesDossiersSoutenanceController::class => function (ContainerInterface $c) {
        return new \App\Controllers\ArchivesDossiersSoutenanceController(
            $c->get(PDO::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\EvaluationSoutenanceController::class => function (ContainerInterface $c) {
        return new \App\Controllers\EvaluationSoutenanceController(
            $c->get(PDO::class),
            $c->get(\App\Models\EvaluationSoutenance::class),
            $c->get(\App\Models\AnneeAcademique::class),
            $c->get(\App\Models\CritereEvaluation::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(\App\Utils\DocumentGeneratorService::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\CandidatureSoutenanceController::class => function (ContainerInterface $c) {
        return new \App\Controllers\CandidatureSoutenanceController(
            $c->get(PDO::class),
            $c->get(\App\Models\Etudiant::class),
            $c->get(\App\Models\Entreprise::class),
            $c->get(\App\Models\InfoStage::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\DossierAcademiqueController::class => function (ContainerInterface $c) {
        return new \App\Controllers\DossierAcademiqueController(
            $c->get(PDO::class),
            $c->get(\App\Models\DossierAcademique::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },

    \App\Controllers\EvaluationDossiersController::class => function (ContainerInterface $c) {
        return new \App\Controllers\EvaluationDossiersController(
            $c->get(PDO::class),
            $c->get(\App\Models\RapportEtudiant::class),
            $c->get(\App\Models\Valider::class),
            $c->get(\App\Models\EvaluationRapport::class),
            $c->get(\App\Models\AuditLog::class),
            $c->get(\App\Utils\SecurityUtils::class),
            $c->get(LoggerInterface::class)
        );
    },
]);

// Construction et retour du conteneur
return $builder->build();
