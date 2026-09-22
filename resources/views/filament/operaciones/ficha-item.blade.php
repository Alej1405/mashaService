<x-filament-panels::page>
@php
    $n = fn ($v, $d = 2) => rtrim(rtrim(number_format((float) $v, $d, ',', '.'), '0'), ',');
@endphp

{{-- Misma estructura que la ficha del archivo de Figma: identidad, cuatro
     datos, kardex valorado y el efecto sobre el costo del lote. --}}
<style>
    .fi-op { --op-surface:#fff; --op-surface-2:#f8fafc; --op-surface-3:#f1f5f9;
             --op-border:#e2e8f0; --op-text:#0f172a; --op-text-2:#1e293b;
             --op-muted:#64748b; --op-muted-2:#94a3b8; --op-warn:#b45309;
             --op-warn-soft:#b4530914; --op-warn-border:#b4530938; --op-accent:#4f46e5;
             --op-accent-soft:#4f46e514; --op-r:14px; }
    .fi-op-sub { font-size:14px; color:var(--op-muted); margin-top:2px; }
    .fi-op-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:18px; }
    .fi-op-card { background:var(--op-surface); border:1px solid var(--op-border);
                  border-radius:var(--op-r); padding:16px 18px; }
    .fi-op-card.is-warn { background:var(--op-warn-soft); border-color:var(--op-warn-border); }
    .fi-op-val { font-size:26px; font-weight:700; letter-spacing:-.8px; line-height:1.05; color:var(--op-text); }
    .fi-op-card.is-warn .fi-op-val { color:var(--op-warn); }
    .fi-op-lbl { font-size:12px; font-weight:700; letter-spacing:.7px; text-transform:uppercase;
                 color:var(--op-muted); margin-top:6px; }
    .fi-op-hint { font-size:12px; color:var(--op-muted-2); margin-top:2px; }
    .fi-op-panel { background:var(--op-surface); border:1px solid var(--op-border);
                   border-radius:var(--op-r); margin-top:18px; overflow:hidden; }
    .fi-op-panel h2 { font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase;
                      color:var(--op-muted); padding:14px 18px; margin:0; }
    .fi-op-tabla { width:100%; border-collapse:collapse; font-size:14px; }
    .fi-op-tabla thead th { font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase;
                            color:var(--op-muted); background:var(--op-surface-3);
                            padding:10px 18px; text-align:left; }
    .fi-op-tabla td { padding:12px 18px; border-top:1px solid var(--op-border); color:var(--op-text-2); }
    .fi-op-tabla .num { text-align:right; font-weight:700; color:var(--op-text); white-space:nowrap; }
    .fi-op-tabla tr:last-child td { background:var(--op-accent-soft); }
    @media (max-width:900px) { .fi-op-cards { grid-template-columns:repeat(2,1fr); } }
</style>

<div class="fi-op">
    <p class="fi-op-sub">
        {{ $item->codigo }} · {{ str_replace('_', ' ', $item->type) }}
        @if($ubicacion) · {{ $ubicacion }} @endif
    </p>

    <div class="fi-op-cards">
        <div class="fi-op-card {{ $bajo ? 'is-warn' : '' }}">
            <p class="fi-op-val">{{ $n($item->stock_actual) }} {{ $unidad }}</p>
            <p class="fi-op-lbl">Stock actual</p>
            <p class="fi-op-hint">
                {{ $bajo ? 'bajo el mínimo de ' . $n($item->stock_minimo) . ' ' . $unidad : 'sobre el mínimo' }}
            </p>
        </div>
        <div class="fi-op-card">
            <p class="fi-op-val">$ {{ number_format($item->costo_promedio, 4, ',', '.') }}</p>
            <p class="fi-op-lbl">Costo promedio</p>
            <p class="fi-op-hint">se recalcula en cada entrada</p>
        </div>
        <div class="fi-op-card">
            <p class="fi-op-val">$ {{ number_format($item->saldo_valorado, 2, ',', '.') }}</p>
            <p class="fi-op-lbl">Valor en bodega</p>
            <p class="fi-op-hint">stock por costo promedio</p>
        </div>
        <div class="fi-op-card">
            <p class="fi-op-val">Promedio</p>
            <p class="fi-op-lbl">Método</p>
            <p class="fi-op-hint">ponderado, NIC 2</p>
        </div>
    </div>

    <div class="fi-op-panel">
        <h2>Kardex</h2>
        <table class="fi-op-tabla">
            <thead>
                <tr>
                    <th>Fecha</th><th>Movimiento</th><th>Documento</th>
                    <th style="text-align:right">Entra</th><th style="text-align:right">Sale</th>
                    <th style="text-align:right">Costo u.</th><th style="text-align:right">Saldo</th>
                    <th style="text-align:right">Promedio</th>
                </tr>
            </thead>
            <tbody>
            @forelse($kardex as $m)
                <tr>
                    <td>{{ $m->date?->format('d M') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $m->motivo ?? $m->type)) }}</td>
                    <td>{{ $m->description ?: '-' }}</td>
                    <td class="num">{{ $m->type === 'entrada' ? $n($m->quantity) . ' ' . $unidad : '-' }}</td>
                    <td class="num">{{ $m->type === 'salida' ? $n($m->quantity) . ' ' . $unidad : '-' }}</td>
                    <td class="num">$ {{ number_format($m->unit_price, 4, ',', '.') }}</td>
                    <td class="num">{{ $m->saldo_cantidad !== null ? $n($m->saldo_cantidad) . ' ' . $unidad : '-' }}</td>
                    <td class="num">{{ $m->costo_promedio !== null ? '$ ' . number_format($m->costo_promedio, 4, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:var(--op-muted);padding:28px">
                    Sin movimientos. El kardex empieza con la primera entrada valorada.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-filament-panels::page>
