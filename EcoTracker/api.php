<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

const TOTAL_MAX_KG = 100;
const USER_MAX_GOAL = 100;
const MAX_PHOTO_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_MATERIALS = ['Plastic', 'Paper', 'Glass', 'Metal', 'E-waste'];

if (!isset($_SESSION['init'])) {
  $_SESSION['init'] = true;
  $_SESSION['goal'] = 20;
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function json_out($arr) { echo json_encode($arr); exit; }

function clamp_goal($g) {
  $g = (int)$g;
  if ($g < 1) $g = 1;
  if ($g > USER_MAX_GOAL) $g = USER_MAX_GOAL;
  return $g;
}

function require_csrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hdr = $_SERVER['HTTP_X_CSRF'] ?? '';
    if (!$hdr || !hash_equals($_SESSION['csrf'], $hdr)) {
      http_response_code(403);
      json_out(['ok' => false, 'error' => 'csrf']);
    }
  }
}

function fetch_current_kg(mysqli $db): float {
  $res = $db->query("SELECT COALESCE(SUM(weight_kg),0) AS total FROM markers WHERE marker_type='waste' AND status='pending'");
  if ($res === false) {
    throw new RuntimeException('Failed to read total weight.');
  }
  $row = $res->fetch_assoc();
  return (float)($row['total'] ?? 0.0);
}

function marker_row_to_payload(array $row): array {
  $marker = [
    'id' => (int)$row['id'],
    'type' => $row['marker_type'],
    'material' => $row['marker_type'] === 'like' ? null : $row['material'],
    'kg' => (float)$row['weight_kg'],
    'lat' => (float)$row['latitude'],
    'lng' => (float)$row['longitude'],
    'title' => $row['title'] ?? null,
    'description' => $row['description'] ?? null,
    'location' => $row['location_name'] ?? null,
    'status' => $row['status'] ?? 'pending',
    'teamSize' => isset($row['team_size']) ? (int)$row['team_size'] : 0,
    'resolvedAt' => $row['resolved_at'] ?? null,
    'createdAt' => $row['created_at'] ?? null,
  ];

  if (!empty($row['photo_data']) && !empty($row['photo_mime'])) {
    $photoValue = $row['photo_data'];
    if (strpos($photoValue, 'data:') !== 0) {
      $photoValue = 'data:' . $row['photo_mime'] . ';base64,' . $photoValue;
    }
    $marker['photo'] = $photoValue;
  }

  return $marker;
}

function fetch_markers(mysqli $db): array {
  $sql = "SELECT id, marker_type, title, description, material, weight_kg, location_name, latitude, longitude, photo_data, photo_mime, status, team_size, resolved_at, created_at FROM markers WHERE status='pending' ORDER BY id DESC";
  $res = $db->query($sql);
  if ($res === false) {
    throw new RuntimeException('Failed to read markers.');
  }
  $markers = [];
  while ($row = $res->fetch_assoc()) {
    $markers[] = marker_row_to_payload($row);
  }
  return $markers;
}

function fetch_admin_markers(mysqli $db): array {
  $sql = "SELECT id, marker_type, title, description, material, weight_kg, location_name, latitude, longitude, photo_data, photo_mime, status, team_size, resolved_at, created_at FROM markers ORDER BY (status='pending') DESC, created_at DESC";
  $res = $db->query($sql);
  if ($res === false) {
    throw new RuntimeException('Failed to read markers for admin.');
  }
  $markers = [];
  while ($row = $res->fetch_assoc()) {
    $markers[] = marker_row_to_payload($row);
  }
  return $markers;
}

function find_marker(mysqli $db, int $id): ?array {
  $stmt = $db->prepare("SELECT id, marker_type, title, description, material, weight_kg, location_name, latitude, longitude, photo_data, photo_mime, status, team_size, resolved_at, created_at FROM markers WHERE id = ?");
  if (!$stmt) {
    throw new RuntimeException('Failed to prepare fetch.');
  }
  $stmt->bind_param('i', $id);
  if (!$stmt->execute()) {
    throw new RuntimeException('Failed to load marker.');
  }
  $result = $stmt->get_result();
  if (!$result) {
    throw new RuntimeException('Failed to load marker result set.');
  }
  $row = $result->fetch_assoc();
  return $row ? marker_row_to_payload($row) : null;
}

