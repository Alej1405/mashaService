@extends('mobile.layout')
@section('title', 'Validar Compras')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Validar Compras</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Compras en borrador pendientes</p>
    </div>
    @if($compras->count())
        <span class="ml-auto text-xs px-2.5 py-1 rounded-full font-semibold text-amber-300"
              style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3);">
            {{ $compras->count() }} pendiente{{ $compras->count() > 1 ? 's' : '' }}
        </span>
    @endif
</div>

{{-- Sin compras --}}
@if($compras->isEmpty())
    <div class="card p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-2xl flex items-center justify-center mb-3"
             style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2);">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Todo al día</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay compras pendientes de validación.</p>
    </div>
@else
    <div class="space-y-3" id="lista-compras">
        @foreach($compras as $compra)
        <div class="card p-4" id="compra-{{ $compra->id }}">

            {{-- Cabecera --}}
            <div class="flex items-start justify-between mb-3">
                <div>
                    <p class="text-sm font-bold text-white">{{ $compra->number }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        Factura: {{ $compra->numero_factura ?? '—' }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25);">
                    Borrador
                </span>
            </div>

            {{-- Detalles --}}
            <div class="space-y-1.5 mb-3 text-xs" style="color: rgba(232,230,240,0.55);">
                <div class="flex justify-between">
                    <span>Proveedor</span>
                    <span class="text-white font-medium">
                        {{ $compra->supplier?->nombre ?? '⚠ Sin proveedor' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Fecha</span>
                    <span class="text-white">{{ \Carbon\Carbon::parse($compra->date)->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Ítems</span>
                    <span class="text-white">{{ $compra->items->count() }}</span>
                </div>
                <div class="flex justify-between pt-1" style="border-top: 1px solid rgba(255,255,255,0.07);">
                    <span class="font-medium">Total</span>
                    <span class="text-white font-bold">${{ number_format($compra->total, 2) }}</span>
                </div>
            </div>

            {{-- Ítems expandible --}}
            @if($compra->items->count())
            <div class="mb-3 rounded-xl overflow-hidden" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);">
                @foreach($compra->items as $item)
                <div class="flex justify-between items-center px-3 py-2 text-xs {{ !$loop->last ? 'border-b' : '' }}"
                     style="{{ !$loop->last ? 'border-color: rgba(255,255,255,0.06)' : '' }}">
                    <span style="color: rgba(232,230,240,0.6);" class="flex-1 pr-2 truncate">
                        {{ $item->inventoryItem?->nombre ?? '(ítem sin vincular)' }}
                    </span>
                    <span class="text-white whitespace-nowrap">
                        {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Error por compra --}}
            <div class="error-msg hidden mb-2 px-3 py-2 rounded-xl text-xs text-red-300"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);">
            </div>

            {{-- Acciones --}}
            @if($compra->supplier_id)
                <button onclick="confirmar({{ $compra->id }}, this)"
                        class="btn-primary w-full py-2.5 text-sm font-semibold">
                    Confirmar compra
                </button>
            @else
                <div class="text-xs text-center py-2 rounded-xl"
                     style="background: rgba(245,158,11,0.08); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">
                    Asigna un proveedor desde el panel ERP antes de confirmar
                </div>
            @endif

        </div>
        @endforeach
    </div>
@endif

<script>
const CSRF = '{{ csrf_token() }}';

function confirmar(id, btn) {
    btn.disabled = true;
    btn.textContent = 'Confirmando...';

    const card = document.getElementById('compra-' + id);
    const errEl = card.querySelector('.error-msg');
    errEl.classList.add('hidden');

    fetch(`/mobile/compras/${id}/confirmar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({}),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            errEl.textContent = data.error;
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Confirmar compra';
            return;
        }

        // Animación de éxito y remover tarjeta
        card.style.transition = 'opacity 0.4s, transform 0.4s';
        card.style.opacity = '0';
        card.style.transform = 'translateX(40px)';
        setTimeout(() => {
            card.remove();

            // Si no quedan compras, mostrar estado vacío
            const lista = document.getElementById('lista-compras');
            if (lista && lista.children.length === 0) {
                lista.innerHTML = `
                    <div class="card p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl flex items-center justify-center mb-3"
                             style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2);">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white mb-1">¡Todo validado!</p>
                        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No quedan compras pendientes.</p>
                    </div>`;
                // Ocultar badge del header
                const badge = document.querySelector('.ml-auto');
                if (badge) badge.remove();
            }
        }, 400);
    })
    .catch(() => {
        errEl.textContent = 'Error de conexión.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Confirmar compra';
    });
}
</script>

@endsection
