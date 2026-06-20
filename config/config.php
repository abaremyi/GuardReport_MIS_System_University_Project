<?php
/** GuardReport — App Config | File: config/config.php */
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env variables (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, JWT_SECRET_KEY, SMTP_*)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad = won't crash if .env is missing a key

define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY'] ?? 'change_this_to_a_long_random_secret_key');
define('APP_ENV',        $_ENV['APP_ENV']        ?? 'development');
define('APP_NAME',       'GuardReport');

// SMTP — leave SMTP_USER blank in .env to disable email sending (no crash, just skips).
define('SMTP_HOST',      $_ENV['SMTP_HOST']      ?? 'smtp.gmail.com');
define('SMTP_USER',      $_ENV['SMTP_USER']      ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS']      ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'GuardReport');