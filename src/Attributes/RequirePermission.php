<?php
declare(strict_types=1);

namespace App\Attributes;

use App\Enums\Action;
use Attribute;

/**
 * Attribut RequirePermission - Déclare les permissions requises pour une méthode
 * 
 * Cet attribut peut être appliqué sur les méthodes de contrôleur pour
 * déclarer les permissions nécessaires à leur exécution.
 * 
 * Exemple d'utilisation:
 * ```php
 * #[RequirePermission('gestion_etudiants', Action::Read)]
 * #[RequirePermission('gestion_etudiants', Action::Update)]
 * public function editStudent(): void { ... }
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class RequirePermission {
    /**
     * @param string $resource Le nom de la ressource (lib_traitement dans la table traitement)
     * @param Action $action L'action requise sur cette ressource
     */
    public function __construct(
        public string $resource,
        public Action $action,
    ) {}
}
