<x-filament-panels::page>
<div class="prod-diag" wire:ignore>
<style>
  .prod-diag{
    --paper:#f6f7f5; --ink:#171a1f; --muted:#5c636e;
    --line:#dfe2dd; --card:#ffffff; --card2:#f0f2ef;
    --accent:#2f6f6b;
    --ok:#2e7d54; --warn:#b5761b; --err:#c0392b;
    --genr:#2f6f6b; --media:#5b53a6; --price:#1f6f9c; --inv:#8a6d1e; --dead:#b03a2e;
    --chip:#eef1ee; --shadow:0 1px 2px rgba(20,24,31,.06),0 8px 24px rgba(20,24,31,.05);
    --mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace;
    --sans:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
    color:var(--ink); font-family:var(--sans); line-height:1.5; -webkit-font-smoothing:antialiased;
  }
  .prod-diag *{box-sizing:border-box}
  .prod-diag .wrap{max-width:1080px;margin:0 auto;padding:8px 0 24px}
  .prod-diag header{border-bottom:1px solid var(--line);padding-bottom:22px;margin-bottom:32px}
  .prod-diag .eyebrow{font:600 12px/1 var(--sans);letter-spacing:.14em;text-transform:uppercase;color:var(--accent);margin:0 0 10px}
  .prod-diag h1{font-size:clamp(24px,3.4vw,34px);line-height:1.1;margin:0;letter-spacing:-.02em;text-wrap:balance;color:var(--ink)}
  .prod-diag .sub{color:var(--muted);margin:12px 0 0;max-width:62ch}
  .prod-diag h2{font-size:20px;letter-spacing:-.01em;margin:44px 0 6px;display:flex;align-items:center;gap:10px;color:var(--ink)}
  .prod-diag h2 .n{font:600 12px/1.6 var(--mono);color:var(--accent);border:1px solid var(--line);border-radius:6px;padding:2px 7px}
  .prod-diag h2 .live{margin-left:auto;font:600 10px/1 var(--sans);letter-spacing:.08em;text-transform:uppercase;color:var(--ok);border:1px solid color-mix(in srgb,var(--ok) 40%,var(--line));border-radius:20px;padding:4px 9px}
  .prod-diag .lead{color:var(--muted);margin:0 0 20px;max-width:70ch}
  .prod-diag .row{display:flex;gap:18px;flex-wrap:wrap}
  .prod-diag .scroll{overflow-x:auto;padding-bottom:6px}
  .prod-diag .scroll .row{flex-wrap:nowrap;min-width:min-content}
  .prod-diag .card{background:var(--card);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);min-width:240px;flex:1}
  .prod-diag .card h3{margin:0;font:600 13px/1 var(--mono);padding:12px 14px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:8px;color:var(--ink)}
  .prod-diag .card h3 .tag{font:600 10px/1 var(--sans);letter-spacing:.06em;text-transform:uppercase;padding:3px 7px;border-radius:20px;background:var(--chip);color:var(--muted)}
  .prod-diag ul.cols{list-style:none;margin:0;padding:8px 0}
  .prod-diag ul.cols li{font:12px/1.7 var(--mono);padding:2px 14px;display:flex;gap:8px;align-items:baseline}
  .prod-diag ul.cols li::before{content:"";width:7px;height:7px;border-radius:2px;flex:0 0 auto;background:var(--dot,var(--muted));transform:translateY(1px)}
  .prod-diag .g-genr{--dot:var(--genr)} .prod-diag .g-media{--dot:var(--media)} .prod-diag .g-price{--dot:var(--price)} .prod-diag .g-inv{--dot:var(--inv)} .prod-diag .g-dead{--dot:var(--dead)}
  .prod-diag .k{color:var(--muted)}
  .prod-diag .legend{display:flex;gap:14px;flex-wrap:wrap;margin:14px 0 0;font-size:12px;color:var(--muted)}
  .prod-diag .legend span{display:inline-flex;align-items:center;gap:6px}
  .prod-diag .legend i{width:9px;height:9px;border-radius:2px;display:inline-block}
  .prod-diag .flows{display:grid;grid-template-columns:1fr;gap:8px;margin-top:6px}
  .prod-diag .flow{display:grid;grid-template-columns:230px 26px 1fr auto;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:10px;padding:10px 14px}
  .prod-diag .flow .src{font:600 13px var(--sans);color:var(--ink)}
  .prod-diag .flow .src small{display:block;font:400 11px var(--mono);color:var(--muted)}
  .prod-diag .arrow{color:var(--muted);text-align:center;font-size:16px}
  .prod-diag .flow .dst{font:12px/1.4 var(--sans);color:var(--muted)}
  .prod-diag .badge{font:600 11px/1 var(--sans);padding:4px 8px;border-radius:20px;white-space:nowrap}
  .prod-diag .b-ok{background:color-mix(in srgb,var(--ok) 16%,transparent);color:var(--ok)}
  .prod-diag .b-err{background:color-mix(in srgb,var(--err) 16%,transparent);color:var(--err)}
  .prod-diag .b-action{background:color-mix(in srgb,var(--accent) 16%,transparent);color:var(--accent)}
  .prod-diag .b-query{background:color-mix(in srgb,var(--price) 16%,transparent);color:var(--price)}
  .prod-diag .flow.ok{border-left:3px solid var(--ok)} .prod-diag .flow.err{border-left:3px solid var(--err)}
  .prod-diag .grp{font:600 12px var(--sans);color:var(--accent);letter-spacing:.04em;margin:16px 0 6px}
  .prod-diag .errbox{background:var(--card);border:1px solid var(--line);border-left:3px solid var(--err);border-radius:12px;padding:6px 4px;box-shadow:var(--shadow)}
  .prod-diag .errbox ol{margin:0;padding:6px 20px 6px 40px}
  .prod-diag .errbox li{padding:9px 0;border-bottom:1px dashed var(--line);color:var(--ink)}
  .prod-diag .errbox li:last-child{border-bottom:0}
  .prod-diag .errbox b{font-weight:650}
  .prod-diag .errbox code,.prod-diag .inline{font:12px var(--mono);background:var(--chip);padding:1px 6px;border-radius:5px}
  .prod-diag .target .card{border-color:color-mix(in srgb,var(--accent) 40%,var(--line))}
  .prod-diag .target .card h3{color:var(--accent)}
  .prod-diag .note{color:var(--muted);font-size:13px;margin-top:10px}
  .prod-diag .pill{display:inline-block;font:600 11px var(--sans);color:var(--accent);border:1px solid color-mix(in srgb,var(--accent) 35%,var(--line));border-radius:20px;padding:3px 10px}
  .prod-diag footer{margin-top:48px;border-top:1px solid var(--line);padding-top:16px;color:var(--muted);font-size:12px}
