<?php

declare(strict_types=1);

namespace App\Services\Document;

/**
 * Contrat commun des générateurs de documents (Phase 4 du plan de réduction).
 *
 * Chaque générateur expose :
 *  - le type de document produit ;
 *  - la génération à partir d'un identifiant d'entité ;
 *  - la diffusion HTTP sécurisée ;
 *  - les métadonnées de résultat.
 */
interface DocumentGeneratorContract
{
    /**
     * Type de document (ex: 'recu', 'pv_final', 'planning', 'bulletin').
     */
    public function documentType(): string;

    /**
     * Génère le document pour une entité donnée.
     *
     * @param string|int $entityId Identifiant de l'entité source.
     * @param int $userId Utilisateur demandeur.
     * @return array{success:bool, reference?:string, path?:string, filename?:string, size?:int|null, error?:string}
     */
    public function generate(string|int $entityId, int $userId): array;

    /**
     * Diffuse le document généré (téléchargement ou aperçu).
     */
    public function serve(array $result): void;
}
