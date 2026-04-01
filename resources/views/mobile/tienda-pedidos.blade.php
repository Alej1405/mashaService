@extends('mobile.layout')
@section('title', 'Pedidos')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.ecommerce.index') }}" class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Pedidos</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $pedidos->total() }} pedidos</p>
    </div>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm text-emerald-300" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm text-red-300" style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

@if($pedidos->isEmpty())
    <div class="card p-8 text-center"><p class="text-sm text-white">Sin pedidos registrados.</p></div>
@else
    <div class="space-y-3">
        @foreach($pedidos as $pedido)
        @php
            $colorMap = [
                'pendiente'   => ['bg'=>'rgba(245,158,11,0.1)',  'color'=>'#fcd34d',  'border'=>'rgba(245,158,11,0.25)'],
                'confirmado'  => ['bg'=>'rgba(99,102,241,0.1)',  'color'=>'#a5b4fc',  'border'=>'rgba(99,102,241,0.25)'],
                'preparando'  => ['bg'=>'rgba(59,130,246,0.1)',  'color'=>'#93c5fd',  'border'=>'rgba(59,130,246,0.25)'],
                'enviado'     => ['bg'=>'rgba(20,184,166,0.1)',  'color'=>'#5eead4',  'border'=>'rgba(20,184,166,0.25)'],
                'entregado'   => ['bg'=>'rgba(16,185,129,0.1)',  'color'=>'#6ee7b7',  'border'=>'rgba(16,185,129,0.25)'],
                'cancelado'   => ['bg'=>'rgba(239,68,68,0.1)',   'color'=>'#fca5a5',  'border'=>'rgba(239,68,68,0.25)'],
            ];
            $c = $colorMap[$pedido->estado] ?? $colorMap['pendiente'];
        @endphp
        <div class="card p-4" id="pedido-{{ $pedido->id }}">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $pedido->numero }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $pedido->customer?->nombre ?? 'Cliente desconocido' }}
                        · {{ $pedido->created_at?->format('d/m/Y') }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: {{ $c['bg'] }}; color: {{ $c['color'] }}; border: 1px solid {{ $c['border'] }};">
                    {{ ucfirst($pedido->estado) }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-white">${{ number_format($pedido->total, 2) }}</span>
                @if(auth()->user()->hasRole('admin_empresa') || auth()->user()->hasRole('super_admin'))
                <select onchange="cambiarEstado({{ $pedido->id }}, this.value, this)"
                        class="text-xs rounded-lg px-2 py-1 text-white"
                        style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);">
                    @foreach(['pendiente','confirmado','preparando','enviado','entregado','cancelado'] as $est)
                    <option value="{{ $est }}" {{ $pedido->estado == $est ? 'selected' : '' }}>{{ ucfirst($est) }}</option>
                    @endforeach
                </select>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $pedidos->links() }}</div>
@endif

<script>
const CSRF = '{{ csrf_token() }}';
async function cambiarEstado(id, estado, sel) {
    try {
        const res = await fetch(`/mobile/tienda/pedidos/${id}/estado`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({estado}),
        });
        const json = await res.json();
        if (json.success) {
            document.getElementById('msg-ok').textContent = json.message;
            document.getElementById('msg-ok').classList.remove('hidden');
            setTimeout(() => document.getElementById('msg-ok').classList.add('hidden'), 3000);
        } else {
            document.getElementById('msg-err').textContent = json.error || 'Error.';
            document.getElementById('msg-err').classList.remove('hidden');
        }
    } catch { document.getElementById('msg-err').textContent = 'Error de conexión.'; document.getElementById('msg-err').classList.remove('hidden'); }
}
</script>

@endsection
