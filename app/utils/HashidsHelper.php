<?php
/**
 * Hashids Helper Service
 * Encodes and decodes IDs for security
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Hashids\Hashids;

class HashidsHelper {
    private static $instance = null;
    private $hashids;
    
    private function __construct() {
        // Load salt from environment variable
        $salt = $_ENV['HASHIDS_SALT'] ?? 'checkmaster-ufhb-default-salt-change-this';
        $minLength = 8; // Minimum hash length
        
        $this->hashids = new Hashids($salt, $minLength);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Encode an ID
     * 
     * @param int $id The ID to encode
     * @return string The encoded hash
     */
    public static function encode($id) {
        $instance = self::getInstance();
        return $instance->hashids->encode($id);
    }
    
    /**
     * Decode a hash to get the original ID
     * 
     * @param string $hash The hash to decode
     * @return int|null The decoded ID or null if invalid
     */
    public static function decode($hash) {
        $instance = self::getInstance();
        $decoded = $instance->hashids->decode($hash);
        return !empty($decoded) ? $decoded[0] : null;
    }
    
    /**
     * Encode multiple IDs
     * 
     * @param array $ids Array of IDs to encode
     * @return string The encoded hash
     */
    public static function encodeMultiple(array $ids) {
        $instance = self::getInstance();
        return $instance->hashids->encode(...$ids);
    }
    
    /**
     * Decode a hash to get multiple IDs
     * 
     * @param string $hash The hash to decode
     * @return array Array of decoded IDs
     */
    public static function decodeMultiple($hash) {
        $instance = self::getInstance();
        return $instance->hashids->decode($hash);
    }
}
