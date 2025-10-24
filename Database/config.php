<?php
// ---- DB CONFIG (XAMPP defaults) ----
const DB_HOST = '127.0.0.1';
const DB_NAME = 'BinGo';
const DB_USER = 'root';
const DB_PASS = ''; // XAMPP default is empty

// ---- Secure sessions (fine for localhost dev) ----
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// ---- PDO connection ----
try {
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB connection failed. Check config.php. Error: " . htmlspecialchars($e->getMessage());
  exit;
}

// ---- CSRF helpers ----
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}
function csrf_check(): void {
  $hdr = $_SERVER['HTTP_X_CSRF'] ?? null;
  $post = $_POST['csrf'] ?? null;
  $token = $hdr ?: $post;
  if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(403);
    exit('Invalid CSRF token');
  }
}
