@extends('mobile.layout')
@section('title', 'Cargas')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.logistica.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Cargas</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $cargas->total() }} registradas</p>
    </div>
    <a href="{{ route('mobile.logistica.carga.nueva') }}"
       class="px-3 py-1.5 rounded-xl text-xs font-semibold"
       style="background: rgba(6,182,212,0.2); color: #67e8f9; border: 1px solid rgba(6,182,212,0.3);">
        + Nueva
    </a>
</div>

@if($cargas->isEmpty())
    <div class="card px-6 py-12 text-center">
        <p class="text-sm" style="color: rgba(232,230,240,0.4);">No hay cargas registradas.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($cargas as $carga)
        @php
            $info   = \App\Models\LogisticsPackage::ESTADOS[$carga->estado] ?? [];
            $label  = $info['label'] ?? $carga->estado;
            $color  = $info['color'] ?? '#6b7280';
        @endphp
        <div class="card px-4 py-3" id="card-{{ $carga->id }}">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">
                        {{ $carga->descripcion ?? 'Carga #' . $carga->id }}
                    </p>
                    @if($carga->numero_tracking)
                        <p class="text-xs font-mono mt-0.5" style="color: rgba(232,230,240,0.4);">{{ $carga->numero_tracking }}</p>
                    @endif
                    @if($carga->storeCustomer)
                        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">{{ $carga->storeCustomer->nombre_completo }}</p>
                    @endif
                </div>
                <span id="badge-{{ $carga->id }}" class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full"
                      style="background-color:{{ $color }}22; color:{{ $color }}; border:1px solid {{ $color }}44;">
                    {{ $label }}
                </span>
            </div>
            <div class="flex items-center justify-between mt-3">
                <p class="text-xs" style="color: rgba(232,230,240,0.3);">
                    {{ $carga->created_at->format('d/m/Y') }}
                    @if($carga->bodega) · {{ $carga->bodega->pais }} @endif
                </p>
                <button onclick="abrirModalEstado({{ $carga->id }}, '{{ $carga->estado }}', '{{ $carga->estado_secundario }}')"
                        class="text-xs px-3 py-1 rounded-lg"
                        style="background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.25);">
                    Cambiar estado
                </button>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($cargas->hasPages())
    <div class="flex justify-between items-center mt-6 gap-2">
        @if($cargas->onFirstPage())
            <span class="text-xs px-3 py-1.5 rounded-xl opacity-30"
                  style="background:rgba(255,255,255,0.05); color:rgba(232,230,240,0.4); border:1px solid rgba(255,255,255,0.08);">← Anterior</span>
        @else
            <a href="{{ $cargas->previousPageUrl() }}"
               class="text-xs px-3 py-1.5 rounded-xl"
               style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">← Anterior</a>
        @endif
        <span class="text-xs" style="color:rgba(232,230,240,0.35);">Página {{ $cargas->currentPage() }} de {{ $cargas->lastPage() }}</span>
        @if($cargas->hasMorePages())
            <a href="{{ $cargas->nextPageUrl() }}"
               class="text-xs px-3 py-1.5 rounded-xl"
               style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">Siguiente →</a>
        @else
            <span class="text-xs px-3 py-1.5 rounded-xl opacity-30"
                  style="background:rgba(255,255,255,0.05); color:rgba(232,230,240,0.4); border:1px solid rgba(255,255,255,0.08);">Siguiente →</span>
        @endif
    </div>
    @endif
@endif

{{-- Modal cambio de estado --}}
<div id="modal-estado" class="hidden fixed inset-0 z-50 flex items-end justify-center px-4 pb-6"
     style="background: rgba(0,0,0,0.7);">
    <div class="card w-full max-w-sm p-5 space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-white">Actualizar estado</p>
            <button onclick="cerrarModal()"
                    class="w-7 h-7 flex items-center justify-center rounded-lg"
                    style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.5);">✕</button>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Estado principal</label>
            <select id="m-estado" class="input w-full px-3 py-2.5 text-sm" onchange="cargarSecundarios()">
                @foreach(\App\Models\LogisticsPackage::ESTADOS as $key => $info)
                    <option value="{{ $key }}">{{ $info['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div id="wrap-secundario">
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Estado secundario</label>
            <select id="m-secundario" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin estado secundario —</option>
            </select>
        </div>

        <div id="modal-error" class="hidden text-xs text-red-300 px-3 py-2 rounded-xl"
             style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

        <div class="flex gap-2 pt-1">
            <button onclick="cerrarModal()"
                    class="flex-1 py-2.5 text-sm rounded-xl"
                    style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">
                Cancelar
            </button>
            <button id="btn-confirmar-estado" onclick="confirmarEstado()"
                    class="flex-1 py-2.5 text-sm rounded-xl font-semibold btn-primary">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script>
const CSRF    = '{{ csrf_token() }}';
const ESTADOS_SEC = @json(\App\Models\LogisticsPackage::ESTADOS_SECUNDARIOS);
let pkgActualId = null;

function abrirModalEstado(id, estadoActual, secundarioActual) {
    pkgActualId = id;
    document.getElementById('m-estado').value = estadoActual;
    cargarSecundarios(secundarioActual);
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('modal-estado').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-estado').classList.add('hidden');
    pkgActualId = null;
}

function cargarSecundarios(seleccionado = '') {
    const estado  = document.getElementById('m-estado').value;
    const sel     = document.getElementById('m-secundario');
    const wrap    = document.getElementById('wrap-secundario');
    const secs    = ESTADOS_SEC[estado] || {};

    sel.innerHTML = '<option value="">— Sin estado secundario —</option>';
    const keys = Object.keys(secs);

    if (keys.length === 0) {
        wrap.classList.add('hidden');
        return;
    }

    keys.forEach(k => {
        const opt = document.createElement('option');
        opt.value = k;
        opt.textContent = secs[k].label;
        if (k === seleccionado) opt.selected = true;
        sel.appendChild(opt);
    });
    wrap.classList.remove('hidden');
}

function confirmarEstado() {
    if (!pkgActualId) return;
    const estado     = document.getElementById('m-estado').value;
    const secundario = document.getElementById('m-secundario').value;
    const btn        = document.getElementById('btn-confirmar-estado');

    btn.disabled = true; btn.textContent = 'Guardando...';
    document.getElementById('modal-error').classList.add('hidden');

    fetch(`/mobile/logistica/carga/${pkgActualId}/estado`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ estado, estado_secundario: secundario || null }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.textContent = 'Confirmar';
        if (!ok) {
            const el = document.getElementById('modal-error');
            el.textContent = data.error || 'Error al actualizar.';
            el.classList.remove('hidden');
            return;
        }
        // Actualizar badge en la tarjeta sin recargar
        const estados = @json(\App\Models\LogisticsPackage::ESTADOS);
        const info    = estados[estado] || {};
        const badge   = document.getElementById('badge-' + pkgActualId);
        if (badge && info.label) {
            badge.textContent = info.label;
            badge.style.color = info.color || '#6b7280';
            badge.style.backgroundColor = (info.color || '#6b7280') + '22';
            badge.style.borderColor = (info.color || '#6b7280') + '44';
        }
        cerrarModal();
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Confirmar';
        document.getElementById('modal-error').textContent = 'Error de conexión.';
        document.getElementById('modal-error').classList.remove('hidden');
    });
}

document.getElementById('modal-estado').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>

@endsection
