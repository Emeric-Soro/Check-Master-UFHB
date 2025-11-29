<?php
namespace App\Services;

use Hashids\Hashids;

class HashIdService
{
    private $hashids;

    public function __construct()
    {
        // Initialize Hashids with a secret salt and minimum hash length
        // In a real app, the salt should be in an environment variable
        $salt = $_ENV['HASHID_SALT'] ?? 'CheckMasterSecureSalt2024';
        $this->hashids = new Hashids($salt, 10);
    }

    public function encode($id)
    {
        return $this->hashids->encode($id);
    }

    public function decode($hash)
    {
        $decoded = $this->hashids->decode($hash);
        return isset($decoded[0]) ? $decoded[0] : null;
    }
}
