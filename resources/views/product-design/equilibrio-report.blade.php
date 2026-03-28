@php
    $design            = $design ?? $record;
    $isPdf             = $isPdf  ?? false;
    $presNombre        = $presNombre ?? ($simulation?->presentation_nombre ?? '—');
    $otrasSimulaciones = $otrasSimulaciones ?? collect();

    $mes  = ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM [de] YYYY'));
    $f2   = fn($v) => '$ ' . number_format((float) $v, 2);
    $f0   = fn($v) => number_format((float) $v, 0);
    $fp   = fn($v) => number_format((float) $v, 1) . '%';

    $ingresoLote = $pvpSinIva * $cantidad;

    $margenSeguridad = ($peUnidades !== null && $cantidad > 0 && $peUnidades > 0)
        ? round(($cantidad - $peUnidades) / $cantidad * 100, 1)
        : null;

    $utilidadOperativa = $contribucionTotal - $totalFijosMensual;

    // Unidades excedentes o faltantes
    $diffUnidades = $peUnidades !== null ? $cantidad - $peUnidades : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Punto de Equilibrio — {{ $design->nombre }}</title>
<style>
    @page { size: A4 portrait; margin: 20mm 22mm 18mm 22mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 10.5px;
        color: #333;
        background: #fff;
        line-height: 1.55;
        padding: 30px 40px;
        max-width: 210mm;
        margin: 0 auto;
    }
    @media print {
        body { padding: 0; max-width: none; margin: 0; }
    }

    /* ── Header ─── */
    .header { padding-bottom: 12px; margin-bottom: 16px; border-bottom: 2.5px solid #1e3a5f; }
    .header table { width: 100%; border-collapse: collapse; }
    .empresa-name { font-size: 17px; font-weight: 700; color: #1e3a5f; }
    .empresa-id { font-size: 9px; color: #777; margin-top: 1px; }
    .doc-title { font-size: 13px; font-weight: 700; color: #1e3a5f; margin-top: 5px; }
    .doc-meta { font-size: 8.5px; color: #999; text-align: right; line-height: 1.7; }

    /* ── Secciones ─── */
    .section { margin-bottom: 14px; }
    .s-head {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: #1e3a5f;
        border-bottom: 1.5px solid #1e3a5f; padding-bottom: 3px; margin-bottom: 6px;
    }
    .s-desc { font-size: 9.5px; color: #666; margin-bottom: 8px; line-height: 1.5; font-style: italic; }

    /* ── Tabla info ─── */
    .ti { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .ti td { padding: 6px 8px; vertical-align: top; border: 1px solid #e0e0e0; background: #fafbfc; }
    .ti .lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; color: #999; display: block; margin-bottom: 1px; }
    .ti .val { font-size: 12px; font-weight: 700; color: #1e3a5f; }
    .ti .val-sm { font-size: 10px; font-weight: 600; color: #333; }

    /* ── Tabla datos ─── */
    .tbl { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .tbl th {
        background: #1e3a5f; color: #fff; padding: 5px 8px;
        font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.4px; text-align: left;
    }
    .tbl th.r { text-align: right; }
    .tbl td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10.5px; }
    .tbl td.r { text-align: right; font-family: 'Courier New', monospace; font-size: 10px; }
    .tbl tr:nth-child(even) td { background: #fafbfc; }
    .tbl tr.total td { border-top: 2px solid #1e3a5f; font-weight: 700; background: #edf2f7; }
    .tbl td.green { color: #0d7a3e; }
    .tbl td.red { color: #c0392b; }
    .tbl td.muted { color: #999; font-size: 9px; font-style: italic; }

    /* ── Resultado PE (destacado) ─── */
    .pe-box { border: 2px solid #1e3a5f; padding: 12px; margin: 10px 0; background: #f7f9fc; }
    .pe-box table { width: 100%; border-collapse: collapse; }
    .pe-lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #777; }
    .pe-val { font-size: 20px; font-weight: 800; color: #1e3a5f; }
    .pe-sub { font-size: 8.5px; color: #aaa; margin-top: 2px; }

    /* ── Caja explicativa ─── */
    .explain {
        background: #f0f4f8; border: 1px solid #d0d8e0; border-radius: 4px;
        padding: 7px 10px; margin: 6px 0; font-size: 9.5px; color: #555; line-height: 1.5;
    }
    .explain strong { color: #1e3a5f; }

    /* ── Barra ─── */
    .bar-wrap { margin: 8px 0; }
    .bar-labels table { width: 100%; border-collapse: collapse; }
    .bar-labels td { font-size: 8px; color: #999; }
    .bar-outer { width: 100%; height: 20px; background: #e8ecef; overflow: hidden; }
    .bar-inner { height: 20px; font-size: 9px; font-weight: 700; color: #fff; text-align: center; line-height: 20px; }

    /* ── Diagnóstico ─── */
    .diag {
        padding: 10px 12px; border-left: 4px solid; margin: 8px 0;
        font-size: 10.5px; line-height: 1.6;
    }
    .diag-ok { border-color: #0d7a3e; background: #edf7f0; color: #1a5e32; }
    .diag-warn { border-color: #d4a017; background: #fdf8e8; color: #7a5e0a; }
    .diag-bad { border-color: #c0392b; background: #fdecea; color: #7a1b1b; }

    /* ── Fórmula ─── */
    .formula {
        text-align: center; padding: 8px 10px; background: #f7f9fc;
        border: 1px solid #d0d8e0; margin: 8px 0;
    }
    .formula .fx-label { font-size: 8.5px; color: #777; margin-bottom: 3px; }
    .formula .fx {
        font-family: 'Courier New', monospace; font-size: 12px;
        font-weight: 700; color: #1e3a5f;
    }
    .formula .fx .result { color: #0d7a3e; font-size: 13px; }

    /* ── Nota ─── */
    .nota { font-size: 8.5px; color: #aaa; margin-top: 12px; padding-top: 6px; border-top: 1px dashed #ddd; line-height: 1.6; }

    /* ── Footer ─── */
    .footer { margin-top: 14px; border-top: 2px solid #1e3a5f; padding-top: 5px; }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer td { font-size: 8px; color: #999; }

    /* ── Botones ─── */
    .action-bar { margin-bottom: 16px; }
    .btn {
        display: inline-block; padding: 7px 18px; font-size: 12px; font-weight: 600;
        text-decoration: none; border-radius: 5px; cursor: pointer; border: none;
    }
    .btn-primary { background: #1e3a5f; color: #fff; }
    .btn-secondary { background: #fff; color: #444; border: 1px solid #ccc; margin-left: 6px; }
    @media print { .action-bar { display: none !important; } }
</style>
</head>
<body>

@if(!$isPdf)
<div class="action-bar">
    <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}" class="btn btn-primary">Descargar PDF</a>
    <button onclick="window.print()" class="btn btn-secondary">Imprimir</button>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- ENCABEZADO --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="header">
    <table>
        <tr>
            <td style="vertical-align: bottom;">
                <div class="empresa-name">{{ $empresa->name }}</div>
                @if($empresa->numero_identificacion)
                <div class="empresa-id">RUC: {{ $empresa->numero_identificacion }}</div>
                @endif
                <div class="doc-title">Análisis de Punto de Equilibrio</div>
            </td>
            <td class="doc-meta" style="vertical-align: bottom; width: 170px;">
                Fecha: {{ now()->format('d/m/Y') }}<br>
                Periodo: {{ $mes }}<br>
                @if($simulation)
                Simulación: {{ $simulation->nombre }}<br>
                Estado: {{ ucfirst($simulation->estado) }}
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 1. PRODUCTO ANALIZADO --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="s-head">1. Producto Analizado</div>
    <table class="ti">
        <tr>
            <td style="width:35%;">
                <span class="lbl">Producto</span>
                <span class="val">{{ $design->nombre }}</span>
            </td>
            <td style="width:25%;">
                <span class="lbl">Presentación</span>
                <span class="val-sm">{{ $presNombre }}</span>
            </td>
            <td style="width:20%;">
                <span class="lbl">Cantidad a Producir</span>
                <span class="val">{{ $f0($cantidad) }} u.</span>
            </td>
            <td style="width:20%;">
                <span class="lbl">Precio de Venta</span>
                <span class="val">{{ $f2($pvpSinIva) }}</span>
                <span style="font-size:8px; color:#999;">sin IVA por unidad</span>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 2. ¿CUÁNTO CUESTA PRODUCIR? --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="s-head">2. ¿Cuánto cuesta producir?</div>
    <div class="s-desc">Para calcular el punto de equilibrio, necesitamos separar los costos en dos tipos: los que cambian según cuánto produzcamos (variables) y los que se pagan siempre, sin importar la producción (fijos).</div>

    <table class="tbl">
        <thead>
            <tr>
                <th style="width:55%;">Tipo de Costo</th>
                <th class="r">Total del Lote</th>
                <th class="r">Por Unidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Costos Variables</strong>
                    <br><span style="font-size:9px; color:#888;">Materiales + mano de obra + costos indirectos del producto</span>
                </td>
                <td class="r">{{ $f2($costoVariable) }}</td>
                <td class="r">{{ $f2($costoVarUnit) }}</td>
            </tr>
            <tr>
                <td>
                    <strong>Costos Fijos Mensuales</strong>
                    <br><span style="font-size:9px; color:#888;">{{ $costosFijos->count() }} rubros: {{ $costosFijos->pluck('nombre')->implode(', ') }}</span>
                </td>
                <td class="r">{{ $f2($totalFijosMensual) }}</td>
                <td class="r muted">no aplica</td>
            </tr>
        </tbody>
    </table>

    <div class="explain">
        <strong>Costos variables</strong> son los que suben o bajan según la cantidad producida (materiales, mano de obra, etc.).
        <strong>Costos fijos</strong> son los que se pagan todos los meses sin importar si se produce o no (arriendo, servicios, etc.).
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 3. ¿CUÁNTO GANAMOS POR CADA UNIDAD VENDIDA? --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="s-head">3. ¿Cuánto aporta cada unidad vendida?</div>
    <div class="s-desc">El margen de contribución es lo que queda de cada venta después de cubrir los costos variables. Este dinero es el que se usa para pagar los costos fijos.</div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="r">Por Unidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Precio de Venta (sin IVA)</td>
                <td class="r">{{ $f2($pvpSinIva) }}</td>
            </tr>
            <tr>
                <td>( – ) Costo Variable por unidad</td>
                <td class="r">{{ $f2($costoVarUnit) }}</td>
            </tr>
        </tbody>
        <tr class="total">
            <td>( = ) Margen de Contribución por unidad</td>
            <td class="r {{ $contribucionUnit >= 0 ? 'green' : 'red' }}">{{ $f2($contribucionUnit) }}</td>
        </tr>
    </table>

    @if($contribucionUnit > 0)
    <div class="explain">
        Por cada unidad vendida a {{ $f2($pvpSinIva) }}, quedan <strong>{{ $f2($contribucionUnit) }}</strong> disponibles para cubrir los costos fijos de la empresa.
        @if($pvpSinIva > 0)
        Esto equivale al <strong>{{ $fp($contribucionUnit / $pvpSinIva * 100) }}</strong> del precio de venta.
        @endif
    </div>
    @elseif($contribucionUnit <= 0)
    <div class="explain" style="background:#fdecea; border-color:#e74c3c;">
        <strong>Alerta:</strong> El precio de venta no alcanza a cubrir ni siquiera los costos variables de producción. Es necesario aumentar el precio o reducir costos antes de continuar con el análisis.
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 4. PUNTO DE EQUILIBRIO --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="s-head">4. Punto de Equilibrio: ¿Cuánto necesitamos vender?</div>
    <div class="s-desc">El punto de equilibrio es la cantidad mínima de unidades que debemos vender para cubrir todos los costos fijos. Por debajo de este número, la empresa pierde dinero. Por encima, comienza a generar ganancia.</div>

    @if($contribucionUnit > 0)
    <div class="formula">
        <div class="fx-label">Fórmula:</div>
        <div class="fx">
            PE = Costos Fijos &divide; Margen por unidad = {{ $f2($totalFijosMensual) }} &divide; {{ $f2($contribucionUnit) }} = <span class="result">{{ $f0($peUnidades) }} unidades</span>
        </div>
    </div>
    @endif

    <div class="pe-box">
        <table>
            <tr>
                <td style="width:33%; text-align:center; border-right:1px solid #ddd; padding:8px;">
                    <div class="pe-lbl">Hay que vender al menos</div>
                    <div class="pe-val">{{ $peUnidades !== null ? $f0($peUnidades) : '—' }}</div>
                    <div class="pe-sub">unidades por mes</div>
                </td>
                <td style="width:33%; text-align:center; border-right:1px solid #ddd; padding:8px;">
                    <div class="pe-lbl">Equivalente en ventas</div>
                    <div class="pe-val">{{ $peMonetario !== null ? $f2($peMonetario) : '—' }}</div>
                    <div class="pe-sub">ingresos sin IVA</div>
                </td>
                <td style="width:34%; text-align:center; padding:8px;">
                    <div class="pe-lbl">Producción planificada</div>
                    <div class="pe-val" style="color:{{ $coberturaOp !== null && $coberturaOp >= 100 ? '#0d7a3e' : '#c0392b' }};">
                        {{ $f0($cantidad) }} u.
                    </div>
                    <div class="pe-sub">
                        @if($diffUnidades !== null)
                            @if($diffUnidades >= 0)
                                {{ $f0($diffUnidades) }} unidades por encima del PE
                            @else
                                faltan {{ $f0(abs($diffUnidades)) }} unidades
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Barra visual --}}
    @if($coberturaOp !== null)
    @php $bW = min((float)$coberturaOp, 100); $bC = $coberturaOp >= 100 ? '#0d7a3e' : ($coberturaOp >= 50 ? '#d4a017' : '#c0392b'); @endphp
    <div class="bar-wrap">
        <div class="bar-labels">
            <table>
                <tr>
                    <td>0 unidades</td>
                    <td style="text-align:center; font-weight:600; color:#1e3a5f;">PE = {{ $f0($peUnidades) }} u.</td>
                    <td style="text-align:right;">Meta: {{ $f0($cantidad) }} u.</td>
                </tr>
            </table>
        </div>
        <div class="bar-outer">
            <div class="bar-inner" style="width:{{ $bW }}%; background:{{ $bC }};">
                @if($bW > 15) {{ $fp($coberturaOp) }} @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Tabla de indicadores --}}
    <table class="tbl" style="margin-top:8px;">
        <thead>
            <tr>
                <th>Indicador</th>
                <th class="r">Valor</th>
                <th>¿Qué significa?</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Cobertura del PE</td>
                <td class="r {{ $coberturaOp !== null && $coberturaOp >= 100 ? 'green' : 'red' }}">
                    <strong>{{ $coberturaOp !== null ? $fp($coberturaOp) : '—' }}</strong>
                </td>
                <td class="muted">
                    @if($coberturaOp !== null && $coberturaOp >= 100)
                        La producción supera el mínimo necesario
                    @elseif($coberturaOp !== null)
                        La producción no alcanza el mínimo necesario
                    @else
                        No se puede calcular
                    @endif
                </td>
            </tr>
            <tr>
                <td>Margen de Seguridad</td>
                <td class="r {{ $margenSeguridad !== null && $margenSeguridad > 0 ? 'green' : 'red' }}">
                    <strong>{{ $margenSeguridad !== null ? $fp($margenSeguridad) : '—' }}</strong>
                </td>
                <td class="muted">
                    @if($margenSeguridad !== null && $margenSeguridad > 0)
                        Las ventas pueden caer hasta un {{ $fp($margenSeguridad) }} sin generar pérdidas
                    @else
                        No hay margen, cualquier baja genera pérdidas
                    @endif
                </td>
            </tr>
            <tr>
                <td>Contribución Total</td>
                <td class="r">{{ $f2($contribucionTotal) }}</td>
                <td class="muted">Lo que aportan las {{ $f0($cantidad) }} u. para cubrir costos fijos</td>
            </tr>
            <tr>
                <td><strong>Ganancia / Pérdida Operativa</strong></td>
                <td class="r {{ $utilidadOperativa >= 0 ? 'green' : 'red' }}"><strong>{{ $f2($utilidadOperativa) }}</strong></td>
                <td class="muted">
                    @if($utilidadOperativa >= 0)
                        Ganancia después de cubrir todos los costos fijos
                    @else
                        Pérdida: la producción no cubre los costos fijos
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- 5. COMPROMISOS CON DEUDAS (si aplica) --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($servicioDeudasMes > 0)
<div class="section">
    <div class="s-head">5. ¿Y si incluimos las deudas? <span style="font-weight:400; text-transform:none; letter-spacing:0; color:#666;">({{ $mes }})</span></div>
    <div class="s-desc">Además de los costos fijos, la empresa tiene cuotas de deuda que pagar este mes. Si queremos cubrir todo con las ventas de este producto, necesitamos vender más.</div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Compromiso del Mes</th>
                <th class="r">Monto</th>
                <th class="r">% del Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Costos Fijos</td>
                <td class="r">{{ $f2($totalFijosMensual) }}</td>
                <td class="r">{{ $totalCompromisos > 0 ? $fp($totalFijosMensual / $totalCompromisos * 100) : '—' }}</td>
            </tr>
            <tr>
                <td>Cuotas de Deuda</td>
                <td class="r">{{ $f2($servicioDeudasMes) }}</td>
                <td class="r">{{ $totalCompromisos > 0 ? $fp($servicioDeudasMes / $totalCompromisos * 100) : '—' }}</td>
            </tr>
        </tbody>
        <tr class="total">
            <td>Total a Cubrir este Mes</td>
            <td class="r">{{ $f2($totalCompromisos) }}</td>
            <td class="r">100.0%</td>
        </tr>
    </table>

    @if($cuotasMes->isNotEmpty())
    <table class="tbl" style="margin-top:6px;">
        <thead>
            <tr>
                <th>Deuda</th>
                <th>Acreedor</th>
                <th class="r">Vencimiento</th>
                <th class="r">Cuota</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuotasMes as $cuota)
            <tr>
                <td>{{ $cuota->debt->numero ?? '—' }}</td>
                <td>{{ $cuota->debt->acreedor ?? '—' }}</td>
                <td class="r">{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td class="r">{{ $f2($cuota->total_cuota) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($contribucionUnit > 0)
    <div class="formula" style="margin-top:8px;">
        <div class="fx-label">PE Total (fijos + deudas):</div>
        <div class="fx">
            {{ $f2($totalCompromisos) }} &divide; {{ $f2($contribucionUnit) }} = <span class="result" style="color:#d4a017;">{{ $peTotalUnidades !== null ? $f0($peTotalUnidades) . ' unidades' : 'N/A' }}</span>
        </div>
    </div>
    @endif

    <div class="pe-box" style="border-color:#d4a017; background:#fefcf3;">
        <table>
            <tr>
                <td style="width:33%; text-align:center; border-right:1px solid #e0d8c0; padding:8px;">
                    <div class="pe-lbl">Hay que vender al menos</div>
                    <div class="pe-val" style="color:#b8860b;">{{ $peTotalUnidades !== null ? $f0($peTotalUnidades) : '—' }}</div>
                    <div class="pe-sub">para cubrir fijos + deudas</div>
                </td>
                <td style="width:33%; text-align:center; border-right:1px solid #e0d8c0; padding:8px;">
                    <div class="pe-lbl">Equivalente en ventas</div>
                    <div class="pe-val" style="color:#b8860b;">{{ $peTotalMonetario !== null ? $f2($peTotalMonetario) : '—' }}</div>
                    <div class="pe-sub">ingresos sin IVA</div>
                </td>
                <td style="width:34%; text-align:center; padding:8px;">
                    <div class="pe-lbl">Cobertura de la producción</div>
                    <div class="pe-val" style="color:{{ $coberturaTotal !== null && $coberturaTotal >= 100 ? '#0d7a3e' : '#c0392b' }};">
                        {{ $coberturaTotal !== null ? $fp($coberturaTotal) : '—' }}
                    </div>
                    <div class="pe-sub">
                        @if($peTotalUnidades !== null && $cantidad >= $peTotalUnidades)
                            Cubre todos los compromisos
                        @elseif($peTotalUnidades !== null)
                            Faltan {{ $f0($peTotalUnidades - $cantidad) }} u.
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        @if($coberturaTotal !== null)
        @php $bW2 = min((float)$coberturaTotal, 100); $bC2 = $coberturaTotal >= 100 ? '#0d7a3e' : ($coberturaTotal >= 50 ? '#d4a017' : '#c0392b'); @endphp
        <div style="margin-top:8px;">
            <div class="bar-outer">
                <div class="bar-inner" style="width:{{ $bW2 }}%; background:{{ $bC2 }};">
                    @if($bW2 > 15) {{ $fp($coberturaTotal) }} @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- CANAL DISTRIBUIDOR (si aplica) --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($pvpDist > 0)
<div class="section">
    <div class="s-head">{{ $servicioDeudasMes > 0 ? '6' : '5' }}. ¿Y si vendemos a distribuidores?</div>
    <div class="s-desc">Vender a distribuidores implica un precio menor. Esto cambia el margen de contribución y, por lo tanto, el punto de equilibrio.</div>

    <table class="tbl">
        <thead>
            <tr>
                <th>Indicador</th>
                <th class="r">Venta Directa</th>
                <th class="r">Distribuidor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Precio de Venta</td>
                <td class="r">{{ $f2($pvpSinIva) }}</td>
                <td class="r">{{ $f2($pvpDist) }}</td>
            </tr>
            <tr>
                <td>Costo Variable / unidad</td>
                <td class="r">{{ $f2($costoVarUnit) }}</td>
                <td class="r">{{ $f2($costoVarUnit) }}</td>
            </tr>
            <tr>
                <td>Margen de Contribución / unidad</td>
                <td class="r green">{{ $f2($contribucionUnit) }}</td>
                <td class="r {{ $contribDistUnit !== null && $contribDistUnit >= 0 ? 'green' : 'red' }}">{{ $contribDistUnit !== null ? $f2($contribDistUnit) : '—' }}</td>
            </tr>
            <tr class="total">
                <td>Punto de Equilibrio</td>
                <td class="r">{{ $peUnidades !== null ? $f0($peUnidades) . ' u.' : '—' }}</td>
                <td class="r">{{ $peDist !== null ? $f0($peDist) . ' u.' : '—' }}</td>
            </tr>
        </tbody>
    </table>

    @if($peDist !== null && $peUnidades !== null && $peDist > $peUnidades)
    <div class="explain">
        Vendiendo a distribuidores se necesitan <strong>{{ $f0($peDist - $peUnidades) }} unidades más</strong> para alcanzar el punto de equilibrio, debido al menor precio de venta.
    </div>
    @endif
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- CONCLUSIÓN --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="section">
    @php $secConclusion = $servicioDeudasMes > 0 ? ($pvpDist > 0 ? '7' : '6') : ($pvpDist > 0 ? '6' : '5'); @endphp
    <div class="s-head">{{ $secConclusion }}. Conclusión</div>

    @if($contribucionUnit <= 0)
    <div class="diag diag-bad">
        <strong>No es viable con esta estructura.</strong> El precio de venta ({{ $f2($pvpSinIva) }}) no alcanza a cubrir los costos variables por unidad ({{ $f2($costoVarUnit) }}). Cada unidad vendida genera una pérdida de {{ $f2(abs($contribucionUnit)) }}. Es necesario subir el precio o reducir costos de producción.
    </div>

    @elseif($coberturaOp !== null && $coberturaOp >= 100)
    <div class="diag diag-ok">
        <strong>La producción es rentable.</strong> Con {{ $f0($cantidad) }} unidades se supera el punto de equilibrio de {{ $f0($peUnidades) }} unidades. Esto significa que después de cubrir todos los costos fijos, este lote genera una ganancia operativa estimada de <strong>{{ $f2($utilidadOperativa) }}</strong>.
        @if($margenSeguridad !== null && $margenSeguridad > 0)
        <br>Las ventas podrían bajar hasta un <strong>{{ $fp($margenSeguridad) }}</strong> antes de que la empresa empiece a perder dinero con este producto.
        @endif
    </div>

    @else
    <div class="diag diag-warn">
        <strong>La producción no alcanza el punto de equilibrio.</strong> Se necesitan {{ $f0($peUnidades) }} unidades para cubrir los costos fijos, pero solo se planifican {{ $f0($cantidad) }}. Faltan <strong>{{ $f0($peUnidades - $cantidad) }} unidades</strong>. Opciones: producir más, subir el precio, o reducir costos fijos.
    </div>
    @endif

    @if($servicioDeudasMes > 0 && $coberturaTotal !== null && $contribucionUnit > 0)
        @if($coberturaTotal >= 100)
        <div class="diag diag-ok" style="margin-top:4px;">
            Incluyendo las cuotas de deuda ({{ $f2($servicioDeudasMes) }}), la producción de {{ $f0($cantidad) }} unidades aún cubre el <strong>{{ $fp($coberturaTotal) }}</strong> de todos los compromisos financieros del mes.
        </div>
        @else
        <div class="diag diag-warn" style="margin-top:4px;">
            Si se incluyen las cuotas de deuda ({{ $f2($servicioDeudasMes) }}), la producción solo cubre el <strong>{{ $fp($coberturaTotal) }}</strong> de los compromisos totales. Se necesitarían <strong>{{ $f0($peTotalUnidades) }} unidades</strong> en total para cubrir todo.
        </div>
        @endif
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- NOTA --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="nota">
    <strong>Documento de respaldo.</strong> Cálculos basados en costos fijos vigentes al {{ now()->format('d/m/Y') }}@if($simulation), simulación "{{ $simulation->nombre }}" registrada el {{ $simulation->updated_at->format('d/m/Y') }}@endif.
    @if($servicioDeudasMes > 0) Compromisos de deuda correspondientes a {{ $mes }}.@endif
    Los resultados son estimaciones sujetas a variaciones en las condiciones del mercado y la operación.
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- PIE DE PÁGINA --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="footer">
    <table>
        <tr>
            <td>{{ $empresa->name }}</td>
            <td style="text-align:center;">Punto de Equilibrio — {{ $design->nombre }}</td>
            <td style="text-align:right;">{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
