<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();

header("Content-Security-Policy: default-src 'self'; object-src 'none'; script-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; frame-ancestors 'self'; form-action 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/helpers/auth-helpers.php';
require_once __DIR__ . '/services/User.php';
require_once __DIR__ . '/services/ValidationService.php';
require_once __DIR__ . '/services/RateLimitService.php';

$host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'password_manager';
$user = getenv('DB_USER') ?: 'app_user';
$pass = getenv('DB_PASS') ?: 'app_password';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Authentification requise.']);
        exit();
    }
}

function require_page_login(): void {
    if (!isset($_SESSION['user_id'])) {
        $redirectURL = urlencode($_SERVER['REQUEST_URI']);
        header('Location: /login?redirect_to=' . $redirectURL);
        exit();
    }
}