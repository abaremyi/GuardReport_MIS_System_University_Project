<?php
/**
 * Path Configuration — GuardReport
 * File: config/paths.php
 */
define('ROOT_PATH',    dirname(__DIR__));
define('MODULES_PATH', ROOT_PATH . '/modules');
define('LAYOUTS_PATH', ROOT_PATH . '/layouts');
define('HELPERS_PATH', ROOT_PATH . '/helpers');
define('IMG_PATH',     ROOT_PATH . '/img');
define('CSS_PATH',     ROOT_PATH . '/css');
define('JS_PATH',      ROOT_PATH . '/js');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

$protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME'] ?? '');
$base_url  = $protocol . '://' . $host . $scriptDir;

define('BASE_URL',      $base_url);
define('IMG_URL',       BASE_URL . '/img');
define('CSS_URL',       BASE_URL . '/css');
define('JS_URL',        BASE_URL . '/js');
define('UPLOADS_URL',   BASE_URL . '/uploads');

function get_layout($name):string { return LAYOUTS_PATH . '/' . $name . '.php'; }
function get_helper($name):string { return HELPERS_PATH . '/' . $name . '.php'; }
function img_url($name):string    { return IMG_URL  . '/' . $name; }
function css_url($name):string    { return CSS_URL  . '/' . $name; }
function js_url($name):string     { return JS_URL   . '/' . $name; }
function upload_url($name):string { return UPLOADS_URL . '/' . $name; }
function url($path = ''):string   { return BASE_URL . (empty($path) ? '' : '/' . ltrim($path, '/')); }