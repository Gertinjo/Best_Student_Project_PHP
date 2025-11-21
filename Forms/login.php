<?php
include_once '../database/config.php';  // $pdo, csrf_token, csrf_check, session_start

$companyName = 'BinGo';

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $old['email'] = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email.';
    }
    if ($pass === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        // IMPORTANT: include is_admin here
        $stmt = $pdo->prepare('
            SELECT id, name, email, password_hash, is_admin
            FROM users
            WHERE email = ?
            LIMIT 1
        ');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $errors[] = 'Email or password is incorrect.';
        } else {
            // store full user info into session
            $_SESSION['user'] = [
                'id'       => (int)$user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'is_admin' => (int)$user['is_admin'],  // <-- THIS IS THE KEY
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
  <title>EcoTracker — Login</title>
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
    .brand{font-size:14px;color:#6b7280;margin-bottom:8px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="brand">Company name is: <strong><?= htmlspecialchars($companyName) ?></strong></div>
    <h1>Welcome back</h1>

    <?php if ($errors): ?>
      <div class="error">
        <?php foreach($errors as $e) echo htmlspecialchars($e)."<br>"; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="<?= htmlspecialchars($old['email'], ENT_QUOTES) ?>" required>

      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>

      <button type="submit">Log In</button>
    </form>

    <p class="muted">No account? <a href="signup.php">Create one</a></p>
  </div>
</body>
</html>
