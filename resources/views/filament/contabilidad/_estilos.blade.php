{{-- Estilos compartidos del panel contable: el CSS base de Filament no trae
     todas las utilidades y este panel no carga el tema del panel App. --}}
<style>
    .ct { --s:#fff; --s2:#f8fafc; --s3:#f1f5f9; --b:#e2e8f0; --b2:#f1f5f9;
          --t:#0f172a; --t2:#1e293b; --m:#64748b; --m2:#94a3b8;
          --ac:#4f46e5; --ok:#047857; --ok-s:#ecfdf5; --wa:#b45309; --wa-s:#fffbeb;
          --da:#be123c; --da-s:#fff1f2; --r:14px;
          display:flex; flex-direction:column; gap:20px; }
    .ct-tit { font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase; color:var(--m); margin:0; }
    .ct-panel { background:var(--s); border:1px solid var(--b); border-radius:var(--r); overflow:hidden; }
    .ct-panel > header { display:flex; align-items:center; justify-content:space-between;
                         gap:12px; padding:14px 18px; border-bottom:1px solid var(--b2); }
    .ct-tabla { width:100%; border-collapse:collapse; font-size:14px; }
    .ct-tabla thead th { font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase;
                         color:var(--m); background:var(--s3); padding:11px 18px; text-align:left; }
    .ct-tabla td { padding:12px 18px; border-top:1px solid var(--b2); color:var(--t2); }
    .ct-tabla .num { text-align:right; white-space:nowrap; }
    .ct-tabla .fuerte td { font-weight:700; color:var(--t); }
    .ct-tabla tfoot td { background:#eef2ff; font-weight:700; color:var(--t); border-top:1px solid var(--b); }
    .ct-chip { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
    .ct-chip-ok { background:var(--ok-s); color:var(--ok); }
    .ct-chip-wa { background:var(--wa-s); color:var(--wa); }
    .ct-chip-da { background:var(--da-s); color:var(--da); }
    .ct-chip-n  { background:var(--s3);   color:var(--m); }
    .ct-aviso { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:var(--r);
                background:var(--wa-s); border:1px solid #fde68a; }
    .ct-aviso p { margin:0; font-size:14px; color:var(--wa); flex:1; }
    .ct-cols { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .ct-rej-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
    .ct-card { background:var(--s); border:1px solid var(--b); border-radius:var(--r); padding:18px; }
    .ct-val { font-size:26px; font-weight:700; letter-spacing:-.8px; color:var(--t); margin:0; }
    .ct-pie { font-size:12px; color:var(--m); margin:2px 0 0; }
    .ct-pie2 { font-size:12px; color:var(--m2); margin:2px 0 0; }
    .ct-fila { display:flex; align-items:center; justify-content:space-between; gap:12px;
               padding:12px 18px; border-bottom:1px solid var(--b2); }
    .ct-fila:last-child { border-bottom:0; }
    .ct-fila-t { font-size:14px; font-weight:700; color:var(--t); margin:0; }
    .ct-fila-s { font-size:12px; color:var(--m); margin:2px 0 0; }
    .ct-tabs { display:flex; flex-wrap:wrap; gap:8px; }
    .ct-tab { padding:8px 14px; border-radius:999px; font-size:12px; font-weight:700;
              text-decoration:none; border:1px solid var(--b); background:var(--s); color:var(--m); }
    .ct-tab.activo { background:#eef2ff; border-color:#eef2ff; color:var(--ac); }
    @media (max-width:900px) { .ct-cols { grid-template-columns:1fr; } .ct-rej-4 { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:560px) { .ct-rej-4 { grid-template-columns:1fr; }
        .ct-tabla thead { display:none; }
        .ct-tabla td { display:flex; justify-content:space-between; gap:12px; border-top:0; padding:8px 16px; }
        .ct-tabla tr { display:block; border-top:1px solid var(--b2); padding:8px 0; }
        .ct-tabla td::before { content:attr(data-col); font-size:11px; font-weight:700;
                               text-transform:uppercase; letter-spacing:.6px; color:var(--m); }
    }

    /* Descarga de una declaración: se ve como lo que es, un botón */
    .ct-btn-descarga { display:inline-flex; align-items:center; gap:6px; flex-shrink:0;
                       padding:7px 14px; border-radius:10px; font-size:13px; font-weight:700;
                       background:var(--ac); color:#fff; text-decoration:none;
                       transition:filter .15s ease; }
    .ct-btn-descarga:hover { filter:brightness(1.1); }

    /* El resumen de una declaración antes de descargarla */
    .ct-resumen { display:flex; flex-wrap:wrap; gap:18px; width:100%;
                  padding:10px 0 2px; border-top:1px dashed var(--b2); margin-top:8px; }
    .ct-resumen > div { display:flex; flex-direction:column; min-width:92px; }
    .ct-resumen-n { font-size:15px; font-weight:700; color:var(--t); }
    .ct-resumen-e { font-size:11px; color:var(--m); }

    /* Barra de pestañas: elegir estado y ejercicio */
    .ct-barra { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .ct-pestana { padding:7px 14px; border:1px solid var(--b); border-radius:999px;
                  background:var(--s); color:var(--m); font-size:13px; font-weight:600;
                  cursor:pointer; transition:all .15s ease; }
    .ct-pestana:hover { border-color:var(--ac); color:var(--t); }
    .ct-pestana.es-activa { background:var(--ac); border-color:var(--ac); color:#fff; }

    /* Tabla de doble entrada: el estado de cambios en el patrimonio */
    .ct-matriz-envoltura { overflow-x:auto; }
    .ct-matriz { width:100%; border-collapse:collapse; font-size:12px; }
    .ct-matriz th, .ct-matriz td { padding:8px 10px; border-bottom:1px solid var(--b2);
                                   text-align:right; white-space:nowrap; }
    .ct-matriz th { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
                    color:var(--m); vertical-align:bottom; background:var(--s2);
                    position:sticky; top:0; }
    .ct-matriz th.es-vacia { color:var(--m2); font-weight:500; }
    .ct-matriz th span, .ct-matriz td span { display:block; font-size:10px; font-weight:500; color:var(--m2); }
    .ct-matriz .ct-matriz-fija { position:sticky; left:0; text-align:left; background:var(--s);
                                 font-weight:600; color:var(--t); min-width:220px; z-index:1; }
    .ct-matriz thead .ct-matriz-fija { background:var(--s2); z-index:2; }
    .ct-matriz td { color:var(--m2); }
    .ct-matriz td.es-valor { color:var(--t); font-weight:700; }

    /* Desplegables e inputs dentro de las filas */
    /* La flecha la pinta el CSS base de Filament como imagen de fondo. Sin
       no-repeat se dibuja una por cada ancho del select: el desplegable
       aparecía con diez flechas encima del texto. */
    .ct-select { min-width:0; padding:8px 30px 8px 10px; border:1px solid var(--b); border-radius:10px;
                 font-size:13px; color:var(--t); font-family:inherit;
                 background-color:var(--s); background-repeat:no-repeat;
                 background-position:right 8px center; background-size:1.1em 1.1em; }
    .ct-select:focus { outline:none; border-color:var(--ac); box-shadow:0 0 0 3px #4f46e51f; }
</style>
