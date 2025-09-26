<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>EcoTrack — Goal Limit + Like Marker</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
  :root{
    --bg:#f1f5f9; --panel:#ffffff; --ink:#0b1020;
    --muted:#475569; --muted-2:#334155;
    --accent:#16a34a; --amber:#f59e0b; --red:#ef4444; --border:#e5e7eb;
    --sidebar:#0f172a; --sidebar-ink:#cbd5e1; --sidebar-hover:#111827;
    --shadow:0 1px 2px rgba(0,0,0,.06);
  }
  [data-theme="dark"]{
    --bg:#0b1220; --panel:#0f172a; --ink:#e5e7eb;
    --muted:#a7b0c0; --muted-2:#cbd5e1;
    --accent:#22c55e; --amber:#fbbf24; --red:#f87171; --border:#1f2937;
    --sidebar:#0a0f1a; --sidebar-ink:#94a3b8; --sidebar-hover:#111827;
    --shadow:0 1px 2px rgba(0,0,0,.35);
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0; font-family:system-ui,Inter,Arial;
    color:var(--ink); background:var(--bg);
    display:grid; grid-template-columns:260px 1fr; min-height:100vh;
  }
  .sidebar{background:var(--sidebar); color:var(--sidebar-ink); padding:20px; display:flex; flex-direction:column; gap:16px}
  .brand{color:#fff; font-weight:800; font-size:20px}
  .nav a{display:block; padding:10px 12px; border-radius:10px; color:var(--sidebar-ink); text-decoration:none}
  .nav a.active,.nav a:hover{ background:var(--sidebar-hover); color:#fff }

  .main{padding:24px; overflow:auto}
  .card{background:var(--panel); border:1px solid var(--border); border-radius:16px; padding:16px; box-shadow:var(--shadow)}
  .top{display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:stretch; margin-bottom:24px}
  .row{display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:stretch; margin-bottom:32px}
  #map{height:540px; border-radius:14px}
  .actions{display:flex; flex-direction:column; gap:12px}
  .btn{display:inline-flex; align-items:center; gap:8px; padding:12px 14px; border:1px solid var(--border); border-radius:12px; background:transparent; cursor:pointer; font-weight:600; color:var(--ink)}
  .btn.primary{background:var(--accent); color:#fff; border-color:var(--accent)}
  .small{font-size:12px; color:var(--muted)}
  .muted{color:var(--muted)}
  .f{display:grid; gap:12px}
  .f label{font-size:12px; color:var(--muted-2)}
  .f input,.f select{width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:transparent; color:var(--ink)}
  .pill{display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:999px}

  .super-gauge{display:flex; align-items:center; justify-content:center}
  .g-wrap{max-width:100%; width:100%}
  .g-caption{display:flex; justify-content:center; gap:12px; margin-top:8px; font-weight:700}

  #confettiCanvas{position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999; display:none}

  @media (max-width:1200px){
    body{grid-template-columns:72px 1fr}
    .brand{display:none}
    .top{grid-template-columns:1fr}
    .row{grid-template-columns:1fr}
  }

  /* Simple emoji marker styles */
  .emoji-marker{
    font-size:20px; line-height:32px; width:32px; height:32px; text-align:center;
    transform:translate(-50%,-50%); filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
  }
</style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ EcoTrack</div>
    <nav class="nav">
      <a href="#" class="active">Dashboard</a>
    </nav>
    <div style="margin-top:auto;font-size:12px;opacity:.7">Goal limit & like marker demo</div>
  </aside>

  <main class="main">
    <div class="top">
      <div class="card">
        <div class="super-gauge g-wrap">
          <!-- Bottom-aligned wide semicircle gauge -->
          <svg id="g-svg" viewBox="0 0 500 300" width="100%" height="240" aria-label="Progress gauge">
            <path id="g-track" d="M50,250 A200,200 0 0 1 450,250"
                  fill="none" stroke="#e5e7eb" stroke-width="28" stroke-linecap="round"/>
            <path id="g-progress" d="M50,250 A200,200 0 0 1 450,250"
                  fill="none" stroke="#16a34a" stroke-width="28" stroke-linecap="round"
                  stroke-dasharray="628" stroke-dashoffset="628"/>
            <g stroke="#cbd5e1" stroke-width="3">
              <line x1="110" y1="246" x2="110" y2="260"/>
              <line x1="250" y1="226" x2="250" y2="260"/>
              <line x1="390" y1="246" x2="390" y2="260"/>
            </g>
            <text x="250" y="140" text-anchor="middle" font-weight="700" font-size="22">Progress</text>
            <text id="g-kg"  x="250" y="170" text-anchor="middle" font-weight="800" font-size="28">0 / 20 kg</text>
            <text id="g-pct" x="250" y="195" text-anchor="middle" font-size="14">0%</text>
          </svg>
        </div>
        <div class="g-caption muted">
          Goal is capped at <b>100 kg</b>. Add waste markers to progress. “Like” markers don’t change kg.
        </div>
      </div>

      <div class="card actions">
        <button id="addBtn" class="btn primary">＋ Add Waste Marker</button>
        <button id="likeBtn" class="btn">❤️ Add Like Marker</button>
        <button id="locateBtn" class="btn">📍 Use My Location</button>
        <div class="pill small">Goal: <strong id="goalPill">20</strong> kg (max 100)</div>
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
          <div class="small">Marker is added at the <b>map center</b>.</div>
        </form>

        <hr style="border:none;border-top:1px solid var(--border); margin:14px 0">
        <div class="f">
          <label>Add a “Like” marker (no kg)</label>
          <button id="likeBtn2" class="btn">❤️ Drop Like at center</button>
        </div>

        <hr style="border:none;border-top:1px solid var(--border); margin:14px 0">
        <form id="goalForm" class="f">
          <div>
            <label for="goalKg">Set Goal (kg, max 100)</label>
            <input id="goalKg" type="number" min="1" max="100" step="1" value="20" />
          </div>
          <button class="btn primary" type="submit">Save Goal</button>
          <div class="small">We cap the goal at 100 kg.</div>
        </form>
      </div>
    </div>
  </main>

  <canvas id="confettiCanvas"></canvas>

<script>
  /* ========= CONFIG ========= */
  const MAX_GOAL = 100;      // hard cap
  const DEFAULT_GOAL = 20;

  /* ========= THEME (optional) ========= */
  function applyTheme(t){ document.documentElement.setAttribute('data-theme', t); }
  applyTheme('light');

  /* ========= STATE ========= */
  let currentKg = 0;
  let targetKg  = (()=> {
    const saved = parseInt(localStorage.getItem('ecotrack_goalKg')||DEFAULT_GOAL, 10);
    return clampGoal(saved);
  })();
  document.getElementById('goalPill').textContent = targetKg;
  document.getElementById('goalKg').value = targetKg;

  function clampGoal(v){
    if(!Number.isFinite(v) || v < 1) return 1;
    return Math.min(v, MAX_GOAL);
  }

  /* ========= GAUGE ========= */
  const CIRC = 628;
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

  /* ========= MAP ========= */
  const map=L.map('map',{zoomControl:true}).setView([42.6629,21.1655],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19, attribution:'&copy; OpenStreetMap'}).addTo(map);

  // Like marker icon (emoji)
  const likeIcon = L.divIcon({
    className: 'emoji-marker',
    html: '❤️',
    iconSize: [32,32],
    iconAnchor: [16,16]
  });

  function addWasteMarker(material, kg){
    const c=map.getCenter();
    L.marker(c,{draggable:true}).addTo(map)
      .bindPopup(`<b>${material}</b><br/>~${kg} kg<br/><small>Drag to adjust</small>`).openPopup();
    currentKg += kg;
    setProgress(currentKg, targetKg);
  }

  function addLikeMarker(){
    const c=map.getCenter();
    L.marker(c,{icon: likeIcon, draggable:true})
      .addTo(map).bindPopup(`<b>Liked spot</b><br/><small>Drag to adjust</small>`);
    // no change to kg/progress
  }

  // Buttons
  document.getElementById('addBtn').onclick=()=>addWasteMarker('Plastic',1.0);
  document.getElementById('likeBtn').onclick=addLikeMarker;
  document.getElementById('likeBtn2').onclick=addLikeMarker;
  document.getElementById('locateBtn').onclick=()=>map.locate({setView:true,maxZoom:16});

  // Quick form
  document.getElementById('quickForm').onsubmit=(e)=>{
    e.preventDefault();
    const m=document.getElementById('material').value;
    const k=parseFloat(document.getElementById('kgInput').value||'0');
    if(k>0) addWasteMarker(m,k);
    e.target.reset();
  };

  // Goal form with 100 kg cap
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

  /* ========= CONFETTI ========= */
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
    setTimeout(()=>{confettiCanvas.style.display='none'; cancelAnimationFrame(animId);},4000);
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
