<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    {{-- Dompdf no entiende Tailwind ni hojas externas: todo va aquí, en mm. --}}
    <style>
        @page { size: 60mm 20mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; }

        .etiqueta { width: 60mm; height: 19.4mm; padding: 1.2mm 2mm 1.2mm 1.5mm; overflow: hidden; }
        .etiqueta + .etiqueta { page-break-before: always; }

        table.marco { width: 100%; height: 100%; border-collapse: collapse; }
        td.qr    { width: 17.5mm; vertical-align: middle; }
        td.datos { vertical-align: middle; padding-left: 2mm; }

        .codigo    { font-size: 5pt; color: #64748b; letter-spacing: .04em; }
        .ubicacion { font-size: 13pt; font-weight: bold; line-height: 1.05; }
        .nombre    { font-size: 6.5pt; font-weight: bold; color: #1e293b; line-height: 1.1; }
        .pie       { font-size: 5pt; color: #64748b; }
    </style>
</head>
<body>
@foreach($items as $item)
    @php
        $ubicacion = $item->stocks->firstWhere('cantidad', '>', 0)?->ubicacion
                  ?? $item->stocks->first()?->ubicacion;
        // Nombre a una línea: en 20 mm no cabe más y partido no se lee.
        $nombre = \Illuminate\Support\Str::limit($item->nombre, 30);
    @endphp
    <div class="etiqueta">
        <table class="marco">
            <tr>
                <td class="qr"><img src="{{ $item->qrPng(66) }}" alt="" style="width:17mm;height:17mm"></td>
                <td class="datos">
                    <div class="codigo">{{ $item->codigo }}</div>
                    @if($ubicacion)
                        <div class="ubicacion">{{ $ubicacion->codigo_ubicacion }}</div>
                        <div class="nombre">{{ $nombre }}</div>
                    @else
                        <div class="ubicacion" style="font-size:9pt">{{ $nombre }}</div>
                        <div class="nombre" style="color:#94a3b8;font-weight:normal">sin ubicación asignada</div>
                    @endif
                    <div class="pie">{{ \Illuminate\Support\Str::limit($item->empresa?->name, 34) }}</div>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
