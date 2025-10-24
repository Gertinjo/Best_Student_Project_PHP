<?php
include_once '../Database/config.php';

$errors = [];
$old = ['name'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $name  = trim($_POST['name']  ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password']   ?? '';
  $pass2 = $_POST['password2']  ?? '';

  $old['name'] = $name;
  $old['email']= $email;

  // Validate
  if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
  if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
  if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

  if (!$errors) {
    // Check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = 'An account with that email already exists.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
      $stmt->execute([$name, $email, $hash]);
      // Auto-login after signup
      $_SESSION['user'] = [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'email'=> $email
      ];
      header('Location: ../EcoTracker/index.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EcoTracker — Sign Up</title>
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f6f7fb}
    .wrap{max-width:420px;margin:8vh auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px}
    h1{margin:0 0 12px}
    .error{background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px;margin-bottom:12px;color:#991b1b}
    label{display:block;margin:10px 0 6px}
    input{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
    button{margin-top:14px;width:100%;padding:12px;border:0;border-radius:10px;background:#16a34a;color:#fff;font-weight:700;cursor:pointer}
    .muted{margin-top:10px;color:#6b7280}
    a{color:#2563eb;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Create your account</h1>
    <?php if ($errors): ?>
      <div class="error">
        <?php foreach($errors as $e) echo htmlspecialchars($e)."<br>"; ?>
      </div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
      <label for="name">Name</label>
      <input id="name" name="name" value="<?= htmlspecialchars($old['name'], ENT_QUOTES) ?>" required>

      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= htmlspecialchars($old['email'], ENT_QUOTES) ?>" required>

      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>

      <label for="password2">Confirm Password</label>
      <input id="password2" type="password" name="password2" required>

      <button type="submit">Sign Up</button>
    </form>
    <p class="muted">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</body>
</html>
