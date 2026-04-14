@extends('portal.layout')
@section('content')

<div class="space-y-5">

    <h1 class="text-xl font-bold text-gray-800">Mis servicios</h1>

    @if($contracts->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
            <p class="text-gray-500 text-sm">No tienes servicios contratados aún.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($contracts as $contract)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-sm font-semibold text-gray-800">{{ $contract->nombre_servicio }}</h2>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold
                                @if($contract->estado === 'activo') bg-green-100 text-green-700
                                @elseif($contract->estado === 'pausado') bg-amber-100 text-amber-700
                                @else bg-gray-100 text-gray-600
                                @endif">
                                {{ ucfirst($contract->estado) }}
                            </span>
                        </div>
                        @if($contract->descripcion)
                            <p class="text-sm text-gray-600 mt-1">{{ $contract->descripcion }}</p>
                        @endif
                        <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-500">
                            <span><span class="font-medium text-gray-700">Inicio:</span> {{ $contract->fecha_inicio->format('d/m/Y') }}</span>
                            @if($contract->fecha_fin)
                                <span><span class="font-medium text-gray-700">Vencimiento:</span> {{ $contract->fecha_fin->format('d/m/Y') }}</span>
                            @else
                                <span class="text-gray-400">Contrato indefinido</span>
                            @endif
                            @if($contract->periodicidad)
                                <span><span class="font-medium text-gray-700">Periodicidad:</span> {{ ucfirst($contract->periodicidad) }}</span>
                            @endif
                        </div>
                    </div>
                    @if($contract->precio)
                    <div class="text-right shrink-0">
                        <p class="text-lg font-bold text-gray-800">${{ number_format($contract->precio, 2) }}</p>
                        @if($contract->periodicidad)
                            <p class="text-xs text-gray-500">{{ $contract->periodicidad }}</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div>{{ $contracts->links() }}</div>
    @endif

</div>

@endsection
