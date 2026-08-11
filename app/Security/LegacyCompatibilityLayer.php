<?php

declare(strict_types=1);

namespace CheckMaster\Security;

/**
 * Couche de compatibilité legacy : applique les alias de pages
 * (ex: maj_etudiant → gestion_etudiants/ajouter_des_etudiants)
 * en réécrivant $_GET/$_REQUEST, comme le faisait layout.php.
 */
final class LegacyCompatibilityLayer
{
    /** @var array<string, array<string,string>> */
    private array $aliases;

    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases !== [] ? $aliases : [
            'maj_etudiant' => [
                'page' => 'gestion_etudiants',
                'action' => 'ajouter_des_etudiants',
            ],
            'repertoire_documents' => [
                'page' => 'repertoire_enseignant',
            ],
        ];
    }

    /**
     * Applique les alias à $_GET/$_REQUEST (par référence).
     */
    public function apply(?array $get = null): void
    {
        $get = $get ?? $_GET;
        $page = (string) ($get['page'] ?? '');

        if ($page === '' || !isset($this->aliases[$page])) {
            return;
        }

        foreach ($this->aliases[$page] as $key => $value) {
            $_GET[$key] = $value;
            $_REQUEST[$key] = $value;
        }
    }

    public function resolve(string $page): array
    {
        return $this->aliases[$page] ?? [];
    }
}
