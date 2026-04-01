@extends('mobile.layout')
@section('title', 'Deudas')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Deudas</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $deudas->total() }} registros</p>
    </div>
    <a href="{{ route('mobile.deuda.nueva') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-violet-300"
       style="background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.25);">
        + Nueva
    </a>
</div>

@if($deudas->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin deudas</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay deudas registradas.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($deudas as $deuda)
        @php
            $estadoColor = match($deuda->estado) {
                'activa'   => ['bg'=>'rgba(16,185,129,0.1)', 'color'=>'#6ee7b7', 'border'=>'rgba(16,185,129,0.25)'],
                'borrador' => ['bg'=>'rgba(139,92,246,0.1)', 'color'=>'#c4b5fd', 'border'=>'rgba(139,92,246,0.25)'],
                default    => ['bg'=>'rgba(239,68,68,0.1)',  'color'=>'#fca5a5', 'border'=>'rgba(239,68,68,0.25)'],
            };
        @endphp
        <div class="card p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $deuda->numero }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $deuda->acreedor }} · {{ $deuda->plazo_meses }} meses
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: {{ $estadoColor['bg'] }}; color: {{ $estadoColor['color'] }}; border: 1px solid {{ $estadoColor['border'] }};">
                    {{ ucfirst($deuda->estado) }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs" style="color: rgba(232,230,240,0.5);">
                <span>{{ $deuda->sistema_amortizacion }} · {{ number_format($deuda->tasa_interes, 2) }}% TNA</span>
                <div class="text-right">
                    <span class="text-white font-bold text-sm">${{ number_format($deuda->monto_original, 2) }}</span>
                    @if($deuda->saldo_pendiente < $deuda->monto_original)
                    <p style="color: rgba(232,230,240,0.4);">Saldo: ${{ number_format($deuda->saldo_pendiente, 2) }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $deudas->links() }}</div>
@endif

@endsection
