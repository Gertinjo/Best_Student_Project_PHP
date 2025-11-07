<?php

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'bingo';

const UPLOADS_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
const UPLOADS_URL_BASE = 'uploads/';

function ensure_uploads_dir(): void {
  if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0775, true);
  }
}

function get_db(): mysqli {
  static $conn;
  if ($conn instanceof mysqli) {
    return $conn;
  }

  $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

  if ($conn->connect_error) {
    throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
  }

  $conn->set_charset('utf8mb4');
  return $conn;
}

function ensure_markers_table(mysqli $db): void {
  $sql = <<<SQL
CREATE TABLE IF NOT EXISTS markers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marker_type   ENUM('waste','like') NOT NULL DEFAULT 'waste',
  title         VARCHAR(255)         NOT NULL,
  description   TEXT                 NOT NULL,
  material      ENUM('Plastic','Paper','Glass','Metal','E-waste') NULL,
  weight_kg     DECIMAL(7,2)         NOT NULL DEFAULT 0.00,
  location_name VARCHAR(255)         NULL,
  latitude      DECIMAL(9,6)         NOT NULL,
  longitude     DECIMAL(9,6)         NOT NULL,
  photo_data    LONGTEXT             NULL,
  photo_mime    VARCHAR(255)         NULL,
  created_at    TIMESTAMP            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_marker_type (marker_type),
  KEY idx_lat_lng (latitude, longitude)
)
ENGINE=InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;
SQL;

  if (!$db->query($sql)) {
    throw new RuntimeException('Failed to ensure markers table: ' . $db->error);
  }

  $columns = [
    'status' => "ALTER TABLE markers ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'",
    'team_size' => "ALTER TABLE markers ADD COLUMN team_size INT UNSIGNED NOT NULL DEFAULT 0",
  ];

  foreach ($columns as $name => $alter) {
    $result = $db->query("SHOW COLUMNS FROM markers LIKE '" . $db->real_escape_string($name) . "'");
    if ($result && $result->num_rows === 0) {
      if (!$db->query($alter)) {
        throw new RuntimeException('Failed updating markers table (' . $name . '): ' . $db->error);
      }
    }
  }

  $db->query("UPDATE markers SET status='pending' WHERE status IS NULL");
  $db->query("UPDATE markers SET team_size=0 WHERE team_size IS NULL");
}

function ensure_users_table(mysqli $db): void {
  $result = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
  if ($result && $result->num_rows === 0) {
    $alter = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash";
    if (!$db->query($alter)) {
      throw new RuntimeException('Failed to add is_admin column: ' . $db->error);
    }
    $db->query("UPDATE users SET is_admin=0 WHERE is_admin IS NULL");
  }
}

function is_user_admin(mysqli $db, ?int $userId): bool {
  if (!$userId || $userId <= 0) {
    return false;
  }
  $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param('i', $userId);
  if (!$stmt->execute()) {
    return false;
  }
  $result = $stmt->get_result();
  if (!$result || $result->num_rows === 0) {
    return false;
  }
  $row = $result->fetch_assoc();
  return isset($row['is_admin']) && (int)$row['is_admin'] === 1;
}


