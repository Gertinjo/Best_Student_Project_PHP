<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Recycling Tracker – Wide Gauge + Spacing + About</title>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
  :root{
    --bg:#0f172a; --panel:#f8fafc; --ink:#0b1020;
    --accent:#16a34a; --amber:#f59e0b; --red:#ef4444; --border:#e5e7eb;
  }
  *{box-sizing:border-box}
  body{
    margin:0; font-family:system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial;
    color:var(--ink); background:#fff; height:100vh;
    display:grid; grid-template-columns:260px 1fr;
  }
  /* Sidebar */
  .sidebar{background:var(--bg); color:#cbd5e1; padding:20px; display:flex; flex-direction:column; gap:16px}
  .brand{color:#fff; font-weight:800; font-size:20px}
  .nav a{display:block; padding:10px 12px; border-radius:10px; color:#cbd5e1; text-decoration:none}
  .nav a.active,.nav a:hover{ background:#111827; color:#fff }
  /* Main */
  .main{padding:24px; overflow:auto; background:#f1f5f9}
  .card{background:var(--panel); border:1px solid var(--border); border-radius:16px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,.04)}
  .top{display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:stretch; margin-bottom:24px}
  .row{display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:stretch; margin-bottom:32px}
  #map{height:540px; border-radius:14px}
  .actions{display:flex; flex-direction:column; gap:12px}
  .btn{display:inline-flex; align-items:center; gap:8px; padding:12px 14px; border:1px solid var(--border); border-radius:12px; background:#fff; cursor:pointer; font-weight:600}
  .btn.primary{background:var(--accent); color:#fff; border-color:var(--accent)}
  .small{font-size:12px; color:#475569}
  .f{display:grid; gap:10px}
  .f label{font-size:12px; color:#334155}
  .f input,.f select{width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:#fff}
  .section{margin-top:16px}
  .section h2{margin:0 0 10px 0; font-size:18px}
  .section p{margin:0; color:#334155}

  /* Gauge */
  .super-gauge{display:flex; align-items:center; justify-content:center}
  .g-wrap{max-width:100%; width:100%}
  .g-caption{display:flex; justify-content:center; gap:12px; margin-top:8px; font-weight:700}

  @media (max-width:1200px){
    body{grid-template-columns:72px 1fr}
    .brand{display:none}
    .top{grid-template-columns:1fr}
    .row{grid-template-columns:1fr}
  }

  /* Confetti overlay */
  #confettiCanvas{
    position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none;
    z-index:9999; display:none;
  }
</style>
</head>
<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand">♻️ RecycTrack</div>
    <nav class="nav">
      <a class="active" href="#">Dashboard</a>
      <a href="#">Requests</a>
      <a href="#">History</a>
      <a href="#">Settings</a>
    </nav>
    <div style="margin-top:auto;font-size:12px;opacity:.7">HTML/CSS/JS demo</div>
  </aside>

  <!-- MAIN -->
  <main class="main">
    <!-- TOP: Wide Gauge + Actions -->
    <div class="top">
      <!-- Wider half-circle gauge -->
      <!-- Wide Half-Circle Gauge -->
<div class="card">
  <div class="super-gauge g-wrap">
    <svg id="g-svg" viewBox="0 0 500 300" width="100%" height="240" aria-label="Progress gauge">
      <!-- Track -->
      <path id="g-track" d="M50,250 A200,200 0 0 1 450,250"
            fill="none" stroke="#e5e7eb" stroke-width="28" stroke-linecap="round"/>
      <!-- Progress -->
      <path id="g-progress" d="M50,250 A200,200 0 0 1 450,250"
            fill="none" stroke="#16a34a" stroke-width="28" stroke-linecap="round"
            stroke-dasharray="628" stroke-dashoffset="628"/>
      <!-- Ticks -->
      <g stroke="#cbd5e1" stroke-width="3">
        <line x1="110" y1="246" x2="110" y2="260"/>
        <line x1="250" y1="226" x2="250" y2="260"/>
        <line x1="390" y1="246" x2="390" y2="260"/>
      </g>
      <!-- Labels inside the arc -->
      <text x="250" y="140" text-anchor="middle" font-weight="700" font-size="22" fill="#111827">Progress</text>
      <text id="g-kg"  x="250" y="170" text-anchor="middle" font-weight="800" font-size="28" fill="#111827">0 / 20 kg</text>
      <text id="g-pct" x="250" y="195" text-anchor="middle" font-size="14" fill="#475569">0%</text>
    </svg>
  </div>
</div>

        <div class="g-caption">
          <span>Move/zoom the map and add waste markers. Reaching the target triggers a celebration 🎉</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="card actions">
        <button id="addBtn" class="btn primary">＋ Add Waste Marker</button>
        <button id="locateBtn" class="btn">📍 Use My Location</button>
        <div class="section">
          <h2>Today</h2>
          <p class="small">Markers added update progress. Drag markers to adjust positions.</p>
        </div>
      </div>
    </div>

    <!-- MAP + SIDE FORM (extra spacing via row gap + margin-bottom) -->
    <div class="row">
      <div id="map" class="card"></div>

      <div class="card">
        <div style="font-weight:700;margin-bottom:6px">Quick Add</div>
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
          <button class="btn primary" type="submit">Add Marker</button>
          <div class="small">Tip: the marker drops at the <b>map center</b>. Pan/zoom first.</div>
        </form>
      </div>
    </div>

    <!-- ABOUT US SECTION -->
    <section class="card section" id="about">
      <h2>About Us</h2>
      <p>
        RecycTrack helps communities recycle smarter. Citizens can log pickups and see their impact,
        drivers get optimized routes, and cities track real results—kilograms diverted from landfill
        and CO₂e avoided. This demo shows the core idea in a simple way. 🚀
      </p>
    </section>
  </main>

  <!-- Confetti overlay -->
  <canvas id="confettiCanvas"></canvas>

<script>
  // ======= Gauge logic (wide half-circle) =======
  const CIRC = 628; // π * r for r=200 semicircle

  function colorFor(p){
    if (p < 0.34) return '#ef4444';   // red
    if (p < 0.67) return '#f59e0b';   // amber
    return '#16a34a';                 // green
  }

  function animate(from, to, dur, cb){
    const t0 = performance.now();
    function tick(t){
      const k = Math.min(1, (t - t0)/dur);
      const e = k<.5 ? 4*k*k*k : 1 - Math.pow(-2*k+2,3)/2; // easeInOutCubic
      cb(from + (to - from)*e);
      if(k<1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function setProgress(kg, target){
    const pct = Math.max(0, Math.min(1, target ? kg/target : 0));
    const nextOffset = CIRC * (1 - pct);
    const path = document.getElementById('g-progress');
    const kgEl  = document.getElementById('g-kg');
    const pctEl = document.getElementById('g-pct');

    const current = parseFloat(path.style.strokeDashoffset || CIRC);
    animate(current, nextOffset, 700, v => { path.style.strokeDashoffset = v; });
    path.setAttribute('stroke', colorFor(pct));

    kgEl.textContent  = `${kg.toFixed(1)} / ${target} kg`;
    pctEl.textContent = `${Math.round(pct*100)}%`;

    if (kg >= target) { launchConfetti(); }
  }

  // ======= Demo state =======
  let currentKg = 0;
  const targetKg = 20;
  setProgress(currentKg, targetKg);

  // ======= Map setup =======
  const map = L.map('map', { zoomControl:true }).setView([42.6629, 21.1655], 12); // Prishtina
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  function addWasteMarker(material, kg){
    const center = map.getCenter();
    L.marker(center, {draggable:true}).addTo(map)
      .bindPopup(`<b>${material}</b><br/>~${kg} kg<br/><small>Drag to adjust</small>`)
      .openPopup();
    currentKg += kg;
    setProgress(currentKg, targetKg);
  }

  document.getElementById('addBtn').addEventListener('click', ()=> addWasteMarker('Plastic', 1.0));
  document.getElementById('locateBtn').addEventListener('click', ()=> map.locate({setView:true, maxZoom:16}));
  document.getElementById('quickForm').addEventListener('submit', (e)=>{
    e.preventDefault();
    const material = document.getElementById('material').value;
    const kgVal = parseFloat(document.getElementById('kgInput').value || '0');
    if(isNaN(kgVal) || kgVal <= 0){ return; }
    addWasteMarker(material, kgVal);
    e.target.reset();
  });

  // ======= Confetti =======
  const confettiCanvas = document.getElementById('confettiCanvas');
  const ctx = confettiCanvas.getContext('2d');
  let confetti = [], animId;

  function resizeCanvas(){
    confettiCanvas.width = window.innerWidth;
    confettiCanvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  function launchConfetti(){
    confetti = [];
    for(let i=0;i<220;i++){
      confetti.push({
        x: Math.random()*confettiCanvas.width,
        y: Math.random()*confettiCanvas.height - confettiCanvas.height,
        r: Math.random()*6 + 4,
        dx: Math.random()*2 - 1,
        dy: Math.random()*3 + 3,
        color: `hsl(${Math.random()*360},100%,50%)`
      });
    }
    confettiCanvas.style.display = 'block';
    cancelAnimationFrame(animId);
    animateConfetti();
    setTimeout(()=>{
      confettiCanvas.style.display = 'none';
      cancelAnimationFrame(animId);
    }, 4000);
  }

  function animateConfetti(){
    ctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height);
    confetti.forEach(p=>{
      ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=p.color; ctx.fill();
      p.x+=p.dx; p.y+=p.dy;
      if(p.y>confettiCanvas.height){ p.y=-10; p.x=Math.random()*confettiCanvas.width; }
    });
    animId = requestAnimationFrame(animateConfetti);
  }
</script>
</body>
</html>
