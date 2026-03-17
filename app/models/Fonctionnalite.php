<?php

class Fonctionnalite
{
    private $pdo;

    private static $hasSlugPermissionColumn = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer toutes les fonctionnalités d'une catégorie
     */
    public function getFonctionnalitesByCategorie($id_categorie)
    {
        $sql = "SELECT * FROM fonctionnalites 
                WHERE id_categorie = :id_categorie AND actif = TRUE 
                ORDER BY ordre_fonctionnalite ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer une fonctionnalité par son ID
     */
    public function getFonctionnaliteById($id_fonctionnalite)
    {
        $sql = "SELECT * FROM fonctionnalites WHERE id_fonctionnalite = :id_fonctionnalite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer une fonctionnalité par son code
     */
    public function getFonctionnaliteByCode($code_fonctionnalite)
    {
        $sql = "SELECT * FROM fonctionnalites WHERE code_fonctionnalite = :code_fonctionnalite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':code_fonctionnalite', $code_fonctionnalite, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getFonctionnaliteBySlugPermission($slug_permission)
    {
        if (!$this->hasSlugPermissionColumn()) {
            return false;
        }

        $sql = "SELECT * FROM fonctionnalites WHERE slug_permission = :slug_permission";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':slug_permission', $slug_permission, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getFonctionnaliteByIdentifier($identifier)
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return false;
        }

        $feature = $this->getFonctionnaliteBySlugPermission($identifier);
        if ($feature) {
            return $feature;
        }

        $feature = $this->getFonctionnaliteByCode($identifier);
        if ($feature) {
            return $feature;
        }

        return $this->getFonctionnaliteByUrl($identifier);
    }

    /**
     * Récupérer une fonctionnalité par son URL
     * Supporte les formats: ?page=dashboard, page=dashboard ou dashboard
     */
    public function getFonctionnaliteByUrl($url_fonctionnalite)
    {
        $rawUrl = trim((string) $url_fonctionnalite);
        $pageUrl = strpos($rawUrl, '?page=') === 0 ? $rawUrl : '?page=' . ltrim($rawUrl, '?');
        $pageOnly = preg_replace('/^\??page=/', '', $rawUrl);

        $sql = "SELECT * FROM fonctionnalites
                WHERE url_fonctionnalite IN (:raw_url, :page_url)
                   OR REPLACE(url_fonctionnalite, '?page=', '') = :page_only
                ORDER BY (url_fonctionnalite = :raw_url_exact) DESC,
                         (url_fonctionnalite = :page_url_exact) DESC,
                         actif DESC,
                         id_fonctionnalite ASC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':raw_url', $rawUrl, PDO::PARAM_STR);
        $stmt->bindValue(':page_url', $pageUrl, PDO::PARAM_STR);
        $stmt->bindValue(':page_only', $pageOnly, PDO::PARAM_STR);
        $stmt->bindValue(':raw_url_exact', $rawUrl, PDO::PARAM_STR);
        $stmt->bindValue(':page_url_exact', $pageUrl, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer les fonctionnalités accessibles par un groupe utilisateur pour une catégorie
     */
    public function getFonctionnalitesForGroupeAndCategorie($id_GU, $id_categorie)
    {
        $sql = "SELECT f.*, p.peut_voir, p.peut_creer, p.peut_modifier, p.peut_supprimer
                FROM fonctionnalites f
                INNER JOIN permissions p ON f.id_fonctionnalite = p.id_fonctionnalite
                WHERE p.id_GU = :id_GU 
                AND f.id_categorie = :id_categorie 
                AND p.peut_voir = TRUE 
                AND f.actif = TRUE
                ORDER BY f.ordre_fonctionnalite ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer toutes les fonctionnalités accessibles par un groupe utilisateur
     */
    public function getFonctionnalitesForGroupe($id_GU)
    {
        $sql = "SELECT f.*, c.lib_categorie, c.code_categorie, 
                       p.peut_voir, p.peut_creer, p.peut_modifier, p.peut_supprimer
                FROM fonctionnalites f
                INNER JOIN categories_fonctionnalites c ON f.id_categorie = c.id_categorie
                INNER JOIN permissions p ON f.id_fonctionnalite = p.id_fonctionnalite
                WHERE p.id_GU = :id_GU AND p.peut_voir = TRUE AND f.actif = TRUE AND c.actif = TRUE
                ORDER BY c.ordre_categorie ASC, f.ordre_fonctionnalite ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer les sous-pages d'une page parente
     */
    public function getSousPages($code_page_parente)
    {
        $sql = "SELECT * FROM fonctionnalites 
                WHERE page_parente = :page_parente AND est_sous_page = TRUE AND actif = TRUE
                ORDER BY ordre_fonctionnalite ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':page_parente', $code_page_parente, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Créer une nouvelle fonctionnalité
     */
    public function createFonctionnalite($data)
    {
        $hasSlug = $this->hasSlugPermissionColumn();
        $columns = [
            'id_categorie',
            'code_fonctionnalite',
            'lib_fonctionnalite',
            'label_fonctionnalite',
            'description_fonctionnalite',
            'url_fonctionnalite',
            'icone_fonctionnalite',
            'ordre_fonctionnalite',
            'est_sous_page',
            'page_parente',
            'actif',
        ];

        $values = [
            ':id_categorie',
            ':code',
            ':lib',
            ':label',
            ':description',
            ':url',
            ':icone',
            ':ordre',
            ':est_sous_page',
            ':page_parente',
            ':actif',
        ];

        if ($hasSlug) {
            $columns[] = 'slug_permission';
            $values[] = ':slug_permission';
        }

        $sql = "INSERT INTO fonctionnalites (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $values) . ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_categorie', $data['id_categorie'], PDO::PARAM_INT);
        $stmt->bindParam(':code', $data['code_fonctionnalite']);
        $stmt->bindParam(':lib', $data['lib_fonctionnalite']);
        $stmt->bindParam(':label', $data['label_fonctionnalite']);
        $stmt->bindParam(':description', $data['description_fonctionnalite']);
        $stmt->bindParam(':url', $data['url_fonctionnalite']);
        $stmt->bindParam(':icone', $data['icone_fonctionnalite']);
        $stmt->bindParam(':ordre', $data['ordre_fonctionnalite'], PDO::PARAM_INT);
        $stmt->bindParam(':est_sous_page', $data['est_sous_page'], PDO::PARAM_BOOL);
        $stmt->bindParam(':page_parente', $data['page_parente']);
        $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_BOOL);
        if ($hasSlug) {
            $slugPermission = $data['slug_permission'] ?? $data['code_fonctionnalite'];
            $stmt->bindParam(':slug_permission', $slugPermission);
        }

        return $stmt->execute();
    }

    /**
     * Mettre à jour une fonctionnalité
     */
    public function updateFonctionnalite($id_fonctionnalite, $data)
    {
        $set = [
            'lib_fonctionnalite = :lib',
            'label_fonctionnalite = :label',
            'description_fonctionnalite = :description',
            'url_fonctionnalite = :url',
            'icone_fonctionnalite = :icone',
            'ordre_fonctionnalite = :ordre',
            'actif = :actif',
        ];

        $hasSlug = $this->hasSlugPermissionColumn() && array_key_exists('slug_permission', $data);
        if ($hasSlug) {
            $set[] = 'slug_permission = :slug_permission';
        }

        $sql = "UPDATE fonctionnalites 
                SET " . implode(', ', $set) . "
                WHERE id_fonctionnalite = :id_fonctionnalite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->bindParam(':lib', $data['lib_fonctionnalite']);
        $stmt->bindParam(':label', $data['label_fonctionnalite']);
        $stmt->bindParam(':description', $data['description_fonctionnalite']);
        $stmt->bindParam(':url', $data['url_fonctionnalite']);
        $stmt->bindParam(':icone', $data['icone_fonctionnalite']);
        $stmt->bindParam(':ordre', $data['ordre_fonctionnalite'], PDO::PARAM_INT);
        $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_BOOL);
        if ($hasSlug) {
            $stmt->bindParam(':slug_permission', $data['slug_permission']);
        }

        return $stmt->execute();
    }

    /**
     * Mise à jour complète (admin menus): structure + catégorie + visibilité
     * - permet de modifier: id_categorie, (lib/label/desc/url/icone/ordre), est_sous_page, page_parente, actif
     * - par défaut, ne modifie pas code_fonctionnalite (unique) sauf si fourni explicitement.
     */
    public function updateFonctionnaliteAdmin($id_fonctionnalite, $data)
    {
        $set = [
            "id_categorie = :id_categorie",
            "lib_fonctionnalite = :lib",
            "label_fonctionnalite = :label",
            "description_fonctionnalite = :description",
            "url_fonctionnalite = :url",
            "icone_fonctionnalite = :icone",
            "ordre_fonctionnalite = :ordre",
            "est_sous_page = :est_sous_page",
            "page_parente = :page_parente",
            "actif = :actif",
        ];

        $hasCode = isset($data['code_fonctionnalite']) && $data['code_fonctionnalite'] !== '';
        if ($hasCode) {
            $set[] = "code_fonctionnalite = :code";
        }

        $hasSlug = $this->hasSlugPermissionColumn() && array_key_exists('slug_permission', $data);
        if ($hasSlug) {
            $set[] = "slug_permission = :slug_permission";
        }

        $sql = "UPDATE fonctionnalites SET " . implode(", ", $set) . " WHERE id_fonctionnalite = :id_fonctionnalite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $data['id_categorie'], PDO::PARAM_INT);
        $stmt->bindParam(':lib', $data['lib_fonctionnalite']);
        $stmt->bindParam(':label', $data['label_fonctionnalite']);
        $stmt->bindParam(':description', $data['description_fonctionnalite']);
        $stmt->bindParam(':url', $data['url_fonctionnalite']);
        $stmt->bindParam(':icone', $data['icone_fonctionnalite']);
        $stmt->bindParam(':ordre', $data['ordre_fonctionnalite'], PDO::PARAM_INT);
        $stmt->bindParam(':est_sous_page', $data['est_sous_page'], PDO::PARAM_BOOL);
        $stmt->bindParam(':page_parente', $data['page_parente']);
        $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_BOOL);
        if ($hasCode) {
            $stmt->bindParam(':code', $data['code_fonctionnalite']);
        }
        if ($hasSlug) {
            $stmt->bindParam(':slug_permission', $data['slug_permission']);
        }
        return $stmt->execute();
    }

    /**
     * Supprimer une fonctionnalité
     */
    public function deleteFonctionnalite($id_fonctionnalite)
    {
        $sql = "DELETE FROM fonctionnalites WHERE id_fonctionnalite = :id_fonctionnalite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function hasSlugPermissionColumn()
    {
        if (self::$hasSlugPermissionColumn !== null) {
            return self::$hasSlugPermissionColumn;
        }

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM fonctionnalites LIKE 'slug_permission'");
            self::$hasSlugPermissionColumn = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            self::$hasSlugPermissionColumn = false;
        }

        return self::$hasSlugPermissionColumn;
    }

    private function assertUniqueClickableUrl($url, $excludeId = null)
    {
        // L'audit applicatif contient volontairement plusieurs entrées de menu
        // pointant vers la même URL. On ne bloque donc plus ces doublons ici.
        return;
    }
}
