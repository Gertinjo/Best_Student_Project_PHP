<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>BinGo — Dashboard & Settings</title>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
  :root{
    /* Light theme */
    --bg:#f1f5f9; --panel:#ffffff; --ink:#0b1020;
    --muted:#475569; --muted-2:#334155;
    --accent:#16a34a; --amber:#f59e0b; --red:#ef4444; --border:#e5e7eb;
    --sidebar:#0f172a; --sidebar-ink:#cbd5e1; --sidebar-hover:#111827;
    --shadow:0 1px 2px rgba(0,0,0,.06);
  }
  [data-theme="dark"]{
    /* Dark theme */
    --bg:#0b1220; --panel:#0f172a; --ink:#e5e7eb;
    --muted:#a7b0c0; --muted-2:#cbd5e1;
    --accent:#22c55e; --amber:#fbbf24; --red:#f87171; --border:#1f2937;
    --sidebar:#0a0f1a; --sidebar-ink:#94a3b8; --sidebar-hover:#111827;
    --shadow:0 1px 2px rgba(0,0,0,.35);
  }

  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0; font-family:system-ui,-apple-system,Segoe UI,Inter,Roboto,Arial;
    color:var(--ink); background:var(--bg);
    display:grid; grid-template-columns:260px 1fr; min-height:100vh;
  }

  /* Sidebar */
  .sidebar{
    background:var(--sidebar); color:var(--sidebar-ink);
    padding:20px; display:flex; flex-direction:column; gap:16px;
  }
  .brand{color:#fff; font-weight:800; font-size:20px}
  .nav a{
    display:block; padding:10px 12px; border-radius:10px; color:var(--sidebar-ink);
    text-decoration:none; transition:.15s background;
  }
  .nav a.active,.nav a:hover{ background:var(--sidebar-hover); color:#fff }

  /* Main */
  .main{padding:24px; overflow:auto}
  .card{
    background:var(--panel); border:1px solid var(--border);
    border-radius:16px; padding:16px; box-shadow:var(--shadow);
  }
  .top{display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:stretch; margin-bottom:24px}
  .row{display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:stretch; margin-bottom:32px}
  #map{height:540px; border-radius:14px}
  .actions{display:flex; flex-direction:column; gap:12px}
  .btn{display:inline-flex; align-items:center; gap:8px; padding:12px 14px;
       border:1px solid var(--border); border-radius:12px; background:transparent; cursor:pointer; font-weight:600; color:var(--ink)}
  .btn.primary{background:var(--accent); color:#fff; border-color:var(--accent)}
  .small{font-size:12px; color:var(--muted)}
  .muted{color:var(--muted)}
  .f{display:grid; gap:12px}
  .f label{font-size:12px; color:var(--muted-2)}
  .f input,.f select{
    width:100%; padding:10px 12px; border:1px solid var(--border);
    border-radius:10px; background:transparent; color:var(--ink)
  }
  .f .row2{display:grid; grid-template-columns:1fr auto; gap:10px}
  .section{margin-top:16px}
  .section h2{margin:0 0 10px 0; font-size:18px}
  .section p{margin:0; color:var(--muted-2)}
  .pill{display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:999px}

  /* Gauge */
  .super-gauge{display:flex; align-items:center; justify-content:center}
  .g-wrap{max-width:100%; width:100%}
  .g-caption{display:flex; justify-content:center; gap:12px; margin-top:8px; font-weight:700}

  /* Page routing (SPA) */
  [data-page]{display:none}
  [data-page].active{display:block}

  /* Confetti overlay */
  #confettiCanvas{
    position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none;
    z-index:9999; display:none;
  }

  @media (max-width:1200px){
    body{grid-template-columns:72px 1fr}
    .brand{display:none}
    .top{grid-template-columns:1fr}
    .row{grid-template-columns:1fr}
  }

  /* Toggle switch */
  .switch{position:relative; width:52px; height:30px}
  .switch input{display:none}
  .slider{
    position:absolute; inset:0; background:#94a3b8; border-radius:999px; transition:.2s;
  }
  .slider::before{
    content:""; position:absolute; width:26px; height:26px; left:2px; top:2px; border-radius:50%;
    background:#fff; transition:.2s;
  }
  .switch input:checked + .slider{ background:#22c55e }
  .switch input:checked + .slider::before{ transform:translateX(22px) }
</style>
</head>
<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand">♻️ BinGo</div>
    <nav class="nav">
      <a href="#dashboard" data-link class="active">Dashboard</a>
      <a href="#settings" data-link>Settings</a>
      <a href="#about" data-link>About</a>
    </nav>
    <div style="margin-top:auto;font-size:12px;opacity:.7">HTML/CSS/JS demo</div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- DASHBOARD -->
    <section id="page-dashboard" data-page class="active">
      <div class="top">
        <!-- Bottom-aligned wide semicircle gauge -->
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
              <text x="250" y="140" text-anchor="middle" font-weight="700" font-size="22">Progress</text>
              <text id="g-kg"  x="250" y="170" text-anchor="middle" font-weight="800" font-size="28">0 / 20 kg</text>
              <text id="g-pct" x="250" y="195" text-anchor="middle" font-size="14">0%</text>
            </svg>
          </div>
          <div class="g-caption muted">
            Add markers to increase your progress. Reaching your goal triggers a celebration 🎉
          </div>
        </div>

        <!-- Quick actions -->
        <div class="card actions">
          <button id="addBtn" class="btn primary">＋ Add Waste Marker</button>
          <button id="locateBtn" class="btn">📍 Use My Location</button>
          <div class="section">
            <div class="pill small">Goal: <strong id="goalPill">20</strong> kg</div>
          </div>
          <div class="section">
            <h2>Today</h2>
            <p class="small">Drag markers to adjust their exact positions.</p>
          </div>
        </div>
      </div>

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
            <div class="small">Tip: marker drops at the <b>map center</b>. Pan/zoom first.</div>
          </form>
        </div>
      </div>
    </section>

    <!-- SETTINGS -->
    <section id="page-settings" data-page>
      <div class="card" style="max-width:860px;margin:0 auto">
        <h2 style="margin:0 0 12px 0">Settings</h2>
        <p class="small" style="margin-top:-4px">Your preferences are saved on this device.</p>
        <div class="section">
          <h3 style="margin:0 0 8px 0;">Appearance</h3>
          <div class="f">
            <div class="row2" style="align-items:center">
              <label for="themeSwitch">Theme</label>
              <label class="switch">
                <input id="themeSwitch" type="checkbox">
                <span class="slider"></span>
              </label>
            </div>
            <p class="small">Off = Light • On = Dark</p>
          </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border); margin:18px 0">

        <div class="section">
          <h3 style="margin:0 0 8px 0;">Goal</h3>
          <form id="goalForm" class="f">
            <div class="row2">
              <div>
                <label for="goalKg">Target (kg)</label>
                <input id="goalKg" type="number" min="1" step="1" value="20" />
              </div>
              <button class="btn primary" type="submit">Save</button>
            </div>
            <div class="small">This controls the dashboard progress bar goal.</div>
          </form>
          <div style="margin-top:10px">
            <button id="resetBtn" class="btn">Reset to Default</button>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT -->
    <section id="page-about" data-page>
      <section class="card section" id="about">
        <h2>About Bingo</h2>
        <p>
          BinGo helps communities recycle smarter. Citizens log pickups and see their impact,
          drivers get optimized routes, and cities track real results—kilograms diverted from
          landfill and CO₂e avoided. This demo shows the core idea in a simple way. 🚀
        </p>
      </section>
    </section>
  </main>

  <!-- Confetti overlay -->
  <canvas id="confettiCanvas"></canvas>

<script>

const MAX_GOAL = 100;      // hard cap
  const DEFAULT_GOAL = 20;

document.getElementById('goalForm').addEventListener('submit', (e)=>{
  e.preventDefault();
  let v = parseInt(document.getElementById('goalKg').value || DEFAULT_GOAL, 10);
  v = clampGoal(v);                               // enforce max 100
  targetKg = v;
  localStorage.setItem('ecotrack_goalKg', String(v));
  document.getElementById('goalPill').textContent = v;
  document.getElementById('goalKg').value = v;   // reflect any clamping
  setProgress(currentKg, targetKg);
  });

  /* ================= SPA NAV ================= */
  const links=[...document.querySelectorAll('[data-link]')];
  const pages={ dashboard:document.getElementById('page-dashboard'),
                settings:document.getElementById('page-settings'),
                about:document.getElementById('page-about') };

  function setActive(page){
    Object.values(pages).forEach(p=>p.classList.remove('active'));
    pages[page].classList.add('active');
    links.forEach(a=>a.classList.toggle('active', a.getAttribute('href')==='#'+page));
    history.replaceState(null,'','#'+page);
  }
  window.addEventListener('hashchange', ()=>{
    const p=location.hash.replace('#','')||'dashboard';
    setActive(pages[p]?p:'dashboard');
  });
  setActive((location.hash||'#dashboard').replace('#',''));

  /* ================= THEME ================= */
  const themeSwitch=document.getElementById('themeSwitch');
  function applyTheme(t){
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('ecotrack_theme', t);
    themeSwitch.checked = (t==='dark');
  }
  applyTheme(localStorage.getItem('ecotrack_theme')||'light');
  themeSwitch.addEventListener('change', ()=> applyTheme(themeSwitch.checked?'dark':'light'));

  /* ================= STATE: GOAL & PROGRESS ================= */
  const goalPill=document.getElementById('goalPill');
  const goalInput=document.getElementById('goalKg');
  let currentKg = 0;
  let targetKg  = parseInt(localStorage.getItem('ecotrack_goalKg')||'20',10);
  goalInput.value = targetKg;
  goalPill.textContent = targetKg;

  /* ================= GAUGE ================= */
  const CIRC = 628; // semicircle length for r=200
  function colorFor(p){ return p<0.34?'#ef4444': p<0.67?'#f59e0b':'#16a34a'; }
  function animate(from,to,dur,cb){
    const t0=performance.now();
    function tick(t){
      const k=Math.min(1,(t-t0)/dur);
      const e=k<.5?4*k*k*k:1-Math.pow(-2*k+2,3)/2;
      cb(from+(to-from)*e);
      if(k<1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  function setProgress(kg,target){
    const pct = Math.max(0, Math.min(1, target? kg/target : 0));
    const next = CIRC*(1-pct);
    const path = document.getElementById('g-progress');
    const kgEl = document.getElementById('g-kg');
    const pctEl= document.getElementById('g-pct');
    const cur  = parseFloat(path.style.strokeDashoffset || CIRC);
    animate(cur,next,700,v=>{ path.style.strokeDashoffset=v; });
    path.setAttribute('stroke', colorFor(pct));
    kgEl.textContent = `${kg.toFixed(1)} / ${target} kg`;
    pctEl.textContent= `${Math.round(pct*100)}%`;
    if(kg>=target){ launchConfetti(); }
  }
  setProgress(currentKg, targetKg);

  /* ================= MAP ================= */
  const map=L.map('map', { zoomControl:true }).setView([42.6629,21.1655],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);

  function addWasteMarker(material, kg){
    const c=map.getCenter();
    L.marker(c,{draggable:true}).addTo(map)
      .bindPopup(`<b>${material}</b><br/>~${kg} kg<br/><small>Drag to adjust</small>`).openPopup();
    currentKg += kg;
    setProgress(currentKg, targetKg);
  }
  document.getElementById('addBtn').onclick=()=>addWasteMarker('Plastic',1.0);
  document.getElementById('locateBtn').onclick=()=>map.locate({setView:true,maxZoom:16});
  document.getElementById('quickForm').onsubmit=(e)=>{
    e.preventDefault();
    const m=document.getElementById('material').value;
    const k=parseFloat(document.getElementById('kgInput').value||'0');
    if(k>0) addWasteMarker(m,k);
    e.target.reset();
  };

  /* ================= SETTINGS: GOAL SAVE / RESET ================= */
  document.getElementById('goalForm').addEventListener('submit', (e)=>{
    e.preventDefault();
    const v = parseInt(goalInput.value||'20',10);
    if(!Number.isFinite(v) || v<1){ return; }
    targetKg = v;
    localStorage.setItem('ecotrack_goalKg', String(v));
    goalPill.textContent = v;
    setProgress(currentKg, targetKg);
  });
  document.getElementById('resetBtn').addEventListener('click', ()=>{
    targetKg = 20; goalInput.value = 20; goalPill.textContent=20;
    localStorage.setItem('ecotrack_goalKg','20');
    setProgress(currentKg, targetKg);
  });

  /* ================= CONFETTI ================= */
  const confettiCanvas=document.getElementById('confettiCanvas');
  const ctx=confettiCanvas.getContext('2d');
  let confetti=[], animId;
  function resizeCanvas(){ confettiCanvas.width=window.innerWidth; confettiCanvas.height=window.innerHeight; }
  window.addEventListener('resize',resizeCanvas); resizeCanvas();
  function launchConfetti(){
    confetti=[];
    for(let i=0;i<220;i++){
      confetti.push({
        x:Math.random()*confettiCanvas.width,
        y:Math.random()*confettiCanvas.height - confettiCanvas.height,
        r:Math.random()*6+4,
        dx:Math.random()*2-1,
        dy:Math.random()*3+3,
        color:`hsl(${Math.random()*360},100%,50%)`
      });
    }
    confettiCanvas.style.display='block';
    cancelAnimationFrame(animId);
    animateConfetti();
    setTimeout(()=>{confettiCanvas.style.display='none'; cancelAnimationFrame(animId);}, 4000);
  }
  function animateConfetti(){
    ctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height);
    confetti.forEach(p=>{
      ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=p.color; ctx.fill();
      p.x+=p.dx; p.y+=p.dy;
      if(p.y>confettiCanvas.height){ p.y=-10; p.x=Math.random()*confettiCanvas.width; }
    });
    animId=requestAnimationFrame(animateConfetti);
  }
</script>
</body>
</html>
