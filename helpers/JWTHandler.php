<?php
/** GuardReport — JWT Handler | File: helpers/JWTHandler.php */
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private string $secret;
    public function __construct() { $this->secret = JWT_SECRET_KEY; }

    public function generateToken(array $payload): string {
        if (isset($payload['is_super_admin'])) $payload['is_super_admin'] = (bool)$payload['is_super_admin'];
        return JWT::encode($payload, $this->secret, 'HS256');
    }
    public function validateToken(string $token): mixed {
        try {
            $d = JWT::decode($token, new Key($this->secret, 'HS256'));
            if (isset($d->is_super_admin)) $d->is_super_admin = (bool)$d->is_super_admin;
            return $d;
        } catch (Exception $e) {
            error_log('JWT validate: ' . $e->getMessage());
            return false;
        }
    }
}