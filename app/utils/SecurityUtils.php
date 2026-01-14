<?php

namespace App\Utils;

use Hashids\Hashids;
use PDO;

/**
 * SecurityUtils - Utilitaire de Sécurité et Permissions
 * 
 * Ce service centralise :
 * - L'encodage/décodage des IDs pour éviter l'énumération
 * - La vérification des droits granulaires (C.R.U.D)
 * - Les fonctions de sécurité courantes
 * 
 * @package App\Utils
 */
class SecurityUtils
{
    private Hashids $hashids;
    private PDO $pdo;
    
    /**
     * Cache des permissions pour éviter les requêtes répétées
     * Structure: ['groupe_slug_action' => bool]
     */
    private array $permissionsCache = [];

    /**
     * Constructeur - Injection automatique via PHP-DI
     * 
     * @param Hashids $hashids Service de hachage des IDs
     * @param PDO $pdo Connexion à la base de données
     */
    public function __construct(Hashids $hashids, PDO $pdo)
    {
        $this->hashids = $hashids;
        $this->pdo = $pdo;
    }

    // ================================================================
    // MÉTHODES D'ENCODAGE / DÉCODAGE DES IDS
    // ================================================================

    /**
     * Encode un ID numérique en hash alphanumérique
     * Exemple: 15 -> "Kj7L2nPx8m"
     * 
     * @param int $id L'ID à encoder
     * @return string Le hash correspondant
     */
    public function encodeId(int $id): string
    {
        return $this->hashids->encode($id);
    }

    /**
     * Décode un hash alphanumérique en ID numérique
     * Exemple: "Kj7L2nPx8m" -> 15
     * 
     * @param string $hash Le hash à décoder
     * @return int|null L'ID décodé, ou null si le hash est invalide
     */
    public function decodeId(string $hash): ?int
    {
        if (empty($hash)) {
            return null;
        }
        
        $decoded = $this->hashids->decode($hash);
        return $decoded[0] ?? null;
    }

    /**
     * Encode plusieurs IDs en même temps
     * 
     * @param array $ids Tableau d'IDs à encoder
     * @return array Tableau de hashs correspondants
     */
    public function encodeIds(array $ids): array
    {
        return array_map([$this, 'encodeId'], array_filter($ids, 'is_int'));
    }

    /**
     * Vérifie si un hash correspond bien à un ID valide
     * 
     * @param string $hash Le hash à vérifier
     * @param int $expectedId L'ID attendu
     * @return bool True si le hash correspond à l'ID
     */
    public function verifyHash(string $hash, int $expectedId): bool
    {
        $decoded = $this->decodeId($hash);
        return $decoded === $expectedId;
    }

    // ================================================================
    // MÉTHODES DE VÉRIFICATION DES PERMISSIONS
    // ================================================================

