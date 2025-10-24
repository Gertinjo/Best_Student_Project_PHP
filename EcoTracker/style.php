<?php
/* No <style> tags here — this is included inside <style> ... </style> */
?>
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
.footnote{margin-top:auto; font-size:12px; opacity:.75}

.main{padding:24px; overflow:auto}
.card{background:var(--panel); border:1px solid var(--border); border-radius:16px; padding:16px; box-shadow:var(--shadow)}
.title{font-weight:700;margin-bottom:6px}
.top{display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:stretch; margin-bottom:24px}
.row{display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:stretch; margin-bottom:32px}
#map{height:540px; border-radius:14px}
.actions{display:flex; flex-direction:column; gap:12px}
.btn{display:inline-flex; align-items:center; gap:8px; padding:12px 14px; border:1px solid var(--border); border-radius:12px; background:transparent; cursor:pointer; font-weight:600; color:var(--ink)}
.btn.primary{background:var(--accent); color:#fff; border-color:var(--accent)}
.btn[disabled]{opacity:.55; cursor:not-allowed}
.small{font-size:12px; color:var(--muted)}
.muted{color:var(--muted)}
.f{display:grid; gap:12px}
.f label{font-size:12px; color:var(--muted-2)}
.f input,.f select{width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:transparent; color:var(--ink)}
.pill{display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:999px}
.divider{border:none;border-top:1px solid var(--border); margin:14px 0}

.super-gauge{display:flex; align-items:center; justify-content:center}
.g-wrap{max-width:100%; width:100%}
.g-caption{display:flex; justify-content:center; gap:12px; margin-top:8px; font-weight:700}

/* Confetti canvas */
#confettiCanvas{position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999; display:none}

/* Emoji marker */
.emoji-marker{
  font-size:20px; line-height:32px; width:32px; height:32px; text-align:center;
  transform:translate(-50%,-50%); filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
}

@media (max-width:1200px){
  body{grid-template-columns:72px 1fr}
  .brand{display:none}
  .top{grid-template-columns:1fr}
  .row{grid-template-columns:1fr}
}
