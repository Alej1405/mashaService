<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de inventario</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Rollo térmico de 60 × 20 mm. Las medidas van en milímetros reales:
           cualquier valor en px saldría de otro tamaño según la impresora. */
        @page { size: 60mm 20mm; margin: 0; }

        .etiqueta {
            width: 60mm; height: 20mm;
            display: flex; align-items: center; gap: 2mm;
            padding: 1mm 2mm 1mm 1.5mm;
            box-sizing: border-box;
            break-inside: avoid; page-break-inside: avoid;
            font-family: ui-sans-serif, system-ui, sans-serif;
            color: #0f172a; background: #fff;
        }
        .etiqueta .qr { width: 18mm; height: 18mm; flex-shrink: 0; }
        .etiqueta .qr svg { width: 100%; height: 100%; display: block; }
        .etiqueta .datos { min-width: 0; flex: 1; }
        .marca { display: flex; align-items: center; gap: 1mm; }
        .marca img { height: 3.2mm; width: auto; object-fit: contain; }
        .codigo { font-size: 5.5pt; font-weight: 700; letter-spacing: .06em; color: #64748b; text-transform: uppercase; }
        .ubicacion { font-size: 13pt; font-weight: 700; line-height: 1.05; letter-spacing: -.01em; }
        .nombre-grande { font-size: 9pt; line-height: 1.1; max-height: 7mm; overflow: hidden; }
        .sin-ubicar { color: #94a3b8; font-weight: 400; }
        .nombre { font-size: 7pt; font-weight: 700; color: #1e293b; line-height: 1.15;
                  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pie { font-size: 5.5pt; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff; margin: 0; }
            .hoja { display: block; padding: 0; gap: 0; }
            .etiqueta { border: 0; page-break-after: always; }
        }
    </style>
</head>
<body class="bg-slate-100">

<div class="no-imprimir sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-4">
    <h1 class="text-lg font-bold text-slate-900">Etiquetas · {{ $items->count() }} productos</h1>
    <p class="mt-0.5 text-sm text-slate-500">
        Rollo de 60 × 20 mm, una etiqueta por producto. Pégala en la gaveta donde vive.
    </p>
    <div class="mt-3 flex flex-wrap gap-2">
        <button onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
            Imprimir
        </button>
        <a href="{{ route('inventario.etiquetas.pdf', request()->query()) }}"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">
            Descargar PDF
        </a>
        <a href="{{ route('inventario.etiquetas.pdf', array_merge(request()->query(), ['ver' => 1])) }}" target="_blank"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">
            Ver PDF
        </a>
    </div>
</div>

<div class="hoja flex flex-wrap gap-3 p-6">
    @foreach($items as $item)
        @php
            $ubicacion = $item->stocks->firstWhere('cantidad', '>', 0)?->ubicacion
                      ?? $item->stocks->first()?->ubicacion;
            $logo = $item->empresa?->logo_path;
        @endphp
        <div class="etiqueta border border-slate-300">
            <div class="qr">{!! $item->qrSvg(160) !!}</div>
            <div class="datos">
                <div class="marca">
                    {{-- El logo es de la empresa dueña del inventario, no de MashaCorp --}}
                    @if($logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="">
                    @endif
                    <span class="codigo">{{ $item->codigo }}</span>
                </div>
                @if($ubicacion)
                    <p class="ubicacion">{{ $ubicacion->codigo_ubicacion }}</p>
                    <p class="nombre">{{ $item->nombre }}</p>
                @else
                    <p class="ubicacion nombre-grande">{{ $item->nombre }}</p>
                    <p class="nombre sin-ubicar">sin ubicación asignada</p>
                @endif
                <p class="pie">{{ $item->empresa?->name }}</p>
            </div>
        </div>
    @endforeach
</div>

</body>
</html>