    /**
     * Vérifie si l'utilisateur a le droit d'effectuer une action
     * 
     * @param int $idGroupe ID du groupe utilisateur
     * @param string $slugTraitement Le libellé du traitement (ex: 'gestion_utilisateurs')
     * @param string $action L'action à vérifier: 'read', 'create', 'update', 'delete'
     * @return bool True si l'utilisateur a le droit
     */
    public function can(int $idGroupe, string $slugTraitement, string $action = 'read'): bool
    {
        // Mapping des actions vers les colonnes de la table 'droits'
        $actionMap = [
            'read'   => 'can_read',
            'create' => 'can_create',
            'update' => 'can_update',
            'delete' => 'can_delete',
            'view'   => 'can_read',      // Alias
            'add'    => 'can_create',    // Alias
            'edit'   => 'can_update',    // Alias
            'remove' => 'can_delete',    // Alias
        ];

        // Vérification de l'action demandée
        $action = strtolower($action);
        if (!isset($actionMap[$action])) {
            return false;
        }
        
        $column = $actionMap[$action];
        
        // Vérification du cache
        $cacheKey = "{$idGroupe}_{$slugTraitement}_{$action}";
        if (isset($this->permissionsCache[$cacheKey])) {
            return $this->permissionsCache[$cacheKey];
        }

        // Requête SQL pour vérifier les droits
        $stmt = $this->pdo->prepare("
            SELECT d.{$column} 
            FROM droits d
            JOIN traitement t ON d.id_traitement = t.id_traitement
            WHERE d.id_GU = :groupe AND t.lib_traitement = :slug
            LIMIT 1
        ");
        
        $stmt->execute([
            'groupe' => $idGroupe, 
            'slug' => $slugTraitement
        ]);
        
        $result = (bool) $stmt->fetchColumn();
        
        // Mise en cache
        $this->permissionsCache[$cacheKey] = $result;
        
        return $result;
    }

    /**
     * Vérifie si l'utilisateur peut lire un traitement
     * Raccourci pour can($idGroupe, $slug, 'read')
     */
    public function canRead(int $idGroupe, string $slugTraitement): bool
    {
        return $this->can($idGroupe, $slugTraitement, 'read');
    }

    /**
     * Vérifie si l'utilisateur peut créer dans un traitement
     * Raccourci pour can($idGroupe, $slug, 'create')
     */
    public function canCreate(int $idGroupe, string $slugTraitement): bool
    {
        return $this->can($idGroupe, $slugTraitement, 'create');
    }

    /**
     * Vérifie si l'utilisateur peut modifier dans un traitement
     * Raccourci pour can($idGroupe, $slug, 'update')
     */
    public function canUpdate(int $idGroupe, string $slugTraitement): bool
    {
        return $this->can($idGroupe, $slugTraitement, 'update');
    }

    /**
     * Vérifie si l'utilisateur peut supprimer dans un traitement
     * Raccourci pour can($idGroupe, $slug, 'delete')
     */
    public function canDelete(int $idGroupe, string $slugTraitement): bool
    {
        return $this->can($idGroupe, $slugTraitement, 'delete');
    }

    /**
     * Récupère toutes les permissions d'un groupe pour un traitement
     * 
     * @param int $idGroupe ID du groupe
     * @param string $slugTraitement Slug du traitement
     * @return array ['read' => bool, 'create' => bool, 'update' => bool, 'delete' => bool]
     */
    public function getPermissions(int $idGroupe, string $slugTraitement): array
    {
        $stmt = $this->pdo->prepare("
            SELECT d.can_read, d.can_create, d.can_update, d.can_delete
            FROM droits d
            JOIN traitement t ON d.id_traitement = t.id_traitement
            WHERE d.id_GU = :groupe AND t.lib_traitement = :slug
            LIMIT 1
        ");
        
        $stmt->execute([
            'groupe' => $idGroupe, 
            'slug' => $slugTraitement
        ]);
        
        $row = $stmt->fetch();
        
        if (!$row) {
            return [
                'read'   => false,
                'create' => false,
                'update' => false,
                'delete' => false,
            ];
        }
        
        return [
            'read'   => (bool) $row['can_read'],
            'create' => (bool) $row['can_create'],
            'update' => (bool) $row['can_update'],
            'delete' => (bool) $row['can_delete'],
        ];
    }

    /**
     * Vérifie et lève une exception si l'utilisateur n'a pas le droit
     * 
     * @param int $idGroupe ID du groupe
     * @param string $slugTraitement Slug du traitement
     * @param string $action Action requise
     * @throws \RuntimeException Si l'utilisateur n'a pas la permission
     */
    public function requirePermission(int $idGroupe, string $slugTraitement, string $action = 'read'): void
    {
        if (!$this->can($idGroupe, $slugTraitement, $action)) {
            throw new \RuntimeException(
                "Accès refusé: permission '{$action}' requise pour '{$slugTraitement}'"
            );
        }
    }

    // ================================================================
    // MÉTHODES UTILITAIRES DE SÉCURITÉ
    // ================================================================

    /**
     * Nettoie et valide une entrée utilisateur
     * 
     * @param string $input Entrée à nettoyer
     * @param int $maxLength Longueur maximale autorisée
     * @return string Entrée nettoyée
     */
    public function sanitizeInput(string $input, int $maxLength = 255): string
    {
        $input = strip_tags($input);
        $input = trim($input);
        $input = mb_substr($input, 0, $maxLength);
        
        return $input;
    }

    /**
     * Génère un token aléatoire sécurisé
     * 
     * @param int $length Nombre de bytes (la longueur finale sera le double en hexadécimal)
     * @return string Token hexadécimal
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Vérifie si une adresse IP est valide
     * 
     * @param string $ip Adresse IP à vérifier
     * @return bool True si l'IP est valide
     */
    public function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Récupère l'adresse IP réelle du client
     * (tient compte des proxies)
     * 
     * @return string Adresse IP
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Vide le cache des permissions
     * (utile après modification des droits)
     */
    public function clearPermissionsCache(): void
    {
        $this->permissionsCache = [];
    }
}
