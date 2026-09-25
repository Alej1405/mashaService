{{-- Estilos compartidos del panel de Operaciones.

     Estos paneles no cargan el tema del panel principal y la mitad de las
     utilidades no aplican, así que el CSS es propio. Vive aquí una sola vez:
     una pantalla nueva no define el suyo, lo añade a este archivo. --}}
<style>
    .op { --op-surface:#fff; --op-border:#e2e8f0; --op-border-soft:#f1f5f9;
          --op-text:#0f172a; --op-muted:#64748b; --op-muted-2:#64748b;
          --op-accent:#4f46e5; --op-warn:#b45309; --op-warn-soft:#fffbeb;
          --op-ok:#047857; --op-r:14px; display:flex; flex-direction:column; gap:18px; }

    .op-barra { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .op-buscar { flex:1 1 320px; min-width:0; }
    .op-buscar input { width:100%; box-sizing:border-box; padding:11px 16px; font-size:14px;
                       border:1px solid var(--op-border); border-radius:12px; background:var(--op-surface);
                       color:var(--op-text); }
    .op-buscar input:focus { outline:none; border-color:var(--op-accent); box-shadow:0 0 0 3px #4f46e51f; }
    .op-btn { display:inline-flex; align-items:center; gap:7px; white-space:nowrap; flex-shrink:0;
              padding:11px 18px; font-size:14px; font-weight:700; border-radius:12px; cursor:pointer;
              border:1px solid var(--op-border); background:var(--op-surface); color:#334155;
              text-decoration:none; transition:transform .12s ease; }
    .op-btn:active { transform:scale(.98); }
    .op-btn-principal { background:var(--op-accent); border-color:var(--op-accent); color:#fff; }
    .op-btn svg { width:16px; height:16px; }
    .op-ayuda-escaneo { flex:1 1 100%; margin:0; font-size:12px; color:var(--op-muted); }

    .op-cols { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .op-panel { background:var(--op-surface); border:1px solid var(--op-border);
                border-radius:var(--op-r); overflow:hidden; }
    .op-tit { margin:0; font-size:11px; font-weight:700; letter-spacing:.9px;
              text-transform:uppercase; color:var(--op-muted); }

    .op-panel header { display:flex; align-items:center; justify-content:space-between;
                       gap:12px; padding:14px 18px; border-bottom:1px solid var(--op-border-soft); }
    .op-panel h2 { margin:0; font-size:11px; font-weight:700; letter-spacing:.9px;
                   text-transform:uppercase; color:var(--op-muted); }
    .op-fila { display:flex; align-items:center; justify-content:space-between; gap:12px;
               padding:12px 18px; border-bottom:1px solid var(--op-border-soft); }
    .op-fila:last-child { border-bottom:0; }
    .op-fila-titulo { font-size:14px; font-weight:700; color:var(--op-text);
                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .op-fila-sub { font-size:12px; color:var(--op-muted); margin-top:2px; }
    .op-vacio { padding:30px 18px; text-align:center; font-size:14px; color:var(--op-muted); }
    .op-chip { flex-shrink:0; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700;
               background:var(--op-warn-soft); color:var(--op-warn); }
    .op-cantidad { flex-shrink:0; font-size:14px; font-weight:700; }
    .op-entra { color:var(--op-ok); }

    .op-metricas { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
    .op-card { background:var(--op-surface); border:1px solid var(--op-border);
               border-radius:var(--op-r); padding:18px; }
    .op-val { font-size:26px; font-weight:700; letter-spacing:-.8px; line-height:1.05; color:var(--op-text); }
    .op-lbl { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase;
              color:var(--op-muted); margin-top:7px; }
    .op-hint { font-size:12px; color:var(--op-muted-2); margin-top:2px; }

    .op-visor { position:fixed; inset:0; z-index:50; display:flex; flex-direction:column;
                align-items:center; justify-content:center; gap:16px; padding:24px;
                background:rgba(0,0,0,.9); }
    .op-visor p { color:#fff; font-size:14px; font-weight:700; text-align:center; max-width:420px; }
    .op-visor video { width:100%; max-width:420px; max-height:60vh; border-radius:18px; object-fit:cover; }
    [x-cloak] { display:none !important; }

    @media (max-width:1100px) { .op-metricas { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:820px)  { .op-cols { grid-template-columns:1fr; } }
    @media (max-width:520px)  { .op-metricas { grid-template-columns:1fr; }
                                .op-btn { flex:1 1 auto; justify-content:center; } }
</style>
