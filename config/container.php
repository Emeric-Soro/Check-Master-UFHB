<?php
declare(strict_types=1);

/**
 * Configuration du conteneur PHP-DI
 * 
 * Ce fichier configure l'injection de dépendances pour les services
 * du système de permissions CheckMaster.
 */

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PermissionService;
use App\Services\TemporaryRoleService;
use App\Middleware\PermissionMiddleware;
use Psr\Log\LoggerInterface;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    // Configuration PDO - Connexion à la base de données
    PDO::class => function () {
        $host = 'localhost';
        $dbname = 'ufrmi1802974_2q2mpf';
        $username = 'root';
        $password = '';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },
    
    // Configuration Monolog - Logger pour l'audit
    LoggerInterface::class => function () {
        $logger = new Logger('checkmaster');
        
        // Handler pour les logs de permissions avec rotation quotidienne (30 jours)
        $handler = new RotatingFileHandler(
            __DIR__ . '/../logs/permissions.log',
            30,
            Logger::INFO
        );
        
        // Format personnalisé pour les logs
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);
        
        $logger->pushHandler($handler);
        
        return $logger;
    },
    
    // Alias pour Logger::class vers LoggerInterface
    Logger::class => DI\get(LoggerInterface::class),
    
    // Configuration PermissionService
    PermissionService::class => function ($c) {
        // Récupérer les informations utilisateur depuis la session
        $userId = $_SESSION['id_utilisateur'] ?? null;
        $groupId = $_SESSION['id_GU'] ?? null;
        
        return new PermissionService(
            db: $c->get(PDO::class),
            logger: $c->get(LoggerInterface::class),
            userId: $userId,
            groupId: $groupId
        );
    },
    
    // Configuration PermissionMiddleware
    PermissionMiddleware::class => function ($c) {
        return new PermissionMiddleware(
            permissionService: $c->get(PermissionService::class),
            logger: $c->get(LoggerInterface::class)
        );
    },
    
    // Configuration TemporaryRoleService
    TemporaryRoleService::class => function ($c) {
        return new TemporaryRoleService(
            db: $c->get(PDO::class),
            logger: $c->get(LoggerInterface::class),
            permissionService: $c->get(PermissionService::class)
        );
    },
]);

return $builder->build();
