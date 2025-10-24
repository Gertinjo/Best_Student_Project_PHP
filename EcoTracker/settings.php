<?php
session_start();
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];
$goal = isset($_SESSION['goal']) ? (int)$_SESSION['goal'] : 20;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EcoTracker — Settings</title>
  <meta name="csrf" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
  <style><?php require __DIR__.'/style.php'; ?></style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ EcoTracker</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="settings.php" class="active">Settings</a>
    </nav>
    <div class="footnote">Preferences are saved per session</div>
  </aside>

  <main class="main">
    <div class="card" style="max-width:860px;margin:0 auto">
      <h2 class="title">Settings</h2>

      <div class="section">
        <h3>Appearance</h3>
        <div class="f">
          <div class="row2" style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center">
            <label for="themeSwitch">Theme</label>
            <label class="switch">
              <input id="themeSwitch" type="checkbox">
              <span class="slider"></span>
            </label>
          </div>
          <p class="small">Off = Light • On = Dark</p>
        </div>
      </div>

      <hr class="divider">

      <div class="section">
        <h3>Goal</h3>
        <form id="goalForm" class="f">
          <div class="row2" style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end">
            <div>
              <label for="goalKg">Target (kg, max 100)</label>
              <input id="goalKg" type="number" min="1" max="100" step="1" value="<?php echo (int)$goal; ?>" />
            </div>
            <button class="btn primary" type="submit">Save</button>
          </div>
          <div class="small">Server enforces a hard cap of 100 kg.</div>
        </form>
      </div>
    </div>
  </main>

  <script>
    // Theme persistence
    const theme = localStorage.getItem('ecotrack_theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
    const themeSwitch = document.getElementById('themeSwitch');
    themeSwitch.checked = (theme === 'dark');
    themeSwitch.addEventListener('change', ()=>{
      const t = themeSwitch.checked ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
      localStorage.setItem('ecotrack_theme', t);
    });

    // Save goal (server clamps to 100)
    document.getElementById('goalForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const v = Math.max(1, Math.min(100, parseInt(document.getElementById('goalKg').value||'20',10)));
      const csrf = document.querySelector('meta[name="csrf"]').content;
      const res  = await fetch('api.php?action=set_goal', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF':csrf},
        body: JSON.stringify({ goal:v })
      });
      const data = await res.json();
      if (data.ok) {
        alert('Goal saved: '+data.goal+' kg');
      } else {
        alert('Failed to save goal');
      }
    });
  </script>
</body>
</html>
