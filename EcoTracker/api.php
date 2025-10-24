<?php
session_start();
header('Content-Type: application/json');

const TOTAL_MAX_KG = 100;
const USER_MAX_GOAL = 100;

if (!isset($_SESSION['init'])) {
  $_SESSION['init'] = true;
  $_SESSION['goal'] = 20;
  $_SESSION['currentKg'] = 0.0;
  $_SESSION['markers'] = []; // ['type'=>'waste'|'like','material'=>..., 'kg'=>float,'lat'=>float,'lng'=>float, 'ts'=>int]
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
      json_out(['ok'=>false,'error'=>'csrf']);
    }
  }
}

$action = $_GET['action'] ?? 'state';

if ($action === 'get_state' || $action === 'state') {
  json_out([
    'ok'=>true,
    'goal'=>(int)$_SESSION['goal'],
    'currentKg'=>(float)$_SESSION['currentKg'],
    'totalMaxKg'=>TOTAL_MAX_KG,
    'markers'=>$_SESSION['markers'],
    'csrf'=>$_SESSION['csrf'],
  ]);
}

if ($action === 'set_goal') {
  require_csrf();
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true) ?: [];
  $goal = clamp_goal($data['goal'] ?? 20);
  $_SESSION['goal'] = $goal;
  json_out(['ok'=>true,'goal'=>$goal]);
}

if ($action === 'add_marker') {
  require_csrf();
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true) ?: [];

  $type = ($data['type'] ?? '') === 'like' ? 'like' : 'waste';
  $material = trim((string)($data['material'] ?? 'Plastic'));
  $kg = max(0.0, (float)($data['kg'] ?? 0));
  $lat = (float)($data['lat'] ?? 0);
  $lng = (float)($data['lng'] ?? 0);

  $goal = clamp_goal($_SESSION['goal']);
  $cap  = min($goal, TOTAL_MAX_KG);
  $remaining = max(0.0, $cap - $_SESSION['currentKg']);
  $inc = ($type === 'waste') ? min($kg, $remaining) : 0.0;

  $_SESSION['markers'][] = [
    'type'=>$type, 'material'=>$material, 'kg'=>$inc,
    'lat'=>$lat, 'lng'=>$lng, 'ts'=>time()
  ];
  if ($inc > 0) {
    $_SESSION['currentKg'] = round($_SESSION['currentKg'] + $inc, 3);
  }

  json_out([
    'ok'=>true,
    'addedKg'=>$inc,
    'currentKg'=>(float)$_SESSION['currentKg'],
    'goal'=>$goal,
    'reached'=>($_SESSION['currentKg'] >= $cap)
  ]);
}

http_response_code(404);
json_out(['ok'=>false,'error'=>'unknown_action']);
