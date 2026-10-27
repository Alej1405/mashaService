<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro diario {{ $periodo }} - {{ $empresa->name }}</title>
    <style>
        @page { margin: 70px 36px 50px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }
        header { position: fixed; top: -52px; left: 0; right: 0; text-align: center; }
        header h1 { margin: 0; font-size: 12px; }
        header p { margin: 1px 0 0; font-size: 8.5px; color: #4b5563; }
        footer { position: fixed; bottom: -34px; left: 0; right: 0; font-size: 7.5px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e3a5f; color: #fff; padding: 4px; text-align: left; font-size: 8px; }
        td { padding: 2px 4px; vertical-align: top; }
        .asiento td { background: #eef2f7; font-weight: bold; border-top: 1px solid #cbd5e1; }
        .subtotal td { border-top: 1px solid #94a3b8; font-weight: bold; }
        .num { text-align: right; white-space: nowrap; }
        .total td { border-top: 2px solid #1f2937; font-weight: bold; font-size: 9.5px; padding-top: 5px; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <header>
        <h1>{{ mb_strtoupper($empresa->name) }}</h1>
        <p>RUC {{ $empresa->numero_identificacion }} · LIBRO DIARIO · {{ $periodo }}</p>
        <p>Expresado en dólares de los Estados Unidos de América</p>
    </header>
    <footer>
        <span>Generado el {{ now()->format('d/m/Y H:i') }}</span>
    </footer>

    @php $money = fn ($n) => number_format((float) $n, 2, ',', '.'); $totalDebe = 0; $totalHaber = 0; @endphp
    <table>
        <thead>
            <tr><th style="width:12%">Asiento</th><th style="width:9%">Fecha</th><th style="width:11%">Código</th><th>Cuenta / detalle</th><th class="num" style="width:11%">Debe</th><th class="num" style="width:11%">Haber</th></tr>
        </thead>
        <tbody>
        @foreach ($asientos as $a)
            <tr class="asiento">
                <td>{{ $a->numero }}</td>
                <td>{{ \Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                <td colspan="4">{{ $a->descripcion }}</td>
            </tr>
            @foreach ($a->lines->sortBy('orden') as $l)
                <tr>
                    <td></td><td></td>
                    <td>{{ $l->accountPlan?->code }}</td>
                    <td>{{ $l->accountPlan?->name }}@if ($l->descripcion) <span style="color:#6b7280"> · {{ $l->descripcion }}</span>@endif</td>
                    <td class="num">{{ (float) $l->debe ? $money($l->debe) : '' }}</td>
                    <td class="num">{{ (float) $l->haber ? $money($l->haber) : '' }}</td>
                </tr>
            @endforeach
            @php $totalDebe += $a->lines->sum('debe'); $totalHaber += $a->lines->sum('haber'); @endphp
            <tr class="subtotal">
                <td colspan="4"></td>
                <td class="num">{{ $money($a->lines->sum('debe')) }}</td>
                <td class="num">{{ $money($a->lines->sum('haber')) }}</td>
            </tr>
        @endforeach
            <tr class="total">
                <td colspan="4">TOTAL DEL PERÍODO · {{ $asientos->count() }} asientos</td>
                <td class="num">{{ $money($totalDebe) }}</td>
                <td class="num">{{ $money($totalHaber) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
