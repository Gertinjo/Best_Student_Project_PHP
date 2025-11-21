<?php
// admin.php

// Use your existing config (must start session, create $pdo, csrf_token(), csrf_check())
include_once '../database/config.php';

// require login
// require login
if (empty($_SESSION['user'])) {
  header('Location: ../Forms/login.php');
  exit;
}

// require admin
if ((int)($_SESSION['user']['is_admin'] ?? 0) !== 1) {
  http_response_code(403);
  echo "Access denied — Admins only.";
  exit;
}

// CSRF for this page
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>BinGo — Admin Dashboard</title>
<meta name="csrf" content="<?= htmlspecialchars($csrf, ENT_QUOTES); ?>">
<!-- your styles from before ... -->
</head>
<body>
<!-- your HTML dashboard content here (what you already had) -->

</body>
</html>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BinGo — Admin Dashboard</title>
  <meta name="csrf" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      --bg-primary: #0f1419;
      --bg-secondary: #1a1f2e;
      --bg-tertiary: #232d3f;
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --accent-green: #10b981;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
      --border-color: #2d3a4f;
      --radius-md: 12px;
      --radius-sm: 8px;
      --shadow-md: 0 12px 30px rgba(15, 80, 60, 0.18);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
    }

    .sidebar {
      width: 260px;
      background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
      border-right: 1px solid var(--border-color);
      padding: 28px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .brand {
      font-family: 'Poppins', sans-serif;
      font-size: 24px;
      font-weight: 700;
      background: linear-gradient(135deg, var(--accent-green), #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .nav {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .nav a {
      text-decoration: none;
      color: var(--text-secondary);
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      transition: all 0.2s ease;
      font-weight: 500;
      border-left: 2px solid transparent;
    }

    .nav a:hover {
      color: var(--accent-green);
      border-left-color: var(--accent-green);
      background: rgba(16, 185, 129, 0.08);
    }

    .nav a.active {
      color: var(--accent-green);
      border-left-color: var(--accent-green);
      background: rgba(16, 185, 129, 0.12);
      font-weight: 600;
    }

    .footnote {
      margin-top: auto;
      font-size: 12px;
      color: var(--text-secondary);
    }

    .main {
      flex: 1;
      padding: 32px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .page-title {
      font-family: 'Poppins', sans-serif;
      font-size: 28px;
      font-weight: 600;
      letter-spacing: -0.5px;
    }

    .cards-grid {
      display: grid;
      gap: 24px;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }

    .card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: var(--shadow-md);
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .card h2 {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .metrics {
      display: flex;
      gap: 18px;
    }

    .metric {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .metric .value {
      font-size: 26px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
    }

    .metric .label {
      font-size: 13px;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    thead {
      background: rgba(16, 185, 129, 0.08);
    }

    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-color);
      text-align: left;
    }

    th {
      font-size: 12px;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      color: var(--text-secondary);
    }

    tbody tr:hover {
      background: rgba(15, 23, 42, 0.35);
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.12);
      color: var(--accent-amber);
      border: 1px solid rgba(245, 158, 11, 0.4);
    }

    .status-repair {
      background: rgba(16, 185, 129, 0.12);
      color: var(--accent-green);
      border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .status-ignored {
      background: rgba(239, 68, 68, 0.12);
      color: var(--accent-red);
      border: 1px solid rgba(239, 68, 68, 0.35);
    }

    .actions {
      display: inline-flex;
      gap: 10px;
    }

    .btn {
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-color);
      background: transparent;
      color: var(--text-secondary);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn:hover {
      color: var(--accent-green);
      border-color: var(--accent-green);
    }

    .btn.primary {
      background: linear-gradient(135deg, var(--accent-green), #34d399);
      border: none;
      color: var(--bg-primary);
    }

    .btn.danger {
      border-color: var(--accent-red);
      color: var(--accent-red);
    }

    .btn.danger:hover {
      background: rgba(239, 68, 68, 0.1);
    }

    .empty-state {
      padding: 24px;
      text-align: center;
      color: var(--text-secondary);
    }

    .hidden {
      display: none !important;
    }

    .badge {
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.15);
      color: var(--text-secondary);
    }

    @media (max-width: 960px) {
      body {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        flex-direction: row;
        align-items: center;
        gap: 16px;
        padding: 20px;
      }
      .nav {
        flex-direction: row;
        gap: 8px;
      }
      .main {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ BinGo</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="settings.php">Settings</a>
      <a href="admin.php" class="active">Admin</a>
    </nav>
    <div class="footnote">Admin tools • Manage markers & teams</div>
  </aside>

  <main class="main">
    <div>
      <div class="page-title">Operations Control Center</div>
      <p style="color: var(--text-secondary); margin-top: 6px;">Monitor community reports, deploy repair teams, and review user activity.</p>
    </div>

    <section class="cards-grid">
      <div class="card">
        <h2>Live Queue</h2>
        <div class="metrics">
          <div class="metric">
            <span class="value" id="pendingCount">0</span>
            <span class="label">Pending Markers</span>
          </div>
          <div class="metric">
            <span class="value" id="pendingKg">0 kg</span>
            <span class="label">Pending Weight</span>
          </div>
          <div class="metric">
            <span class="value" id="userCount">0</span>
            <span class="label">Registered Users</span>
          </div>
        </div>
      </div>
      <div class="card">
        <h2>Quick Actions</h2>
        <p style="color: var(--text-secondary); line-height: 1.5;">Select a marker to either dispatch a repair crew or ignore low-priority reports. Repairing a marker removes it from the public map instantly.</p>
        <button id="refreshBtn" class="btn primary" style="align-self: flex-start;">⟳ Refresh Data</button>
      </div>
    </section>

    <section class="card">
      <div style="display:flex; align-items:center; justify-content: space-between;">
        <h2>Pending Markers</h2>
        <span class="badge" id="pendingBadge">0 active</span>
      </div>
      <div class="table-wrapper" style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Material</th>
              <th>Weight</th>
              <th>Location</</th>
              <th>Reported</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="pendingBody"></tbody>
        </table>
      </div>
      <div class="empty-state hidden" id="pendingEmpty">No pending markers 🎉</div>
    </section>

    <section class="card">
      <div style="display:flex; align-items:center; justify-content: space-between;">
        <h2>Resolved & Ignored</h2>
        <span class="badge" id="resolvedBadge">0 archived</span>
      </div>
      <div class="table-wrapper" style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Decision</th>
              <th>Team</th>
              <th>Resolved At</th>
            </tr>
          </thead>
          <tbody id="resolvedBody"></tbody>
        </table>
      </div>
      <div class="empty-state hidden" id="resolvedEmpty">No resolved markers yet.</div>
    </section>

    <section class="card">
      <div style="display:flex; align-items:center; justify-content: space-between;">
        <h2>Users</h2>
        <span class="badge" id="userBadge">0 members</span>
      </div>
      <div class="table-wrapper" style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody id="userBody"></tbody>
        </table>
      </div>
      <div class="empty-state hidden" id="userEmpty">No users found.</div>
    </section>
  </main>

  <script>
    const API = 'api.php';
    const CSRF = document.querySelector('meta[name="csrf"]').content;

    const pendingBody = document.getElementById('pendingBody');
    const resolvedBody = document.getElementById('resolvedBody');
    const userBody = document.getElementById('userBody');
    const pendingEmpty = document.getElementById('pendingEmpty');
    const resolvedEmpty = document.getElementById('resolvedEmpty');
    const userEmpty = document.getElementById('userEmpty');
    const pendingCountEl = document.getElementById('pendingCount');
    const pendingKgEl = document.getElementById('pendingKg');
    const userCountEl = document.getElementById('userCount');
    const pendingBadge = document.getElementById('pendingBadge');
    const resolvedBadge = document.getElementById('resolvedBadge');
    const userBadge = document.getElementById('userBadge');
    const refreshBtn = document.getElementById('refreshBtn');

    let state = {
      markers: [],
      users: [],
      pendingKg: 0,
      pendingCount: 0,
    };

    function formatDate(dateStr) {
      if (!dateStr) return '—';
      const d = new Date(dateStr.replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return dateStr;
      return d.toLocaleString();
    }

    function renderState() {
      const pending = state.markers.filter(m => (m.status || 'pending') === 'pending');
      const resolved = state.markers.filter(m => (m.status || 'pending') !== 'pending');

      pendingBody.innerHTML = pending.map(marker => {
        const material = marker.material || '—';
        const weight = marker.kg ? `${Number(marker.kg).toFixed(1)} kg` : '—';
        const location = marker.location || '—';
        const statusClass = `status-pill status-${marker.status || 'pending'}`;
        const created = formatDate(marker.createdAt);
        return `
          <tr data-marker="${marker.id}">
            <td>${marker.title ? marker.title.replace(/</g, '&lt;') : 'Untitled'}</td>
            <td>${material}</td>
            <td>${weight}</td>
            <td>${location}</td>
            <td>${created}</td>
            <td><span class="${statusClass}">${(marker.status || 'pending').toUpperCase()}</span></td>
            <td>
              <div class="actions">
                <button class="btn primary" data-action="resolve" data-id="${marker.id}">Resolve</button>
                <button class="btn danger" data-action="ignore" data-id="${marker.id}">Ignore</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');

      resolvedBody.innerHTML = resolved.map(marker => {
        const statusClass = `status-pill status-${marker.status}`;
        const team = marker.teamSize ? `${marker.teamSize} people` : '—';
        return `
          <tr>
            <td>${marker.title ? marker.title.replace(/</g, '&lt;') : 'Untitled'}</td>
            <td><span class="${statusClass}">${marker.status.toUpperCase()}</span></td>
            <td>${team}</td>
            <td>${formatDate(marker.resolvedAt)}</td>
          </tr>
        `;
      }).join('');

      userBody.innerHTML = state.users.map(user => `
        <tr>
          <td>${user.name ? user.name.replace(/</g, '&lt;') : '—'}</td>
          <td>${user.email}</td>
          <td>${formatDate(user.createdAt)}</td>
        </tr>
      `).join('');

      pendingEmpty.classList.toggle('hidden', pending.length !== 0);
      resolvedEmpty.classList.toggle('hidden', resolved.length !== 0);
      userEmpty.classList.toggle('hidden', state.users.length !== 0);

      pendingCountEl.textContent = pending.length;
      pendingKgEl.textContent = `${Number(state.pendingKg || 0).toFixed(1)} kg`;
      userCountEl.textContent = state.users.length;
      pendingBadge.textContent = `${pending.length} active`;
      resolvedBadge.textContent = `${resolved.length} archived`;
      userBadge.textContent = `${state.users.length} members`;
    }

    async function loadState() {
      refreshBtn.disabled = true;
      try {
        const res = await fetch(`${API}?action=admin_state`, {
          headers: { 'X-CSRF': CSRF }
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
          throw new Error(data.message || data.error || 'Failed to load admin state');
        }
        state = {
          markers: data.markers || [],
          users: data.users || [],
          pendingKg: data.pendingKg || 0,
          pendingCount: data.pendingCount || 0,
        };
        renderState();
      } catch (err) {
        console.error(err);
        alert(err.message || 'Unable to load admin data');
      } finally {
        refreshBtn.disabled = false;
      }
    }

    async function sendDecision(markerId, decision, teamSize = 0) {
      if (!Number.isInteger(markerId) || markerId <= 0) {
        alert('Invalid marker. Please refresh and try again.');
        return;
      }
      try {
        const res = await fetch(`${API}?action=update_marker`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF': CSRF
          },
          body: JSON.stringify({ id: markerId, decision, teamSize })
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
          throw new Error(data.message || data.error || 'Failed to update marker');
        }
        await loadState();
      } catch (err) {
        console.error(err);
        alert(err.message || 'Unable to update marker.');
      }
    }

    function resolveMarkerIdFromElement(el) {
      if (!el) return NaN;
      const row = el.closest('tr');
      if (el.dataset && el.dataset.id) {
        return Number.parseInt(el.dataset.id, 10);
      }
      if (row && row.dataset && row.dataset.marker) {
        return Number.parseInt(row.dataset.marker, 10);
      }
      return NaN;
    }

    pendingBody.addEventListener('click', (event) => {
      const btn = event.target.closest('button[data-action]');
      if (!btn) return;
      const markerId = resolveMarkerIdFromElement(btn);
      const action = btn.dataset.action;
      if (!markerId) return;

      if (action === 'ignore') {
        if (confirm('Ignore this marker? It will be removed from the public map.')) {
          sendDecision(markerId, 'ignore');
        }
      } else if (action === 'resolve') {
        sendDecision(markerId, 'repair');
      }
    });

    refreshBtn.addEventListener('click', loadState);

    loadState();
  </script>
</body>
</html>
