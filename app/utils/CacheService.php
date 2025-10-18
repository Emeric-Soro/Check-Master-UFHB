<?php

/**
 * CacheService - Service de mise en cache des données peu volatiles
 * Utilise le système de fichiers pour le cache simple
 */
class CacheService {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 heure par défaut

    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../cache';
        
        // Créer le répertoire de cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Récupère une valeur du cache
     * 
     * @param string $key Clé du cache
     * @return mixed|null Valeur du cache ou null si expirée/inexistante
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $data = unserialize(file_get_contents($filename));
        
        // Vérifier l'expiration
        if ($data['expires_at'] < time()) {
            unlink($filename);
            return null;
        }

        return $data['value'];
    }

    /**
     * Stocke une valeur dans le cache
     * 
     * @param string $key Clé du cache
     * @param mixed $value Valeur à mettre en cache
     * @param int $ttl Durée de vie en secondes (null = utiliser la valeur par défaut)
     * @return bool Succès de l'opération
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        return file_put_contents($filename, serialize($data)) !== false;
    }

    /**
     * Supprime une entrée du cache
     * 
     * @param string $key Clé du cache
     * @return bool Succès de l'opération
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * Vide tout le cache
     * 
     * @return bool Succès de l'opération
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Récupère une valeur du cache ou l'exécute et la met en cache
     * 
     * @param string $key Clé du cache
     * @param callable $callback Fonction à exécuter si le cache est vide
     * @param int $ttl Durée de vie en secondes
     * @return mixed Valeur du cache ou résultat du callback
     */
    public function remember($key, callable $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Génère le nom de fichier pour une clé de cache
     * 
     * @param string $key Clé du cache
     * @return string Chemin complet du fichier
     */
    private function getCacheFilename($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Nettoie les entrées expirées du cache
     * 
     * @return int Nombre d'entrées supprimées
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '/*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(file_get_contents($file));
                
                if ($data['expires_at'] < time()) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