function fetch_users(mysqli $db): array {
  $sql = "SELECT id, name, email, created_at FROM users ORDER BY created_at DESC";
  $res = $db->query($sql);
  if ($res === false) {
    throw new RuntimeException('Failed to read users.');
  }
  $users = [];
  while ($row = $res->fetch_assoc()) {
    $users[] = [
      'id' => (int)$row['id'],
      'name' => $row['name'],
      'email' => $row['email'],
      'createdAt' => $row['created_at'],
    ];
  }
  return $users;
}

function parse_photo_payload(?string $raw): ?array {
  if (!$raw) return null;

  $mime = null;
  $base64 = $raw;
  if (preg_match('/^data:(.+);base64,(.+)$/', $raw, $matches)) {
    $mime = strtolower(trim($matches[1]));
    $base64 = $matches[2];
  }

  $binary = base64_decode($base64, true);
  if ($binary === false) {
    throw new InvalidArgumentException('Invalid photo encoding.');
  }
  if (strlen($binary) > MAX_PHOTO_BYTES) {
    throw new InvalidArgumentException('Photo exceeds 5MB limit.');
  }

  if ($mime === null && class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detected = $finfo->buffer($binary);
    if ($detected) {
      $mime = $detected;
    }
  }

  if ($mime === null) {
    $mime = 'application/octet-stream';
  }

  return ['mime' => $mime, 'binary' => $binary, 'base64' => $base64];
}

function validate_material(?string $material): string {
  $trimmed = trim((string)$material);
  if (!in_array($trimmed, ALLOWED_MATERIALS, true)) {
    throw new InvalidArgumentException('Invalid material.');
  }
  return $trimmed;
}

try {
  $db = get_db();
  ensure_markers_table($db);
  ensure_users_table($db);
} catch (Throwable $e) {
  http_response_code(500);
  json_out(['ok' => false, 'error' => 'db_connection', 'message' => $e->getMessage()]);
}

$action = $_GET['action'] ?? 'state';