</style>

<div class="wrap">
  <header>
    <p class="eyebrow">Mashaec ERP · Refactor</p>
    <h1>Módulo Producto — diagrama y diagnóstico</h1>
    <p class="sub">Estado actual de las tablas y flujos del producto, los errores detectados, y el modelo normalizado hacia un solo flujo. <b>El diagrama es fijo; las tablas, columnas y operaciones se leen en tiempo real del esquema PostgreSQL.</b></p>
  </header>

  {{-- ── A) Estado actual — EN VIVO desde el esquema real ── --}}
  <h2><span class="n">A</span> Estado actual <span class="live">● en vivo</span></h2>
  <p class="lead">Cada tarjeta es una tabla real del contexto Producto con sus columnas actuales, clasificadas por contexto. Si una columna cambia en la BDD, este mapa cambia solo.</p>

  <div class="scroll">
    <div class="row">
      @foreach ($this->getTablesData() as $t)
        <div class="card">
          <h3>{{ $t['table'] }} <span class="tag">{{ $t['count'] }} col · {{ $t['tag'] }}</span></h3>
          <ul class="cols">
            @foreach ($t['columns'] as $c)
              <li class="g-{{ $c['ctx'] }}">{{ $c['name'] }}@if ($c['note'])<span class="k">&nbsp;{{ $c['note'] }}</span>@endif</li>
            @endforeach
          </ul>
          @if ($t['nota'])
            <p class="note" style="padding:0 14px 14px">{{ $t['nota'] }}</p>
          @endif
        </div>
      @endforeach
    </div>
  </div>

  <div class="legend">
    <span><i style="background:var(--genr)"></i>General</span>
    <span><i style="background:var(--price)"></i>Precio / estrategia</span>
    <span><i style="background:var(--media)"></i>Landing / media / SEO</span>
    <span><i style="background:var(--inv)"></i>Inventario</span>
    <span><i style="background:var(--dead)"></i>Columna muerta (FK huérfana)</span>
  </div>

  {{-- ── B) Operaciones documentadas — EN VIVO desde #[Documentado] ── --}}
  <h2><span class="n">B</span> Operaciones documentadas <span class="live">● en vivo</span></h2>
  <p class="lead">Actions y Queries con el atributo <span class="inline">#[Documentado]</span>, escaneadas por reflexión. Es la lógica de negocio registrada en el sistema, agrupada por su grupo.</p>
  @php($ops = $this->getOperacionesData())
  @forelse ($ops as $grupo => $items)
    <div class="grp">{{ $grupo }}</div>
    <div class="flows">
      @foreach ($items as $op)
        <div class="flow ok">
          <div class="src">{{ $op['clase'] }}<small>{{ $op['archivo'] }}</small></div>
          <div class="arrow">→</div>
          <div class="dst">{{ $op['descripcion'] }}</div>
          <div class="badge {{ $op['tipo'] === 'action' ? 'b-action' : 'b-query' }}">{{ $op['tipo'] }}</div>
        </div>
      @endforeach
    </div>
  @empty
    <p class="note">Aún no hay clases con <span class="inline">#[Documentado]</span> en el contexto escaneado.</p>
  @endforelse

  {{-- ── ! Errores detectados — diagnóstico original (fijo) ── --}}
  <h2><span class="n">!</span> Errores detectados</h2>
  <div class="errbox">
    <ol>
      <li><b>Tabla ancha no normalizada.</b> <code>store_products</code> mezcla contextos (general, precio/estrategia, landing/media, SEO, inventario) en una sola tabla. Cargar la landing arrastra precio y stock que no necesita → consultas más pesadas. <span class="k">Es el mismo problema que tenía product_designs, pero del otro lado.</span></li>
      <li><b>Columnas muertas.</b> Referencias <code>*_id</code> sin FK real (huérfanas) quedan marcadas en rojo arriba; el modelo ya no las usa pero pueden seguir en el esquema.</li>
      <li><b>Móvil con el flujo viejo.</b> El portal móvil todavía crea <code>product_designs</code> con fórmula e insumos, no un producto en <code>store_products</code>. No es "un solo flujo".</li>
      <li><b>Precio/estrategia mezclados con la ficha.</b> <code>precio_distribuidor</code>, <code>cantidad_minima_distribuidor</code> son estrategia comercial, hoy pegados al producto general.</li>
    </ol>
  </div>

  {{-- ── C) Modelo propuesto (fijo) ── --}}
  <h2><span class="n">C</span> Modelo propuesto — un solo flujo, normalizado</h2>
  <p class="lead">Tabla principal <b>angosta</b> con detalles por contexto colgando. Cada consulta va solo a lo que necesita. Un único flujo de escritura (Módulo Producto / Tienda / Móvil) y de lectura (web / landing / portal).</p>
  <div class="scroll target">
    <div class="row">
      <div class="card">
        <h3>products <span class="tag">núcleo</span></h3>
        <ul class="cols">
          <li class="g-genr">id · empresa_id</li>
          <li class="g-genr">nombre · slug</li>
          <li class="g-genr">category_id <span class="k">→ FK</span></li>
          <li class="g-genr">publicado · destacado · orden</li>
        </ul>
      </div>
      <div class="card">
        <h3>product_categories <span class="tag">categoría</span></h3>
        <ul class="cols"><li class="g-genr">id · empresa_id · nombre · slug</li></ul>
        <p class="note" style="padding:0 14px 14px">= store_categories, renombrada/normalizada.</p>
      </div>
      <div class="card">
        <h3>product_media <span class="tag">landing</span></h3>
        <ul class="cols">
          <li class="g-media">product_id <span class="k">→ FK</span></li>
          <li class="g-media">imagen_principal · galeria</li>
          <li class="g-media">descripcion · caracteristicas</li>
          <li class="g-media">meta_titulo · meta_descripcion</li>
        </ul>
      </div>
      <div class="card">
        <h3>product_pricing <span class="tag">precio</span></h3>
        <ul class="cols">
          <li class="g-price">product_id <span class="k">→ FK</span></li>
          <li class="g-price">precio_venta · unidad_precio</li>
          <li class="g-price">precio_distribuidor · cant_min</li>
        </ul>
      </div>
      <div class="card" style="opacity:.7;border-style:dashed">
        <h3>product_costs <span class="tag">luego · Python</span></h3>
        <ul class="cols">
          <li class="g-inv">product_id <span class="k">→ FK</span></li>
          <li class="g-inv">costos · ROI · escenarios</li>
        </ul>
        <p class="note" style="padding:0 14px 14px">El ERP captura, el micro Python procesa. Se define en su fase.</p>
      </div>
    </div>
  </div>
  <p class="note"><span class="pill">Regla</span> &nbsp;La landing consulta <b>products + product_media</b>; el checkout consulta <b>products + product_pricing</b>. Nadie carga costos si no los necesita.</p>

  <footer>Diagrama basado en el esquema real (PostgreSQL), leído en tiempo real. Producción y su tabla <code>product_designs</code> se tratan en su propio refactor. — Mashaec ERP</footer>
</div>
</div>
</x-filament-panels::page>
