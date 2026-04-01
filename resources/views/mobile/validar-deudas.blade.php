@extends('mobile.layout')
@section('title', 'Validar Deudas')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Validar Deudas</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Deudas en borrador pendientes de activación</p>
    </div>
    @if($deudas->count())
        <span class="ml-auto text-xs px-2.5 py-1 rounded-full font-semibold text-violet-300"
              style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3);">
            {{ $deudas->count() }} pendiente{{ $deudas->count() > 1 ? 's' : '' }}
        </span>
    @endif
</div>

@if($deudas->isEmpty())
    <div class="card p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-2xl flex items-center justify-center mb-3"
             style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2);">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Todo al día</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay deudas pendientes de activación.</p>
    </div>
@else
    <div class="space-y-3" id="lista-deudas">
        @foreach($deudas as $deuda)
        <div class="card p-4" id="deuda-{{ $deuda->id }}">

            <div class="flex items-start justify-between mb-3">
                <div>
                    <p class="text-sm font-bold text-white">{{ $deuda->numero }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $deuda->tipo_label }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: rgba(139,92,246,0.12); color: #c4b5fd; border: 1px solid rgba(139,92,246,0.25);">
                    Borrador
                </span>
            </div>

            <div class="space-y-1.5 mb-3 text-xs" style="color: rgba(232,230,240,0.55);">
                <div class="flex justify-between">
                    <span>Acreedor</span>
                    <span class="text-white font-medium">{{ $deuda->acreedor }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Monto</span>
                    <span class="text-white font-bold">${{ number_format($deuda->monto_original, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Tasa TNA</span>
                    <span class="text-white">{{ number_format($deuda->tasa_interes, 2) }}% anual</span>
                </div>
                <div class="flex justify-between">
                    <span>Plazo</span>
                    <span class="text-white">{{ $deuda->plazo_meses }} meses</span>
                </div>
                <div class="flex justify-between">
                    <span>Sistema</span>
                    <span class="text-white">{{ ucfirst($deuda->sistema_amortizacion) }}</span>
                </div>
                <div class="flex justify-between pt-1" style="border-top: 1px solid rgba(255,255,255,0.07);">
                    <span>Inicio</span>
                    <span class="text-white">{{ \Carbon\Carbon::parse($deuda->fecha_inicio)->format('d/m/Y') }}</span>
                </div>
            </div>

            @if($deuda->descripcion)
            <p class="text-xs mb-3 italic" style="color: rgba(232,230,240,0.4);">{{ $deuda->descripcion }}</p>
            @endif

            <div class="error-msg hidden mb-2 px-3 py-2 rounded-xl text-xs text-red-300"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);"></div>

            <button onclick="activar({{ $deuda->id }}, this)"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold text-white"
                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                Activar deuda — generar asiento y tabla
            </button>

        </div>
        @endforeach
    </div>
@endif

<script>
const CSRF = '{{ csrf_token() }}';

function activar(id, btn) {
    btn.disabled = true;
    btn.textContent = 'Activando...';

    const card = document.getElementById('deuda-' + id);
    const errEl = card.querySelector('.error-msg');
    errEl.classList.add('hidden');

    fetch(`/mobile/deudas/${id}/activar`, {
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
            btn.textContent = 'Activar deuda — generar asiento y tabla';
            return;
        }

        card.style.transition = 'opacity 0.4s, transform 0.4s';
        card.style.opacity = '0';
        card.style.transform = 'translateX(40px)';
        setTimeout(() => {
            card.remove();
            const lista = document.getElementById('lista-deudas');
            if (lista && lista.children.length === 0) {
                lista.innerHTML = `
                    <div class="card p-8 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl flex items-center justify-center mb-3"
                             style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2);">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white mb-1">¡Todo activado!</p>
                        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No quedan deudas pendientes.</p>
                    </div>`;
                const badge = document.querySelector('.ml-auto');
                if (badge) badge.remove();
            }
        }, 400);
    })
    .catch(() => {
        errEl.textContent = 'Error de conexión.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Activar deuda — generar asiento y tabla';
    });
}
</script>

@endsection
