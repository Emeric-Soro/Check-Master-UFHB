<?php

declare(strict_types=1);

namespace CheckMaster\Core;

use PDO;

/**
 * Conteneur de dépendances léger.
 * Remplace les 50+ "new Service()" dans les controllers.
 * Pattern : singleton lazy (les services ne sont créés qu'à la première utilisation).
 */
final class ServiceContainer
{
    private static ?self $instance = null;

    /** @var array<string, callable> Factories enregistrées */
    private array $factories = [];

    /** @var array<string, object> Instances créées (singletons) */
    private array $instances = [];

    /** Instance PDO partagée */
    private ?PDO $pdo = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Réinitialiser le container (utile en tests).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // ─── Configuration ────────────────────────────────────

    /**
     * Définir l'instance PDO partagée.
     */
    public function setPDO(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * Obtenir l'instance PDO.
     */
    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = \Database::getConnection();
        }
        return $this->pdo;
    }

    /**
     * Enregistrer un service par sa factory.
     *
     * @param string $key Nom du service (ex: 'AuthService')
     * @param callable $factory Fonction qui crée le service (reçoit le container)
     */
    public function register(string $key, callable $factory): self
    {
        $this->factories[$key] = $factory;
        return $this;
    }

    /**
     * Résoudre un service.
     * Crée l'instance si elle n'existe pas encore (lazy singleton).
     */
    public function get(string $key): object
    {
        // Retourner l'instance existante
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // Créer via la factory enregistrée
        if (isset($this->factories[$key])) {
            $instance = ($this->factories[$key])($this);
            $this->instances[$key] = $instance;
            return $instance;
        }

        // Fallback : essayer d'instancier directement (classe existante)
        if (class_exists($key)) {
            $instance = $this->autoWire($key);
            $this->instances[$key] = $instance;
            return $instance;
        }

        throw new \RuntimeException("Service non trouvé : $key");
    }

    /**
     * Vérifier si un service est enregistré.
     */
    public function has(string $key): bool
    {
        return isset($this->factories[$key]) || isset($this->instances[$key]) || class_exists($key);
    }

    // ─── Auto-wiring simple ───────────────────────────────

    /**
     * Instancier une classe en injectant automatiquement ses dépendances.
     * Ne fonctionne que pour les constructeurs avec des types de classe.
     */
    private function autoWire(string $class): object
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                // Injecter PDO automatiquement
                if ($typeName === PDO::class) {
                    $args[] = $this->getPDO();
                    continue;
                }

                // Injecter le container lui-même
                if ($typeName === self::class) {
                    $args[] = $this;
                    continue;
                }

                // Résoudre récursivement
                if ($this->has($typeName)) {
                    $args[] = $this->get($typeName);
                    continue;
                }
            }

            // Paramètre avec valeur par défaut
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Paramètre nullable
            if ($type !== null && $type->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Impossible de résoudre le paramètre \${$param->getName()} de $class"
            );
        }

        return $reflection->newInstanceArgs($args);
    }

    // ─── Enregistrement des services de l'application ─────

    /**
     * Enregistrer tous les services de l'application.
     * Appelé une fois au boot.
     */
    public function registerAppServices(): self
    {
        $pdo = $this->getPDO();

        // ── Modèles ───────────────────────────────────
        $this->register('Utilisateur', fn() => new \Utilisateur($pdo));
        $this->register('Etudiant', fn() => new \Etudiant($pdo));
        $this->register('Enseignant', fn() => new \Enseignant($pdo));
        $this->register('Inscription', fn() => new \Inscription($pdo));
        $this->register('Soutenance', fn() => new \Soutenance($pdo));
        $this->register('AnneeAcademique', fn() => new \AnneeAcademique($pdo));
        $this->register('Rapport', fn() => new \CheckMaster\Models\Rapport($pdo));
        $this->register('Salle', fn() => new \Salle($pdo));
        $this->register('Permission', fn() => new \Permission($pdo));
        $this->register('AuditLog', fn() => new \AuditLog($pdo));

        // ── Services ──────────────────────────────────
        $this->register('AuthService', fn() => new \CheckMaster\Services\AuthService($pdo));
        $this->register('MenuService', fn() => new \CheckMaster\Services\MenuService($pdo));
        $this->register('AuditService', fn() => new \CheckMaster\Services\AuditService($pdo));
        $this->register('ParametreService', fn() => new \CheckMaster\Services\ParametreService($pdo));
        $this->register('EtudiantService', fn() => new \CheckMaster\Services\EtudiantService($pdo));
        $this->register('InscriptionService', fn() => new \CheckMaster\Services\InscriptionService($pdo));
        $this->register('NotesService', fn() => new \CheckMaster\Services\NotesService($pdo));
        $this->register('GestionRapportService', fn() => new \CheckMaster\Services\GestionRapportService($pdo));
        $this->register('GestionReclamationsService', fn() => new \CheckMaster\Services\GestionReclamationsService($pdo));
        $this->register('ProgrammationSoutenanceService', fn() => new \CheckMaster\Services\ProgrammationSoutenanceService($pdo));
        $this->register('PlanificationSoutenanceService', fn() => new \CheckMaster\Services\PlanificationSoutenanceService($pdo));
        $this->register('EvaluationSoutenanceService', fn() => new \CheckMaster\Services\EvaluationSoutenanceService($pdo));
        $this->register('ProcessusValidationService', fn() => new \CheckMaster\Services\ProcessusValidationService($pdo));
        $this->register('ValidationMemoireService', fn() => new \CheckMaster\Services\ValidationMemoireService($pdo));
        $this->register('ArchiveService', fn() => new \CheckMaster\Services\ArchiveService($pdo));
        $this->register('GestionUtilisateurService', fn() => new \CheckMaster\Services\GestionUtilisateurService($pdo));
        $this->register('FicheEnseignantService', fn() => new \CheckMaster\Services\FicheEnseignantService($pdo));
        $this->register('FicheFinanciereService', fn() => new \CheckMaster\Services\FicheFinanciereService($pdo));
        $this->register('FichePersAdminService', fn() => new \CheckMaster\Services\FichePersAdminService($pdo));
        $this->register('SauvegardeRestaurationService', fn() => new \CheckMaster\Services\SauvegardeRestaurationService($pdo));
        $this->register('CycleEtudiantService', fn() => new \CheckMaster\Services\CycleEtudiantService($pdo));
        $this->register('NotificationService', fn() => new \NotificationService($pdo));
        $this->register('ImportService', fn() => new \CheckMaster\Services\ImportService($pdo));
        $this->register('ExportService', fn() => new \CheckMaster\Services\ExportService($pdo));

        // ── Helpers / Utils ───────────────────────────
        $this->register('EmailService', fn() => new \EmailService());
        $this->register('Logger', fn() => new \Logger());

        return $this;
    }
}
