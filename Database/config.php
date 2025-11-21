<?php
// database/config.php

// ===== DB CONFIG (XAMPP defaults, adjust db name if needed) =====
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bingo');  // make sure this exists
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default empty

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== PDO =====
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Throwable $e) {
    die("DB ERROR: " . $e->getMessage());
}

// ===== CSRF =====
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? null);
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}
