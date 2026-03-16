<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Flujo de Efectivo - {{ $empresa->name ?? 'Empresa' }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa->name ?? 'Nombre de la Empresa' }}</h1>
        <p>SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS</p>
        <p>ESTADO DE FLUJO DE EFECTIVO</p>
        <p>Por el período terminado al {{ date('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr class="title-row"><th colspan="2">FLUJOS DE EFECTIVO DE LAS ACTIVIDADES DE OPERACIÓN</th></tr>
        </thead>
        <tbody>
            <tr><td colspan="2" style="text-align:center; padding: 20px; color: #666; font-style: italic;">Sin datos de operaciones registrados.</td></tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>Flujos netos de efectivo (utilizados en) procedentes de actividades de operación</td>
                <td class="text-right">$0.00</td>
            </tr>
        </tfoot>
    </table>

    <br>

    <table>
        <thead>
            <tr class="title-row"><th colspan="2">FLUJOS DE EFECTIVO DE LAS ACTIVIDADES DE INVERSIÓN</th></tr>
        </thead>
        <tbody>
            <tr><td colspan="2" style="text-align:center; padding: 20px; color: #666; font-style: italic;">Sin datos de inversiones registrados.</td></tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>Flujos netos de efectivo (utilizados en) procedentes de actividades de inversión</td>
                <td class="text-right">$0.00</td>
            </tr>
        </tfoot>
    </table>

    <br>

    <table>
        <thead>
            <tr class="title-row"><th colspan="2">FLUJOS DE EFECTIVO DE LAS ACTIVIDADES DE FINANCIACIÓN</th></tr>
        </thead>
        <tbody>
            <tr><td colspan="2" style="text-align:center; padding: 20px; color: #666; font-style: italic;">Sin datos de financiación registrados.</td></tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>Flujos netos de efectivo (utilizados en) procedentes de actividades de financiación</td>
                <td class="text-right">$0.00</td>
            </tr>
        </tfoot>
    </table>
    
    <br>

    <table>
        <tr>
            <td>Incremento (Disminución) Neto de Efectivo y Equivalentes al Efectivo</td>
            <td class="text-right font-bold">$0.00</td>
        </tr>
        <tr>
            <td>Efectivo y Equivalentes al Efectivo al Principio del Ejercicio</td>
            <td class="text-right font-bold">$0.00</td>
        </tr>
        <tr class="total-row">
            <td>EFECTIVO Y EQUIVALENTES DE EFECTIVO AL FINAL DEL EJERCICIO</td>
            <td class="text-right font-bold">$0.00</td>
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
