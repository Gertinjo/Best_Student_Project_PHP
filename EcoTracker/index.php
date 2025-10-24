<?php
session_start();
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BinGo — Dashboard</title>
  <meta name="csrf" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <!-- App styles (single source) -->
  <style><?php require __DIR__.'/style.php'; ?></style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">♻️ BinGo</div>
    <nav class="nav">
      <a href="index.php" class="active">Dashboard</a>
      <a href="settings.php">Settings</a>
    </nav>
    <div class="footnote">PHP session demo • Max 100 kg</div>
  </aside>

  <!-- Add Marker Modal -->
<div id="markerModal" class="modal-overlay" style="display:none;">
  <div class="modal-card">
    <h3>Add Waste Marker</h3>
    <form id="markerForm">
      <label>Material</label>
      <select id="mMaterial" required>
        <option value="Plastic">Plastic</option>
        <option value="Paper">Paper</option>
        <option value="Glass">Glass</option>
        <option value="Metal">Metal</option>
        <option value="E-waste">E-waste</option>
      </select>

      <label>Estimated Weight (kg)</label>
      <input id="mWeight" type="number" min="0.1" step="0.1" required placeholder="e.g. 1.5">

      <label>Location Name (optional)</label>
      <input id="mLocation" type="text" placeholder="e.g. City Park Bin">

      <div class="form-actions">
        <button type="button" id="cancelMarker" class="btn">Cancel</button>
        <button type="submit" class="btn primary">Add Marker</button>
      </div>
    </form>
  </div>
