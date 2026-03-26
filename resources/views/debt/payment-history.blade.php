<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos - {{ $debt->numero }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 20px; }
        .print-btn { background: #4f46e5; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-bottom: 20px; }
        .print-btn:hover { background: #4338ca; }
        h1 { font-size: 18px; font-weight: bold; }
        h2 { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #111; padding-bottom: 12px; }
        .header-left h1 { font-size: 20px; }
        .header-left p { color: #555; font-size: 12px; margin-top: 2px; }
        .header-right { text-align: right; }
        .header-right .badge { background: #e0e7ff; color: #3730a3; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px; }
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; background: #f8f8f8; padding: 14px; border-radius: 8px; }
        .info-item label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #777; display: block; margin-bottom: 2px; }
        .info-item span { font-weight: bold; font-size: 13px; }
        .info-item.highlight span { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #1e1b4b; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        th.right, td.right { text-align: right; }
        th.center, td.center { text-align: center; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        tr:nth-child(even) td { background: #f9fafb; }
        tr.mora-row td { background: #fef2f2 !important; }
        tfoot td { font-weight: bold; border-top: 2px solid #111; background: #f0f0f0 !important; font-size: 13px; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #1e1b4b; border-left: 3px solid #4f46e5; padding-left: 8px; margin: 16px 0 8px; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; display: flex; justify-content: space-between; font-size: 10px; color: #888; }
        .no-payments { text-align: center; padding: 20px; color: #777; font-style: italic; }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>

<div class="header">
    <div class="header-left">
        <h1>{{ $empresa->name ?? $empresa->nombre ?? 'Empresa' }}</h1>
        <p>Historial de Pagos de Deuda</p>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="header-right">
        <h2>{{ $debt->numero }}</h2>
        <div class="badge">{{ strtoupper($debt->estado) }}</div>
    </div>
</div>

<div class="info-grid">
    <div class="info-item">
        <label>Acreedor</label>
        <span>{{ $debt->acreedor }}</span>
    </div>
    <div class="info-item">
        <label>Tipo</label>
        <span>{{ $debt->tipo_label }}</span>
    </div>
    <div class="info-item">
        <label>Clasificación</label>
        <span>{{ $debt->clasificacion === 'corriente' ? 'Corriente' : 'No Corriente' }}</span>
    </div>
    <div class="info-item">
        <label>Monto Original</label>
        <span>${{ number_format($debt->monto_original, 2) }}</span>
    </div>
    <div class="info-item">
        <label>Tasa de Interés</label>
        <span>{{ number_format($debt->tasa_interes, 2) }}% {{ ucfirst($debt->frecuencia_tasa) }} ({{ ucfirst($debt->tipo_tasa) }})</span>
    </div>
    <div class="info-item">
        <label>Plazo</label>
        <span>{{ $debt->plazo_meses ? $debt->plazo_meses . ' meses' : '—' }} {{ $debt->numero_cuotas ? '/ ' . $debt->numero_cuotas . ' cuotas' : '' }}</span>
    </div>
    <div class="info-item">
        <label>Fecha Inicio</label>
        <span>{{ \Carbon\Carbon::parse($debt->fecha_inicio)->format('d/m/Y') }}</span>
    </div>
    <div class="info-item">
        <label>Fecha Vencimiento</label>
        <span>{{ \Carbon\Carbon::parse($debt->fecha_vencimiento)->format('d/m/Y') }}</span>
    </div>
    <div class="info-item highlight">
        <label>Saldo Pendiente</label>
        <span>${{ number_format($debt->saldo_pendiente, 2) }}</span>
    </div>
</div>

{{-- Historial de Pagos --}}
<p class="section-title">Historial de Pagos</p>

@if($debt->payments->isEmpty())
    <p class="no-payments">No se han registrado pagos para esta deuda.</p>
@else
<table>
    <thead>
        <tr>
            <th>N° Pago</th>
            <th class="center">Cuota</th>
            <th>Fecha</th>
            <th class="right">Capital</th>
            <th class="right">Intereses</th>
            <th class="right">Mora</th>
            <th class="right">Total</th>
            <th>Método</th>
            <th>Cuenta / Caja</th>
        </tr>
    </thead>
    <tbody>
        @php $totalCapital = 0; $totalInteres = 0; $totalMora = 0; $totalPagado = 0; @endphp
        @foreach($debt->payments as $pago)
        @php
            $totalCapital += $pago->monto_capital;
            $totalInteres += $pago->monto_interes;
            $totalMora    += $pago->monto_mora;
            $totalPagado  += $pago->total;
        @endphp
        <tr class="{{ $pago->monto_mora > 0 ? 'mora-row' : '' }}">
            <td><strong>{{ $pago->numero }}</strong></td>
            <td class="center">{{ $pago->numero_cuota ? '#' . $pago->numero_cuota : '—' }}</td>
            <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
            <td class="right">${{ number_format($pago->monto_capital, 2) }}</td>
            <td class="right">${{ number_format($pago->monto_interes, 2) }}</td>
            <td class="right">{{ $pago->monto_mora > 0 ? '$' . number_format($pago->monto_mora, 2) : '—' }}</td>
            <td class="right"><strong>${{ number_format($pago->total, 2) }}</strong></td>
            <td>{{ match($pago->metodo_pago) { 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', default => $pago->metodo_pago } }}</td>
            <td>{{ $pago->bankAccount?->nombre_completo ?? $pago->cashRegister?->nombre ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTALES</td>
            <td class="right">${{ number_format($totalCapital, 2) }}</td>
            <td class="right">${{ number_format($totalInteres, 2) }}</td>
            <td class="right">${{ number_format($totalMora, 2) }}</td>
            <td class="right">${{ number_format($totalPagado, 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div style="display:grid; grid-template-columns: repeat(3,1fr); gap:16px; margin-top:8px;">
    <div style="background:#f0fdf4; border:1px solid #86efac; padding:12px; border-radius:8px;">
        <div style="font-size:10px; color:#16a34a; text-transform:uppercase; font-weight:bold;">Capital Pagado</div>
        <div style="font-size:18px; font-weight:bold; color:#15803d; margin-top:4px;">${{ number_format($totalCapital, 2) }}</div>
    </div>
    <div style="background:#fefce8; border:1px solid #fde047; padding:12px; border-radius:8px;">
        <div style="font-size:10px; color:#ca8a04; text-transform:uppercase; font-weight:bold;">Intereses Pagados</div>
        <div style="font-size:18px; font-weight:bold; color:#854d0e; margin-top:4px;">${{ number_format($totalInteres, 2) }}</div>
    </div>
    <div style="background:#fef2f2; border:1px solid #fca5a5; padding:12px; border-radius:8px;">
        <div style="font-size:10px; color:#dc2626; text-transform:uppercase; font-weight:bold;">Saldo Pendiente</div>
        <div style="font-size:18px; font-weight:bold; color:#991b1b; margin-top:4px;">${{ number_format($debt->saldo_pendiente, 2) }}</div>
    </div>
</div>
@endif

{{-- Tabla de Amortización --}}
@if($debt->amortizationLines->isNotEmpty())
<p class="section-title" style="margin-top:24px;">Tabla de Amortización</p>
<table>
    <thead>
        <tr>
            <th class="center">Cuota</th>
            <th>Vencimiento</th>
            <th class="right">Saldo Inicial</th>
            <th class="right">Capital</th>
            <th class="right">Intereses</th>
            <th class="right">Total Cuota</th>
            <th class="right">Saldo Final</th>
            <th class="center">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($debt->amortizationLines as $linea)
        <tr>
            <td class="center"><strong>#{{ $linea->numero_cuota }}</strong></td>
            <td>{{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->format('d/m/Y') }}</td>
            <td class="right">${{ number_format($linea->saldo_inicial, 2) }}</td>
            <td class="right">${{ number_format($linea->monto_capital, 2) }}</td>
            <td class="right">${{ number_format($linea->monto_interes, 2) }}</td>
            <td class="right"><strong>${{ number_format($linea->total_cuota, 2) }}</strong></td>
            <td class="right">${{ number_format($linea->saldo_final, 2) }}</td>
            <td class="center">{{ strtoupper($linea->estado) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    <span>{{ $empresa->name ?? '' }} | {{ $debt->numero }}</span>
    <span>Documento generado el {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
