{{-- Vista del historial del embarque --}}
@php
    $record = $getRecord();
    $history = $record
        ? \App\Models\LogisticsShipmentHistory::where('shipment_id', $record->id)
            ->latest()
            ->get()
        : collect();

    $iconos = [
        'cambio_estado' => '🔄',
        'nota'          => '📝',
        'documento'     => '📄',
        'paquete'       => '📦',
        'creacion'      => '✅',
    ];

    $colores = [
        'cambio_estado' => 'blue',
        'nota'          => 'gray',
        'documento'     => 'yellow',
        'paquete'       => 'purple',
        'creacion'      => 'green',
    ];

    $clasesBorde = [
        'blue'   => 'border-blue-500',
        'gray'   => 'border-gray-400',
        'yellow' => 'border-yellow-500',
        'purple' => 'border-purple-500',
        'green'  => 'border-green-500',
    ];

    $clasesBg = [
        'blue'   => 'bg-blue-500',
        'gray'   => 'bg-gray-400',
        'yellow' => 'bg-yellow-500',
        'purple' => 'bg-purple-500',
        'green'  => 'bg-green-500',
    ];
@endphp

@if ($history->isEmpty())
    <div class="text-center py-8 text-gray-400 text-sm">
        Sin eventos registrados aún.
    </div>
@else
    <ol class="relative border-l border-gray-600 ml-3 space-y-6 py-2">
        @foreach ($history as $evento)
            @php
                $color  = $colores[$evento->tipo] ?? 'gray';
                $icono  = $iconos[$evento->tipo] ?? '•';
                $border = $clasesBorde[$color];
                $bg     = $clasesBg[$color];
            @endphp
            <li class="ml-6">
                {{-- Punto del timeline --}}
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full {{ $bg }} ring-4 ring-gray-800 text-xs">
                    {{ $icono }}
                </span>

                <div class="p-3 rounded-lg border {{ $border }} bg-gray-800/50">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm text-gray-100">{{ $evento->descripcion }}</p>
                        <time class="text-xs text-gray-400 whitespace-nowrap">
                            {{ $evento->created_at->format('d/m/Y H:i') }}
                        </time>
                    </div>

                    @if ($evento->estado_anterior && $evento->estado_nuevo)
                        <div class="mt-1 flex items-center gap-2 text-xs">
                            <span class="text-gray-400">
                                {{ \App\Models\LogisticsShipment::ESTADOS[$evento->estado_anterior]['label'] ?? $evento->estado_anterior }}
                            </span>
                            <span class="text-gray-500">→</span>
                            <span class="text-gray-200 font-medium">
                                {{ \App\Models\LogisticsShipment::ESTADOS[$evento->estado_nuevo]['label'] ?? $evento->estado_nuevo }}
                            </span>
                        </div>
                    @endif

                    @if ($evento->user_nombre)
                        <p class="mt-1 text-xs text-gray-500">por {{ $evento->user_nombre }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
