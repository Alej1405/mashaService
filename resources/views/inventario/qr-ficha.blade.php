<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $item->nombre }} — Inventario</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Se abre desde la cámara, en la bodega y con una mano: todo va al alcance
     del pulgar y el dato que se viene a buscar ocupa el primer lugar. --}}
<body class="min-h-screen bg-slate-50 text-slate-800">

@php
    $bajoMinimo = $item->stock_minimo > 0 && $item->stock_actual < $item->stock_minimo;
    $ubicacion  = $item->stocks->firstWhere('cantidad', '>', 0)?->ubicacion;
    $unidad     = $item->measurementUnit->abreviatura ?? $item->measurementUnit->nombre ?? '';
    $tipos      = ['materia_prima' => 'Materia prima', 'insumo' => 'Insumo', 'producto_terminado' => 'Producto terminado', 'activo_fijo' => 'Activo fijo'];
@endphp

<header class="bg-white px-5 pt-[calc(env(safe-area-inset-top)+1.5rem)] pb-4 border-b border-slate-200">
    <span class="inline-block rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        {{ $tipos[$item->type] ?? $item->type }} · {{ $item->codigo }}
    </span>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $item->nombre }}</h1>
</header>

<main class="px-5 py-4 space-y-3">

    <div class="rounded-2xl border p-5 {{ $bajoMinimo ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }}">
        <p class="text-3xl font-bold {{ $bajoMinimo ? 'text-amber-800' : 'text-emerald-800' }}">
            {{ rtrim(rtrim(number_format($item->stock_actual, 2, ',', '.'), '0'), ',') }} {{ $unidad }}
        </p>
        <p class="mt-1 text-sm font-semibold {{ $bajoMinimo ? 'text-amber-800' : 'text-emerald-800' }}">
            @if($bajoMinimo)
                Bajo el mínimo de {{ rtrim(rtrim(number_format($item->stock_minimo, 2, ',', '.'), '0'), ',') }} {{ $unidad }}
            @else
                Sobre el mínimo
            @endif
        </p>
    </div>

    @foreach ([
        'Ubicación'        => $ubicacion?->codigo_ubicacion ?? 'sin asignar',
        'Costo promedio'   => '$ ' . number_format($item->costo_promedio, 4, ',', '.'),
        'Valor en bodega'  => '$ ' . number_format($item->saldo_valorado, 2, ',', '.'),
        'Último movimiento'=> optional($movimientos->first())->date?->format('d/m/Y') ?? 'sin movimientos',
    ] as $etiqueta => $valor)
        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3.5">
            <span class="text-sm text-slate-500">{{ $etiqueta }}</span>
            <span class="text-sm font-bold text-slate-900">{{ $valor }}</span>
        </div>
    @endforeach

    @if($movimientos->isNotEmpty())
        <h2 class="pt-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Últimos movimientos</h2>
        <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
            @foreach($movimientos as $m)
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ ucfirst(str_replace('_', ' ', $m->motivo ?? $m->type)) }}</p>
                        <p class="text-xs text-slate-500">{{ $m->date?->format('d/m/Y') }} · {{ $m->description ?: 'sin documento' }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold {{ $m->type === 'entrada' ? 'text-emerald-700' : 'text-slate-700' }}">
                        {{ $m->type === 'entrada' ? '+' : '-' }}{{ rtrim(rtrim(number_format($m->quantity, 2, ',', '.'), '0'), ',') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</main>

{{-- Acciones fijas abajo: en bodega se usa con una mano --}}
@php
    // Quien escanea en bodega ya sabe qué ítem es: las acciones llegan con el
    // ítem puesto para que solo tenga que escribir la cantidad.
    $panel     = 'operaciones/' . $item->empresa->slug;
    $registrar = url($panel . '/registrar-movimiento') . '?item=' . $item->id;
    $kardex    = url($panel . '/inventarios/' . $item->id);
@endphp
<footer class="sticky bottom-0 space-y-2.5 border-t border-slate-200 bg-white px-5 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)]">
    <a href="{{ $registrar }}&tipo=entrada" class="block rounded-xl bg-indigo-600 py-4 text-center text-sm font-bold text-white active:scale-[0.99] transition-transform">
        Registrar entrada
    </a>
    <div class="grid grid-cols-2 gap-2.5">
        <a href="{{ $registrar }}&tipo=salida" class="rounded-xl border border-slate-300 py-3.5 text-center text-sm font-bold text-slate-700 active:scale-[0.99] transition-transform">Registrar salida</a>
        <a href="{{ $kardex }}" class="rounded-xl border border-slate-300 py-3.5 text-center text-sm font-bold text-slate-700 active:scale-[0.99] transition-transform">Ver kardex</a>
    </div>
</footer>

</body>
</html>