</div>

  <main class="main">
    <div class="top">
      <div class="card">
        <div class="super-gauge g-wrap">
          <!-- Half-circle gauge (bottom aligned) -->
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
            <text id="g-kg"  x="250" y="170" text-anchor="middle" font-weight="800" font-size="28">—</text>
            <text id="g-pct" x="250" y="195" text-anchor="middle" font-size="14">—</text>
          </svg>
        </div>
        <div class="g-caption muted">Goal is capped at <b>100 kg</b>. “Like” markers don’t change kg.</div>
      </div>

      <div class="card actions">
        <button id="addBtn" class="btn primary">＋ Add Waste Marker</button>
        <button id="likeBtn" class="btn">❤️ Add Like Marker</button>
        <button id="locateBtn" class="btn">📍 Use My Location</button>
        <div class="pill small">Goal: <strong id="goalPill">—</strong> kg (max 100)</div>
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
          <div class="small">Marker is added at the <b>map center</b>.</div>
        </form>

        <hr class="divider">
        <div class="f">
          <label>Add a “Like” marker (no kg)</label>
          <button id="likeBtn2" class="btn">❤️ Drop Like at center</button>
        </div>
      </div>
    </div>
  </main>

  <canvas id="confettiCanvas"></canvas>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

  <!-- Minimal inline JS (browser-only parts: map, gauge, confetti) -->
  <script>
  const CIRC=628, API='api.php'; let currentKg=0, goal=20, map;

  // Read CSRF from meta (issued by PHP)
  const CSRF = document.querySelector('meta[name="csrf"]').content;

  // Gauge elements
  const path=document.getElementById('g-progress');
  const kgEl=document.getElementById('g-kg');
  const pctEl=document.getElementById('g-pct');
  const goalPill=document.getElementById('goalPill');
  const addBtn=document.getElementById('addBtn');
  const quickAddBtn=document.getElementById('quickAddBtn');

  function colorFor(p){ return p<0.34?'#ef4444': p<0.67?'#f59e0b':'#16a34a'; }
  function animate(from,to,dur,cb){ const t0=performance.now(); function tick(t){ const k=Math.min(1,(t-t0)/dur); const e=k<.5?4*k*k*k:1-Math.pow(-2*k+2,3)/2; cb(from+(to-from)*e); if(k<1) requestAnimationFrame(tick);} requestAnimationFrame(tick); }
  function setProgress(kg,target){
    const eff = Math.min(target, 100);
    const shown = Math.min(kg, eff);
    const pct = eff ? Math.min(1, shown/eff) : 0;
    const cur=parseFloat(path.style.strokeDashoffset||CIRC);
    const next=CIRC*(1-pct); animate(cur,next,700,v=>path.style.strokeDashoffset=v);
    path.setAttribute('stroke', colorFor(pct));
    kgEl.textContent = `${shown.toFixed(1)} / ${eff} kg`;
    pctEl.textContent = `${Math.round(pct*100)}%`;
    const reached = shown >= eff;
    addBtn.disabled = reached; quickAddBtn.disabled = reached;
    if (reached) launchConfetti();
  }

  // Confetti
  const confettiCanvas=document.getElementById('confettiCanvas');
  const ctx=confettiCanvas.getContext('2d'); let confetti=[], animId;
  function resizeCanvas(){ confettiCanvas.width=innerWidth; confettiCanvas.height=innerHeight; }
  addEventListener('resize',resizeCanvas); resizeCanvas();
  function launchConfetti(){
    confetti=[]; for(let i=0;i<220;i++){ confetti.push({x:Math.random()*confettiCanvas.width,y:Math.random()*confettiCanvas.height-confettiCanvas.height,r:Math.random()*6+4,dx:Math.random()*2-1,dy:Math.random()*3+3,color:`hsl(${Math.random()*360},100%,50%)`}); }
    confettiCanvas.style.display='block'; cancelAnimationFrame(animId); (function loop(){ ctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height); confetti.forEach(p=>{ ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fillStyle=p.color; ctx.fill(); p.x+=p.dx; p.y+=p.dy; if(p.y>confettiCanvas.height){ p.y=-10; p.x=Math.random()*confettiCanvas.width; } }); animId=requestAnimationFrame(loop); })();
    setTimeout(()=>{ confettiCanvas.style.display='none'; cancelAnimationFrame(animId); }, 4000);
  }

  // Map
  function initMap(markers){
    map=L.map('map',{zoomControl:true}).setView([42.6629,21.1655],12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19, attribution:'&copy; OpenStreetMap'}).addTo(map);
    const likeIcon=L.divIcon({className:'emoji-marker', html:'❤️', iconSize:[32,32], iconAnchor:[16,16]});
    // existing markers
    (markers||[]).forEach(m=>{
      if(m.type==='like'){
        L.marker([m.lat,m.lng],{icon:likeIcon}).addTo(map).bindPopup('<b>Liked spot</b>');
      } else {
        L.marker([m.lat,m.lng]).addTo(map).bindPopup(`<b>${m.material}</b><br/>~${m.kg} kg`);
      }
    });
    document.getElementById('locateBtn').onclick=()=>map.locate({setView:true,maxZoom:16});
    document.getElementById('addBtn').onclick=()=>quickWaste(1.0,'Plastic');
    document.getElementById('likeBtn').onclick=()=>addLike();
    document.getElementById('likeBtn2').onclick=()=>addLike();
    document.getElementById('quickForm').addEventListener('submit',e=>{
      e.preventDefault();
      const m=document.getElementById('material').value;
      const k=parseFloat(document.getElementById('kgInput').value||'0');
      if(k>0) quickWaste(k,m);
      e.target.reset();
    });

    async function quickWaste(kg, material){
      const c=map.getCenter();
      const res=await fetch('api.php?action=add_marker',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':CSRF},body:JSON.stringify({type:'waste',material,kg,lat:c.lat,lng:c.lng})});
      const data=await res.json(); if(!data.ok) return;
      currentKg=data.currentKg; setProgress(currentKg, data.goal);
      L.marker([c.lat,c.lng]).addTo(map).bindPopup(`<b>${material}</b><br/>~${data.addedKg} kg`).openPopup();
    }
    async function addLike(){
      const c=map.getCenter();
      const res=await fetch('api.php?action=add_marker',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':CSRF},body:JSON.stringify({type:'like',lat:c.lat,lng:c.lng})});
      const data=await res.json(); if(!data.ok) return;
      const likeIcon2=L.divIcon({className:'emoji-marker', html:'❤️', iconSize:[32,32], iconAnchor:[16,16]});
      L.marker([c.lat,c.lng],{icon:likeIcon2}).addTo(map).bindPopup('<b>Liked spot</b>').openPopup();
    }
  }

  // Boot: get current state from PHP (goal, kg, markers, csrf)
  (async function(){
    const s=await fetch('api.php?action=get_state'); const data=await s.json();
    if(!data.ok) return;
    currentKg=data.currentKg; goal=data.goal; goalPill.textContent=goal;
    setProgress(currentKg, goal); initMap(data.markers||[]);
  })();
  </script>
</body>
</html>
