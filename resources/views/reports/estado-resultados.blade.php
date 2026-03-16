<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Resultados - {{ $empresa->name ?? 'Empresa' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .title-row { background-color: #e9ecef; font-weight: bold; }
        .total-row { background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #333; }
        .result-row.utilidad { background-color: #d4edda; color: #155724; }
        .result-row.perdida { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa->name ?? 'Nombre de la Empresa' }}</h1>
        <p>SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS</p>
        <p>ESTADO DE RESULTADOS INTEGRALES</p>
        <p>Del 01/01/{{ date('Y') }} al {{ date('d/m/Y') }}</p>
    </div>

    <!-- INGRESOS -->
    <table>
        <thead>
            <tr class="title-row"><th colspan="3">4. INGRESOS</th></tr>
            <tr><th>Código</th><th>Cuenta</th><th class="text-right">Saldo ($)</th></tr>
        </thead>
        <tbody>
            @forelse($ingresos as $cuenta)
            <tr>
                <td>{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;">No hay ingresos registrados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL INGRESOS</td>
                <td class="text-right">${{ number_format($ingresos->sum('saldo'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>

    <!-- COSTOS Y GASTOS -->
    <table>
        <thead>
            <tr class="title-row"><th colspan="3">5. Y 6. COSTOS Y GASTOS</th></tr>
            <tr><th>Código</th><th>Cuenta</th><th class="text-right">Saldo ($)</th></tr>
        </thead>
        <tbody>
            <tr><td colspan="3" class="font-bold">Costos</td></tr>
            @forelse($costos as $cuenta)
            <tr>
                <td style="padding-left: 20px;">{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;">No hay costos registrados.</td></tr>
            @endforelse

            <tr><td colspan="3" class="font-bold">Gastos</td></tr>
            @forelse($gastos as $cuenta)
            <tr>
                <td style="padding-left: 20px;">{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;">No hay gastos registrados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL COSTOS Y GASTOS</td>
                <td class="text-right">${{ number_format($costos->sum('saldo') + $gastos->sum('saldo'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>
    
    <!-- RESULTADO DEL EJERCICIO -->
    @php
        $totalIngresos = $ingresos->sum('saldo');
        $totalEgresos = $costos->sum('saldo') + $gastos->sum('saldo');
        $resultado = $totalIngresos - $totalEgresos;
        $esUtilidad = $resultado >= 0;
    @endphp
    <table>
        <tr class="total-row result-row {{ $esUtilidad ? 'utilidad' : 'perdida' }}">
            <td colspan="2">{{ $esUtilidad ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO' }}</td>
            <td class="text-right">${{ number_format(abs($resultado), 2) }}</td>
        </tr>
    </table>

    <div style="margin-top: 50px; text-align: center;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%; text-align: center;">
                    <hr style="width: 60%; border-color: #333;">
                    <br>Representante Legal
                </td>
                <td style="border: none; width: 50%; text-align: center;">
                    <hr style="width: 60%; border-color: #333;">
                    <br>Contador
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
