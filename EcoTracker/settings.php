<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];
$goal = isset($_SESSION['goal']) ? (int)$_SESSION['goal'] : 20;

$isAdmin = false;
try {
  $db = get_db();
  ensure_users_table($db);
  $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
  $isAdmin = is_user_admin($db, $userId);
} catch (Throwable $e) {
  // Silently fail - user just won't see admin link
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BinGo — Settings</title>
  <meta name="csrf" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
  
  <style>/* Settings page specific styles */
/* ============ Global Styles ============ */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  color-scheme: dark;
}

body {
  background: linear-gradient(135deg, #0f1419 0%, #1a1f2e 100%);
  color: #e2e8f0;
  font-family: "Poppins", "Inter", system-ui, sans-serif;
  font-size: 16px;
  line-height: 1.6;
  min-height: 100vh;
}

/* ============ Layout ============ */
body {
  display: flex;
}

.sidebar {
  width: 240px;
  background: #111318;
  border-right: 1px solid #2d3748;
  padding: 24px 20px;
  display: flex;
  flex-direction: column;
  gap: 32px;
  min-height: 100vh;
  position: sticky;
  top: 0;
}

.brand {
  font-size: 24px;
  font-weight: 800;
  color: #16a34a;
  letter-spacing: -1px;
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.nav a {
  padding: 12px 16px;
  border-radius: 8px;
  text-decoration: none;
  color: #cbd5e1;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.nav a:hover {
  background: rgba(22, 163, 74, 0.1);
  color: #16a34a;
  border-left-color: #16a34a;
}

.nav a.active {
  background: rgba(22, 163, 74, 0.15);
  color: #16a34a;
  border-left-color: #16a34a;
}

.footnote {
  font-size: 12px;
  color: #64748b;
  margin-top: auto;
}

.main {
  flex: 1;
  padding: 32px;
  overflow-y: auto;
}

/* ============ Cards & Sections ============ */
.card {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid #2d3748;
  border-radius: 12px;
  padding: 24px;
  backdrop-filter: blur(10px);
  transition: all 0.3s;
}

.card:hover {
  border-color: #16a34a;
  box-shadow: 0 0 20px rgba(22, 163, 74, 0.1);
}

/* ============ Typography ============ */
h1, h2, h3 {
  font-family: "Poppins", sans-serif;
}

.super-title {
  font-size: 32px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.super-subtitle {
  font-size: 15px;
  color: #cbd5e1;
  margin: 0;
}

/* ============ Buttons ============ */
.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  background: #2d3748;
  color: #e2e8f0;
}

.btn:hover {
  background: #404854;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn.primary {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  color: #ffffff;
}

.btn.primary:hover {
  background: linear-gradient(135deg, #17a44a 0%, #16934d 100%);
  box-shadow: 0 4px 16px rgba(22, 163, 74, 0.4);
}

/* ============ Grid & Layout ============ */
.top {
  margin-bottom: 32px;
}

.row {
  display: flex;
  gap: 24px;
  margin-bottom: 24px;
}

/* ============ Settings Styles ============ */
.settings-container {
  padding: 40px;
}

.settings-section {
  margin-bottom: 32px;
}

.settings-section:last-child {
  margin-bottom: 0;
}

.section-header {
  margin-bottom: 24px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 8px 0;
}

.section-description {
  font-size: 14px;
  color: #cbd5e1;
  margin: 0;
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #e2e8f0;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #404854;
  border-radius: 8px;
  background: #1a1f2e;
  color: #e2e8f0;
  font-size: 14px;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #16a34a;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
}

.input-unit {
  position: absolute;
  right: 12px;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 600;
}

.form-hint {
  font-size: 12px;
  color: #94a3b8;
}

.settings-divider {
  height: 1px;
  background: linear-gradient(to right, #404854, transparent);
  margin: 24px 0;
}

.settings-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid #2d3748;
}

.settings-option:last-child {
  border-bottom: none;
}

.option-content {
  flex: 1;
}

.option-title {
  font-size: 15px;
  font-weight: 600;
  color: #e2e8f0;
  margin: 0 0 4px 0;
}

.option-desc {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

.toggle {
  position: relative;
  display: inline-flex;
  cursor: pointer;
}

.toggle input {
  display: none;
}

.toggle-switch {
  width: 44px;
  height: 24px;
  background: #404854;
  border-radius: 12px;
  position: relative;
  transition: background 0.3s;
}

.toggle-switch::before {
  content: "";
  position: absolute;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: left 0.3s;
}

.toggle input:checked ~ .toggle-switch {
  background: #16a34a;
}

.toggle input:checked ~ .toggle-switch::before {
  left: 22px;
}

.about-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.about-content p {
  color: #cbd5e1;
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

.about-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: rgba(22, 163, 74, 0.1);
  border: 1px solid #16a34a;
  border-radius: 8px;
  color: #a8e6c8;
  font-size: 13px;
  font-weight: 500;
}

.feature-icon {
  font-size: 16px;
}

.about-info {
  padding: 16px;
  background: #1a1f2e;
  border-radius: 8px;
  border-left: 3px solid #16a34a;
}

.about-info p {
  font-size: 13px;
  color: #cbd5e1;
  margin: 0;
}

.about-info p:not(:last-child) {
  margin-bottom: 8px;
}

/* ============ Responsive ============ */
@media (max-width: 768px) {
  .sidebar {
    width: 70px;
    padding: 12px;
  }

  .nav a span {
    display: none;
  }

  .main {
    padding: 16px;
  }

  .settings-container {
    padding: 20px;
  }

  .row {
    flex-direction: column;
  }
}
';
?>

  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ BinGo</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="settings.php" class="active">Settings</a>
      <?php if ($isAdmin): ?>
      <a href="admin.php">Admin</a>
      <?php endif; ?>
    </nav>
    <div class="footnote">PHP session demo • Manage goal & preferences</div>
  </aside>

  <main class="main">
    <!-- Added premium header section matching dashboard -->
    <div class="top">
      <div class="card">
        <h1 class="super-title">Settings</h1>
        <p class="super-subtitle">Customize your waste tracking preferences and goals</p>
      </div>
    </div>

    <div class="row">
      <!-- Wrapper card with premium styling -->
      <div class="card settings-container" style="max-width: 800px; margin: 0 auto; width: 100%;">
        
        <!-- Waste Goal Section -->
        <div class="settings-section">
          <div class="section-header">
            <h3 class="section-title">🎯 Waste Goal</h3>
            <p class="section-description">Set your target recycling goal. The app tracks your progress towards this limit.</p>
          </div>
          
          <form id="goalForm" class="settings-form">
            <div class="form-group">
              <label for="goalKg" class="form-label">Target Weight (kg)</label>
              <div class="input-wrapper">
                <input id="goalKg" type="number" min="1" max="100" step="1" value="<?php echo (int)$goal; ?>" placeholder="20" />
                <span class="input-unit">kg</span>
              </div>
              <div class="form-hint">Maximum allowed: 100 kg</div>
            </div>
            
            <button class="btn primary" type="submit" style="align-self: flex-start;">💾 Save Goal</button>
          </form>
        </div>

        <div class="settings-divider"></div>

        <!-- Preferences Section -->
        <div class="settings-section">
          <div class="section-header">
            <h3 class="section-title">⚙️ Preferences</h3>
            <p class="section-description">Configure your app experience</p>
          </div>
          
          <div class="settings-option">
            <div class="option-content">
              <h4 class="option-title">Default Material</h4>
              <p class="option-desc">Starting material for quick add (currently: Plastic)</p>
            </div>
            <button class="btn" style="font-size: 14px;">Edit</button>
          </div>

          <div class="settings-option">
            <div class="option-content">
              <h4 class="option-title">Location Services</h4>
              <p class="option-desc">Allow BinGo to access your current location</p>
            </div>
            <label class="toggle">
              <input type="checkbox" checked />
              <span class="toggle-switch"></span>
            </label>
          </div>

          <div class="settings-option">
            <div class="option-content">
              <h4 class="option-title">Notifications</h4>
              <p class="option-desc">Get alerts when you're close to your goal</p>
            </div>
            <label class="toggle">
              <input type="checkbox" />
              <span class="toggle-switch"></span>
            </label>
          </div>
        </div>

        <div class="settings-divider"></div>

        <!-- About Section -->
        <div class="settings-section">
          <div class="section-header">
            <h3 class="section-title">ℹ️ About BinGo</h3>
          </div>
          
          <div class="about-content">
            <p><strong>BinGo</strong> is a location-based waste tracking application designed to help you manage and monitor recyclable materials in your area.</p>
            
            <div class="about-features">
              <div class="feature-item">
                <span class="feature-icon">📍</span>
                <span>Location-based marker system</span>
              </div>
              <div class="feature-item">
                <span class="feature-icon">📊</span>
                <span>Real-time progress tracking</span>
              </div>
              <div class="feature-item">
                <span class="feature-icon">♻️</span>
                <span>Multi-material categorization</span>
              </div>
              <div class="feature-item">
                <span class="feature-icon">☁️</span>
                <span>Session-based data storage</span>
              </div>
            </div>

            <div class="about-info">
              <p><strong>Version:</strong> 1.0</p>
              <p><strong>Max Goal:</strong> 100 kg</p>
              <p><strong>Session-Based:</strong> Data resets on logout</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script>
    const CSRF = document.querySelector('meta[name="csrf"]').content;
    
    // Save goal (server clamps to 100)
    document.getElementById('goalForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const v = Math.max(1, Math.min(100, parseInt(document.getElementById('goalKg').value || '20', 10)));
      const res = await fetch('api.php?action=set_goal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
        body: JSON.stringify({ goal: v })
      });
      const data = await res.json();
      if (data.ok) {
        const msg = document.createElement('div');
        msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#16a34a;color:white;padding:12px 20px;border-radius:8px;font-weight:600;z-index:1000;animation:slideIn 0.3s ease-out;';
        msg.textContent = `✓ Goal saved: ${data.goal} kg`;
        document.body.appendChild(msg);
        setTimeout(() => msg.remove(), 3000);
      } else {
        alert('Failed to save goal');
      }
    });

    // Add animation styles dynamically
    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>
