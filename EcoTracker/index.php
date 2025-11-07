<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];

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
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BinGo — Dashboard</title>
  <meta name="csrf" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <!-- Google Fonts for premium typography -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- App styles -->
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      /* <CHANGE> Modern dark theme with emerald accents for eco branding */
      --bg-primary: #0f1419;
      --bg-secondary: #1a1f2e;
      --bg-tertiary: #232d3f;
      --text-primary: #f0f4f8;
      --text-secondary: #a8b5c7;
      --border-color: #2d3a4f;
      --accent-green: #10b981;
      --accent-light-green: #6ee7b7;
      --accent-red: #ef4444;
      --accent-amber: #f59e0b;
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
      --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* <CHANGE> Refined sidebar with modern styling */
    .sidebar {
      width: 280px;
      background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
      border-right: 1px solid var(--border-color);
      padding: 24px;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      z-index: 50;
      display: flex;
      flex-direction: column;
    }

    .brand {
      font-family: 'Poppins', sans-serif;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 32px;
      background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-light-green) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: flex;
      align-items: center;
      gap: 8px;
      letter-spacing: -0.5px;
    }

    .nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex: 1;
    }

    .nav a {
      padding: 12px 16px;
      color: var(--text-secondary);
      text-decoration: none;
      border-radius: var(--radius-md);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 500;
      font-size: 14px;
      border-left: 2px solid transparent;
      margin-left: -2px;
    }

    .nav a:hover {
      background: rgba(16, 185, 129, 0.1);
      color: var(--accent-green);
      border-left-color: var(--accent-green);
    }

    .nav a.active {
      background: rgba(16, 185, 129, 0.15);
      color: var(--accent-green);
      border-left-color: var(--accent-green);
      font-weight: 600;
    }

    .footnote {
      font-size: 12px;
      color: var(--text-secondary);
      margin-top: auto;
      padding-top: 16px;
      border-top: 1px solid var(--border-color);
    }

    /* <CHANGE> Main content with adjusted margin for sidebar */
    .main {
      margin-left: 280px;
      padding: 32px;
      min-height: 100vh;
    }

    .top {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 32px;
    }

    /* <CHANGE> Premium card styling with glass morphism effect */
    .card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 28px;
      box-shadow: var(--shadow-md);
      backdrop-filter: blur(10px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card:hover {
      border-color: rgba(16, 185, 129, 0.3);
      box-shadow: 0 12px 32px rgba(16, 185, 129, 0.15);
      transform: translateY(-2px);
    }

    .progress-card {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .progress-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 20px;
    }

    .progress-stats {
      text-align: right;
    }

    #progressKg {
      display: block;
      font-family: 'Poppins', sans-serif;
      font-size: 26px;
      font-weight: 600;
      color: var(--accent-green);
      letter-spacing: -0.3px;
    }

    #progressPct {
      display: block;
      font-size: 14px;
      color: var(--text-secondary);
    }

    .progress-bar {
      position: relative;
      width: 100%;
      height: 14px;
      background: var(--bg-tertiary);
      border: 1px solid var(--border-color);
      border-radius: 999px;
      overflow: hidden;
      box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.4);
    }

    #progressFill {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 0;
      background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-light-green) 100%);
      border-radius: inherit;
      box-shadow: 0 0 18px rgba(16, 185, 129, 0.35);
      transition: width 0.5s ease;
    }

    .card.actions {
      display: flex;
      flex-direction: column;
      gap: 16px;
      justify-content: space-between;
    }

    .progress-footnote {
      font-size: 12px;
      color: var(--text-secondary);
    }

    .title {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 20px;
      color: var(--text-primary);
      letter-spacing: -0.3px;
    }

    /* <CHANGE> Button system with modern hover states */
    .btn {
      padding: 11px 18px;
      border: 1px solid var(--border-color);
      background: transparent;
      color: var(--text-secondary);
      border-radius: var(--radius-md);
      cursor: pointer;
      font-weight: 500;
      font-size: 14px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-family: 'Inter', sans-serif;
    }

    .btn:hover {
      border-color: var(--accent-green);
      color: var(--accent-green);
      background: rgba(16, 185, 129, 0.08);
      transform: translateY(-1px);
    }

    .btn.primary {
      background: linear-gradient(135deg, var(--accent-green) 0%, var(--accent-light-green) 100%);
      color: var(--bg-primary);
      border: none;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn.primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    }

    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none !important;
    }

    .pill {
      display: inline-block;
      background: rgba(16, 185, 129, 0.1);
      color: var(--accent-green);
      padding: 8px 12px;
      border-radius: var(--radius-md);
      font-size: 12px;
      font-weight: 600;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .pill.small {
      padding: 6px 10px;
      font-size: 11px;
    }

    .row {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
    }

    #map {
      height: 400px;
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    .f {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .f > div {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    select, input, textarea {
      padding: 10px 12px;
      background: var(--bg-tertiary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      color: var(--text-primary);
      font-family: inherit;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    textarea {
      min-height: 96px;
      resize: vertical;
    }

    select:focus, input:focus, textarea:focus {
      outline: none;
      border-color: var(--accent-green);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
      background: var(--bg-tertiary);
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border-color);
      margin: 16px 0;
    }

    .small {
      font-size: 12px;
      color: var(--text-secondary);
    }

    .muted {
      color: var(--text-secondary);
    }

    /* <CHANGE> Modal styling with modern overlay */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
      backdrop-filter: blur(4px);
    }

    .modal-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 32px;
      max-width: 420px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 20px;
    }

    .modal-title {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .modal-close {
      background: transparent;
      border: none;
      color: var(--text-secondary);
      font-size: 20px;
      line-height: 1;
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .modal-close:hover {
      color: var(--accent-green);
    }

    #markerForm {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-grid {
      display: grid;
      gap: 18px;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }

    .form-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
      background: rgba(35, 45, 63, 0.6);
      border: 1px solid rgba(45, 58, 79, 0.8);
      border-radius: var(--radius-md);
      padding: 16px;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .form-field label {
      font-size: 12px;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      color: var(--text-secondary);
      font-weight: 600;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
      margin-top: 2px;
    }

    .span-2 {
      grid-column: 1 / -1;
    }

    .file-note {
      font-size: 11px;
      color: var(--text-secondary);
    }

    .file-input {
      position: relative;
    }

    .file-input input[type="file"] {
      padding: 12px;
      border-style: dashed;
      cursor: pointer;
    }

    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 4px;
    }

    .form-actions button {
      flex: 1;
    }

    .emoji-marker {
      font-size: 28px !important;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    #confettiCanvas {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 200;
      display: none;
    }

    .leaflet-popup-content .popup-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 16px;
      margin-bottom: 4px;
      color: var(--text-primary);
    }

    .leaflet-popup-content .popup-kg {
      font-size: 13px;
      margin-bottom: 8px;
      color: var(--accent-green);
      font-weight: 600;
    }

    .leaflet-popup-content .popup-desc,
    .leaflet-popup-content .popup-location {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 6px;
    }

    .leaflet-popup-content .popup-photo {
      display: block;
      max-width: 200px;
      width: 100%;
      border-radius: var(--radius-md);
      margin-top: 10px;
      box-shadow: var(--shadow-sm);
    }

    /* Leaflet customization */
    .leaflet-control-attribution {
      background: var(--bg-secondary) !important;
      color: var(--text-secondary) !important;
      border: 1px solid var(--border-color) !important;
    }

    .leaflet-control-zoom {
      border: 1px solid var(--border-color) !important;
      border-radius: var(--radius-md) !important;
    }

    .leaflet-control-zoom a {
      background: var(--bg-secondary) !important;
      color: var(--accent-green) !important;
      border-bottom: 1px solid var(--border-color) !important;
    }

    .leaflet-control-zoom a:hover {
      background: rgba(16, 185, 129, 0.1) !important;
    }

    .leaflet-popup-content-wrapper {
      background: var(--bg-secondary) !important;
      color: var(--text-primary) !important;
      border: 1px solid var(--border-color) !important;
      border-radius: var(--radius-md) !important;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
    }

    .leaflet-popup-tip {
      background: var(--bg-secondary) !important;
      border-color: var(--border-color) !important;
    }

    .leaflet-popup-close-button {
      color: var(--text-secondary) !important;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
      .sidebar {
        width: 240px;
        padding: 20px;
      }

      .main {
        margin-left: 240px;
        padding: 24px;
      }

      .top {
        grid-template-columns: 1fr;
      }

      .row {
        grid-template-columns: 1fr;
      }

      #map {
        height: 300px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: static;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
      }

      .brand {
        margin-bottom: 0;
      }

      .nav {
        flex-direction: row;
        gap: 4px;
      }

      .footnote {
        display: none;
      }

      .main {
        margin-left: 0;
        padding: 16px;
      }

      .top, .row {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      #map {
        height: 250px;
      }

      .card {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ BinGo</div>
    <nav class="nav">
      <a href="index.php" class="active">Dashboard</a>
      <a href="settings.php">Settings</a>
      <?php if ($isAdmin): ?>
      <a href="admin.php">Admin</a>
      <?php endif; ?>
    </nav>
    <div class="footnote">PHP session demo • Max 100 kg</div>
  </aside>

  <!-- <CHANGE> Redesigned modal with modern styling -->
  <div id="markerModal" class="modal-overlay" style="display:none;">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">Add Waste Marker</div>
        <button type="button" id="closeMarker" class="modal-close" aria-label="Close">×</button>
      </div>
      <form id="markerForm">
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="mTitle">Title</label>
            <input id="mTitle" type="text" required placeholder="e.g. Overflowing bin near park" />
          </div>

          <div class="form-field span-2">
            <label for="mDescription">Description</label>
            <textarea id="mDescription" placeholder="Explain what you found and any helpful details" required></textarea>
          </div>

          <div class="form-field span-2 file-input">
            <label for="mPhoto">Photo</label>
            <input id="mPhoto" type="file" accept="image/*" required />
            <div class="file-note">Upload a clear JPG or PNG under 5MB.</div>
          </div>

          <div class="form-field">
            <label for="mMaterial">Material</label>
            <select id="mMaterial" required>
              <option value="Plastic">Plastic</option>
              <option value="Paper">Paper</option>
              <option value="Glass">Glass</option>
              <option value="Metal">Metal</option>
              <option value="E-waste">E-waste</option>
            </select>
          </div>

          <div class="form-field">
            <label for="mWeight">Estimated Weight (kg)</label>
            <input id="mWeight" type="number" min="0.1" step="0.1" required placeholder="e.g. 1.5">
          </div>

          <div class="form-field span-2">
            <label for="mLocation">Location Name (optional)</label>
            <input id="mLocation" type="text" placeholder="e.g. City Park Bin">
          </div>
        </div>

        <div class="form-actions">
          <button type="button" id="cancelMarker" class="btn">Cancel</button>
          <button type="submit" class="btn primary">Add Marker</button>
        </div>
      </form>
    </div>
  </div>

  <main class="main">
    <div class="top">
      <div class="card progress-card">
        <div class="progress-header">
          <div>
            <div class="title">Collection Progress</div>
            <div class="progress-footnote">Goal is capped at <b>100 kg</b>. "Like" markers don't affect kg.</div>
          </div>
          <div class="progress-stats">
            <span id="progressKg">—</span>
            <span id="progressPct">—</span>
          </div>
        </div>
        <div class="progress-bar">
          <span id="progressFill" data-progress="0"></span>
        </div>
      </div>

      <div class="card actions">
        <button id="addBtn" class="btn primary">＋ Add Waste Marker</button>
        <button id="likeBtn" class="btn">❤️ Add Like Marker</button>
        <button id="locateBtn" class="btn">📍 Use My Location</button>
        <div style="margin-top: auto;">
          <div class="pill small">Goal: <strong id="goalPill">—</strong> kg (max 100)</div>
        </div>
      </div>
    </div>

    <div class="row">
      <div id="map" class="card"></div>
      <div class="card">
        <div class="title">Quick Add</div>
        <form id="quickForm" class="f">
          <div>
            <label for="material">Material</label>
            <select id="material" required>
              <option value="Plastic">Plastic</option>
              <option value="Paper">Paper</option>
              <option value="Metal">Metal</option>
              <option value="Glass">Glass</option>
              <option value="E-waste">E-waste</option>
            </select>
          </div>
          <div>
            <label for="kgInput">Estimated weight (kg)</label>
            <input id="kgInput" type="number" min="0" step="0.1" placeholder="e.g. 1.5" required />
          </div>
          <button id="quickAddBtn" class="btn primary" type="submit">Add Marker</button>
          <div class="small">Marker is added at the map center.</div>
        </form>

        <hr class="divider">
        <div class="f">
          <label>Add a "Like" marker (no kg)</label>
          <button id="likeBtn2" class="btn">❤️ Drop Like at center</button>
        </div>
      </div>
    </div>
  </main>

  <canvas id="confettiCanvas"></canvas>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

  <!-- ... existing code ... -->
  <script>
  const API='api.php'; let currentKg=0, goal=20, map;
  const CSRF = document.querySelector('meta[name="csrf"]').content;
  const progressFill=document.getElementById('progressFill');
  const progressKgEl=document.getElementById('progressKg');
  const progressPctEl=document.getElementById('progressPct');
  const goalPill=document.getElementById('goalPill');
  const addBtn=document.getElementById('addBtn');
  const quickAddBtn=document.getElementById('quickAddBtn');
  const markerModal=document.getElementById('markerModal');
  const markerForm=document.getElementById('markerForm');
  const cancelMarkerBtn=document.getElementById('cancelMarker');
  const closeMarkerBtn=document.getElementById('closeMarker');
  const mPhotoInput=document.getElementById('mPhoto');
  let addWasteFromCenter=null;
  let renderMarkerOnMap=null;

  function colorFor(p){ return p<0.34?'#ef4444': p<0.67?'#f59e0b':'#10b981'; }
  function gradientFor(p){ const base=colorFor(p); return `linear-gradient(135deg, ${base} 0%, var(--accent-light-green) 100%)`; }
  function animate(from,to,dur,cb){ const t0=performance.now(); function tick(t){ const k=Math.min(1,(t-t0)/dur); const e=k<.5?4*k*k*k:1-Math.pow(-2*k+2,3)/2; cb(from+(to-from)*e); if(k<1) requestAnimationFrame(tick);} requestAnimationFrame(tick); }
  function setProgress(kg,target){
    const eff = Math.min(target, 100);
    const shown = Math.min(kg, eff);
    const pct = eff ? Math.min(1, shown/eff) : 0;
    const currentPct = progressFill && !Number.isNaN(parseFloat(progressFill.dataset.progress)) ? parseFloat(progressFill.dataset.progress) : 0;
    if (progressFill) {
      animate(currentPct, pct, 700, value => {
        const safe = Math.max(0, Math.min(1, value));
        progressFill.style.width = `${(safe*100).toFixed(1)}%`;
        progressFill.style.background = gradientFor(safe);
        progressFill.style.boxShadow = `0 0 18px ${colorFor(safe)}55`;
        progressFill.dataset.progress = safe;
      });
    }
    if (progressKgEl) progressKgEl.textContent = `${shown.toFixed(1)} / ${eff} kg`;
    if (progressPctEl) progressPctEl.textContent = `${Math.round(pct*100)}%`;
    const reached = shown >= eff;
    if (addBtn) addBtn.disabled = reached;
    if (quickAddBtn) quickAddBtn.disabled = reached;
    if (reached) launchConfetti();
  }

  const confettiCanvas=document.getElementById('confettiCanvas');
  const ctx=confettiCanvas.getContext('2d'); let confetti=[], animId;
  function resizeCanvas(){ confettiCanvas.width=innerWidth; confettiCanvas.height=innerHeight; }
  addEventListener('resize',resizeCanvas); resizeCanvas();
  function launchConfetti(){
    confetti=[]; for(let i=0;i<220;i++){ confetti.push({x:Math.random()*confettiCanvas.width,y:Math.random()*confettiCanvas.height-confettiCanvas.height,r:Math.random()*6+4,dx:Math.random()*2-1,dy:Math.random()*3+3,color:`hsl(${Math.random()*360},100%,50%)`}); }
    confettiCanvas.style.display='block'; cancelAnimationFrame(animId); (function loop(){ ctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height); confetti.forEach(p=>{ ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fillStyle=p.color; ctx.fill(); p.x+=p.dx; p.y+=p.dy; if(p.y>confettiCanvas.height){ p.y=-10; p.x=Math.random()*confettiCanvas.width; } }); animId=requestAnimationFrame(loop); })();
    setTimeout(()=>{ confettiCanvas.style.display='none'; cancelAnimationFrame(animId); }, 4000);
  }

  const ESCAPE_LOOKUP = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' };
  function escapeHtml(str){
    return str ? str.replace(/[&<>"']/g, c=>ESCAPE_LOOKUP[c] || c) : '';
  }

  function buildMarkerPopup(marker){
    if (!marker) return '';
    const type = marker.type || 'waste';
    const parts=[];
    const title = marker.title || (type === 'like' ? 'Liked spot' : marker.material);
    if (title) parts.push(`<div class="popup-title">${escapeHtml(title)}</div>`);
    if (type === 'waste' && marker.kg !== undefined && marker.kg !== null && !Number.isNaN(Number(marker.kg))) {
      parts.push(`<div class="popup-kg">~${Number(marker.kg).toFixed(1)} kg</div>`);
    }
    const description = marker.description || (type === 'like' ? 'Someone appreciated this location.' : '');
    if (description) parts.push(`<div class="popup-desc">${escapeHtml(description)}</div>`);
    if (marker.location) parts.push(`<div class="popup-location">${escapeHtml(marker.location)}</div>`);
    const photoSrc = marker.photoUrl || marker.photo;
    if (type === 'waste' && photoSrc) {
      const safeSrc = photoSrc.startsWith('data:') ? photoSrc : encodeURI(photoSrc);
      parts.push(`<img class="popup-photo" src="${safeSrc}" alt="Marker photo"/>`);
    }
    return parts.join('');
  }

  function openMarkerModal(){
    if (!markerModal) return;
    markerModal.style.display='flex';
    document.body.style.overflow='hidden';
    const titleInput = document.getElementById('mTitle');
    if (titleInput) setTimeout(()=>titleInput.focus(), 10);
  }

  function closeMarkerModal(){
    if (!markerModal) return;
    markerModal.style.display='none';
    document.body.style.overflow='';
    if (markerForm) markerForm.reset();
    if (mPhotoInput) mPhotoInput.value='';
  }

  async function fileToBase64(file){
    return new Promise((resolve,reject)=>{
      const reader=new FileReader();
      reader.onload=()=>resolve(reader.result);
      reader.onerror=()=>reject(new Error('Unable to read file.'));
      reader.readAsDataURL(file);
    });
  }

  if (addBtn) addBtn.addEventListener('click', openMarkerModal);
  if (cancelMarkerBtn) cancelMarkerBtn.addEventListener('click', closeMarkerModal);
  if (closeMarkerBtn) closeMarkerBtn.addEventListener('click', closeMarkerModal);
  if (markerModal) markerModal.addEventListener('click', e=>{ if(e.target===markerModal) closeMarkerModal(); });
  document.addEventListener('keydown', e=>{
    if (e.key === 'Escape' && markerModal && markerModal.style.display !== 'none') {
      closeMarkerModal();
    }
  });

  if (markerForm) {
    markerForm.addEventListener('submit', async e=>{
      e.preventDefault();
      if (!addWasteFromCenter) { alert('Map is still loading. Please try again in a moment.'); return; }
      const title = document.getElementById('mTitle').value.trim();
      const description = document.getElementById('mDescription').value.trim();
      const material = document.getElementById('mMaterial').value;
      const weightRaw = document.getElementById('mWeight').value;
      const locationName = document.getElementById('mLocation').value.trim();
      const file = mPhotoInput && mPhotoInput.files ? mPhotoInput.files[0] : null;
      const submitBtn = markerForm.querySelector('button[type="submit"]');
      const MAX_PHOTO_SIZE = 5 * 1024 * 1024;
      if (file && file.size > MAX_PHOTO_SIZE) {
        alert('Please choose a photo smaller than 5MB.');
        return;
      }
      if (submitBtn) submitBtn.disabled = true;
      try {
        const photoData = file ? await fileToBase64(file) : null;
        await addWasteFromCenter({
          material,
          kg: parseFloat(weightRaw),
          title,
          description,
          location: locationName || undefined,
          photo: photoData || undefined,
          photoName: file ? file.name : undefined,
          photoType: file ? file.type : undefined
        });
        closeMarkerModal();
      } catch (err) {
        console.error(err);
        alert(err.message || 'Failed to add marker.');
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  function initMap(markers){
    map=L.map('map',{zoomControl:true}).setView([42.6629,21.1655],12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19, attribution:'&copy; OpenStreetMap'}).addTo(map);
    const likeIcon=L.divIcon({className:'emoji-marker', html:'❤️', iconSize:[32,32], iconAnchor:[16,16]});

    renderMarkerOnMap = (marker, openPopup=false) => {
      if (!marker || typeof marker.lat !== 'number' || typeof marker.lng !== 'number') return null;
      const coords=[marker.lat, marker.lng];
      const popupHtml = buildMarkerPopup(marker);
      if ((marker.type || 'waste') === 'like') {
        const instance=L.marker(coords,{icon:likeIcon}).addTo(map).bindPopup(popupHtml || '<b>Liked spot</b>');
        if (openPopup) instance.openPopup();
        return instance;
      }
      const instance=L.marker(coords).addTo(map).bindPopup(popupHtml || '<b>Waste marker</b>');
      if (openPopup) instance.openPopup();
      return instance;
    };

    (markers||[]).forEach(m=>{ try { renderMarkerOnMap(m); } catch(err) { console.error('Failed to render marker', err); } });
    const locateBtn=document.getElementById('locateBtn');
    if (locateBtn) locateBtn.onclick=()=>map.locate({setView:true,maxZoom:16});
    const likeBtn=document.getElementById('likeBtn');
    const likeBtn2=document.getElementById('likeBtn2');
    const quickForm=document.getElementById('quickForm');
    const quickMaterial=document.getElementById('material');
    const quickKg=document.getElementById('kgInput');
    if (likeBtn) likeBtn.onclick=()=>addLike();
    if (likeBtn2) likeBtn2.onclick=()=>addLike();
    if (quickForm) quickForm.addEventListener('submit',async e=>{
      e.preventDefault();
      if (!addWasteFromCenter) return;
      const mVal=quickMaterial ? quickMaterial.value : 'Plastic';
      const kVal=quickKg ? parseFloat(quickKg.value||'0') : 0;
      if(kVal>0){
        try {
          await addWasteFromCenter({ material:mVal, kg:kVal });
        } catch (err) {
          console.error(err);
          alert(err.message || 'Failed to add marker.');
        }
      }
      e.target.reset();
    });
    addWasteFromCenter = async function(payload){
      if (!map) throw new Error('Map is not ready.');
      const c=map.getCenter();
      const body={ type:'waste', lat:c.lat, lng:c.lng };
      if (payload.material) body.material = payload.material;
      if (payload.kg !== undefined && payload.kg !== null && !Number.isNaN(Number(payload.kg))) body.kg = Number(payload.kg);
      if (payload.title) body.title = payload.title;
      if (payload.description) body.description = payload.description;
      if (payload.location) body.location = payload.location;
      if (payload.photo) body.photo = payload.photo;
      if (payload.photoName) body.photoName = payload.photoName;
      if (payload.photoType) body.photoType = payload.photoType;
      if (!body.title) body.title = `${body.material || 'Waste'} marker`;
      if (!body.description) body.description = 'Quick add marker created from map center.';
      const res=await fetch(`${API}?action=add_marker`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':CSRF},body:JSON.stringify(body)});
      const data=await res.json();
      if(!res.ok || !data.ok) {
        const message = data.message || data.error || res.statusText || 'Unable to add marker.';
        throw new Error(message);
      }
      currentKg=data.currentKg; setProgress(currentKg, data.goal);
      const markerInfo = data.marker || {
        type: 'waste',
        material: body.material,
        kg: data.addedKg !== undefined ? data.addedKg : body.kg,
        title: body.title,
        description: body.description,
        location: body.location,
        photo: body.photo,
        lat: c.lat,
        lng: c.lng
      };
      if (renderMarkerOnMap) {
        renderMarkerOnMap(markerInfo, true);
      }
      return data;
    };
    async function addLike(){
      const c=map.getCenter();
      const res=await fetch(`${API}?action=add_marker`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':CSRF},body:JSON.stringify({type:'like',lat:c.lat,lng:c.lng})});
      const data=await res.json();
      if(!res.ok || !data.ok) {
        const message = data.message || data.error || res.statusText || 'Unable to add like marker.';
        alert(message);
        return;
      }
      const markerInfo = data.marker || { type:'like', title:'Liked spot', description:'Someone liked this location.', lat:c.lat, lng:c.lng };
      if (renderMarkerOnMap) {
        renderMarkerOnMap(markerInfo, true);
      }
    }
  }

  (async function(){
    const s=await fetch(`${API}?action=get_state`); const data=await s.json();
    if(!data.ok) return;
    currentKg=data.currentKg; goal=data.goal; goalPill.textContent=goal;
    setProgress(currentKg, goal); initMap(data.markers||[]);
  })();
  </script>
</body>
</html>