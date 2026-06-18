<?php
// ── config/config.php ──────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('APP_ENV',        $_ENV['APP_ENV']        ?? 'development');
define('SMTP_HOST',      $_ENV['SMTP_HOST']      ?? 'smtp.gmail.com');
define('SMTP_USER',      $_ENV['SMTP_USER']      ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS']      ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'GuardReport');
define('APP_NAME',       'GuardReport');