try {
  if ($action === 'get_state' || $action === 'state') {
    $goal = clamp_goal($_SESSION['goal']);
    $currentKg = fetch_current_kg($db);
    $markers = fetch_markers($db);

    json_out([
      'ok' => true,
      'goal' => $goal,
      'currentKg' => $currentKg,
      'totalMaxKg' => TOTAL_MAX_KG,
      'markers' => $markers,
      'csrf' => $_SESSION['csrf'],
    ]);
  }

  if ($action === 'set_goal') {
    require_csrf();
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $goal = clamp_goal($data['goal'] ?? 20);
    $_SESSION['goal'] = $goal;
    json_out(['ok' => true, 'goal' => $goal]);
  }

  if ($action === 'admin_state') {
    $markers = fetch_admin_markers($db);
    $users = fetch_users($db);
    $pendingCount = 0;
    foreach ($markers as $marker) {
      if (($marker['status'] ?? 'pending') === 'pending') {
        $pendingCount++;
      }
    }
    json_out([
      'ok' => true,
      'users' => $users,
      'markers' => $markers,
      'pendingCount' => $pendingCount,
      'pendingKg' => fetch_current_kg($db),
    ]);
  }

  if ($action === 'update_marker') {
    require_csrf();
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    $markerId = isset($data['id']) ? (int)$data['id'] : 0;
    if ($markerId <= 0) {
      throw new InvalidArgumentException('Invalid marker id.');
    }

    $decision = strtolower(trim((string)($data['decision'] ?? '')));
    if (!in_array($decision, ['repair','ignore'], true)) {
      throw new InvalidArgumentException('Decision must be repair or ignore.');
    }

    $teamSize = 0;
    if ($decision === 'repair') {
      $teamSize = isset($data['teamSize']) ? (int)$data['teamSize'] : 0;
      if ($teamSize < 0) {
        throw new InvalidArgumentException('Team size cannot be negative.');
      }
    }

    $status = $decision === 'repair' ? 'repair' : 'ignored';

    $stmt = $db->prepare("UPDATE markers SET status=?, team_size=?, resolved_at=NOW() WHERE id=? AND status='pending'");
    if (!$stmt) {
      throw new RuntimeException('Failed to prepare marker update.');
    }
    $stmt->bind_param('sii', $status, $teamSize, $markerId);
    if (!$stmt->execute()) {
      throw new RuntimeException('Failed to update marker.');
    }
    if ($stmt->affected_rows === 0) {
      throw new InvalidArgumentException('Marker already processed or not found.');
    }

    $updatedMarker = find_marker($db, $markerId);

    json_out([
      'ok' => true,
      'marker' => $updatedMarker,
      'currentKg' => fetch_current_kg($db),
    ]);
  }

  if ($action === 'add_marker') {
    require_csrf();
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    $type = ($data['type'] ?? '') === 'like' ? 'like' : 'waste';
    if (!isset($data['lat'], $data['lng'])) {
      throw new InvalidArgumentException('Missing coordinates.');
    }
    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];

    $goal = clamp_goal($_SESSION['goal']);
    $cap  = min($goal, TOTAL_MAX_KG);
    $currentKg = fetch_current_kg($db);
    $remaining = max(0.0, $cap - $currentKg);

    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $locationNameRaw = trim((string)($data['location'] ?? ''));
    $locationParam = $locationNameRaw === '' ? null : $locationNameRaw;

    if ($type === 'waste') {
      if ($title === '') {
        throw new InvalidArgumentException('Title is required.');
      }
      if ($description === '') {
        throw new InvalidArgumentException('Description is required.');
      }

      $material = validate_material($data['material'] ?? null);
      $kg = max(0.0, (float)($data['kg'] ?? 0));
      if ($kg <= 0) {
        throw new InvalidArgumentException('Weight must be greater than 0.');
      }

      $photoPayload = isset($data['photo']) ? parse_photo_payload($data['photo']) : null;

      $weightToAdd = min($kg, $remaining);
      $photoData = $photoPayload['base64'] ?? null;
      $photoMime = $photoPayload['mime'] ?? null;
    } else {
      $material = null;
      $weightToAdd = 0.0;
      if ($title === '') $title = 'Liked spot';
      if ($description === '') $description = 'Someone liked this location.';
      $photoData = null;
      $photoMime = null;
    }

    $status = 'pending';
    $teamSize = 0;
    $resolved = null;

    $stmt = $db->prepare("INSERT INTO markers (marker_type, title, description, material, weight_kg, location_name, latitude, longitude, photo_data, photo_mime, status, team_size, resolved_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    if (!$stmt) {
      throw new RuntimeException('Failed to prepare insert.');
    }

    $stmt->bind_param(
      'ssssdsddsssis',
      $type,
      $title,
      $description,
      $material,
      $weightToAdd,
      $locationParam,
      $lat,
      $lng,
      $photoData,
      $photoMime,
      $status,
      $teamSize,
      $resolved
    );

    if (!$stmt->execute()) {
      throw new RuntimeException('Failed to insert marker.');
    }

    $insertId = (int)$stmt->insert_id;
    if ($insertId === 0) {
      $insertId = (int)$db->insert_id;
    }

    $updatedKg = fetch_current_kg($db);
    $marker = find_marker($db, $insertId);

    json_out([
      'ok' => true,
      'addedKg' => $weightToAdd,
      'currentKg' => $updatedKg,
      'goal' => $goal,
      'reached' => ($updatedKg >= $cap),
      'marker' => $marker,
    ]);
  }

  http_response_code(404);
  json_out(['ok' => false, 'error' => 'unknown_action']);
} catch (InvalidArgumentException $e) {
  http_response_code(422);
  json_out(['ok' => false, 'error' => 'validation', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
  http_response_code(500);
  json_out(['ok' => false, 'error' => 'server', 'message' => $e->getMessage()]);
}
