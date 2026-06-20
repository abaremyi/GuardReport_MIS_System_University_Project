<?php
/** GuardReport — Auth Middleware | File: helpers/AuthMiddleware.php */
require_once __DIR__ . '/JWTHandler.php';

class AuthMiddleware {
    private JWTHandler $jwt;
    public function __construct() { $this->jwt = new JWTHandler(); }

    public function authenticate(array $required = []): array {
        $token = $_COOKIE['auth_token'] ?? '';
        if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $h = $_SERVER['HTTP_AUTHORIZATION'];
            if (str_starts_with($h,'Bearer ')) $token = substr($h, 7);
        }
        if (!$token) return ['authenticated'=>false,'message'=>'No token provided'];
        $decoded = $this->jwt->validateToken($token);
        if (!$decoded) return ['authenticated'=>false,'message'=>'Invalid or expired token'];
        if (isset($decoded->account_status) && $decoded->account_status !== 'active')
            return ['authenticated'=>false,'message'=>'Account is '.$decoded->account_status];

        if (!empty($required)) {
            $perms = (array)($decoded->permissions ?? []);
            if (!$decoded->is_super_admin) {
                $missing = array_diff($required, $perms);
                if (!empty($missing)) return ['authenticated'=>false,'message'=>'Insufficient permissions'];
            }
        }
        return ['authenticated'=>true,'user'=>$decoded];
    }

    public function requireAuth(array $required = []): mixed {
        $auth = $this->authenticate($required);
        if (!$auth['authenticated']) {
            $isJson = str_contains($_SERVER['REQUEST_URI']??'','/api/')
                   || (($_SERVER['HTTP_ACCEPT']??'') === 'application/json');
            if ($isJson) {
                header('Content-Type: application/json'); http_response_code(401);
                echo json_encode(['success'=>false,'message'=>$auth['message']]); exit;
            }
            setcookie('auth_token','',time()-3600,'/');
            header('Location: '.url('login')); exit;
        }
        return $auth['user'];
    }
    public function optionalAuth(): mixed {
        $token = $_COOKIE['auth_token'] ?? '';
        return $token ? ($this->jwt->validateToken($token) ?: null) : null;
    }
}