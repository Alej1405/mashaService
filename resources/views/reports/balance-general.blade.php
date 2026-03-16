<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Balance General - {{ $empresa->name ?? 'Empresa' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .title-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa->name ?? 'Nombre de la Empresa' }}</h1>
        <p>SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS</p>
        <p>ESTADO DE SITUACIÓN FINANCIERA (BALANCE GENERAL)</p>
        <p>Al {{ date('d/m/Y') }}</p>
    </div>

    <!-- ACTIVOS -->
    <table>
        <thead>
            <tr class="title-row">
                <th colspan="3">1. ACTIVOS</th>
            </tr>
            <tr>
                <th>Código</th>
                <th>Cuenta</th>
                <th class="text-right">Saldo ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activos as $cuenta)
            <tr>
                <td>{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center;">No hay activos registrados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL ACTIVOS</td>
                <td class="text-right">${{ number_format($activos->sum('saldo'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>

    <!-- PASIVOS -->
    <table>
        <thead>
            <tr class="title-row">
                <th colspan="3">2. PASIVOS</th>
            </tr>
            <tr>
                <th>Código</th>
                <th>Cuenta</th>
                <th class="text-right">Saldo ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pasivos as $cuenta)
            <tr>
                <td>{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center;">No hay pasivos registrados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL PASIVOS</td>
                <td class="text-right">${{ number_format($pasivos->sum('saldo'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>

    <!-- PATRIMONIO -->
    <table>
        <thead>
            <tr class="title-row">
                <th colspan="3">3. PATRIMONIO</th>
            </tr>
            <tr>
                <th>Código</th>
                <th>Cuenta</th>
                <th class="text-right">Saldo ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patrimonio as $cuenta)
            <tr>
                <td>{{ $cuenta->code }}</td>
                <td>{{ $cuenta->name }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center;">No hay patrimonio registrado.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL PATRIMONIO</td>
                <td class="text-right">${{ number_format($patrimonio->sum('saldo'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <br>
    
    <!-- RESUMEN -->
    <table>
        <tr class="total-row">
            <td colspan="2">TOTAL PASIVO Y PATRIMONIO</td>
            <td class="text-right">${{ number_format($pasivos->sum('saldo') + $patrimonio->sum('saldo'), 2) }}</td>
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
