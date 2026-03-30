@extends('mobile.layout')
@section('title', 'Zonas · ' . $almacen->nombre)

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.almacenes.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl flex-shrink-0"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1 min-w-0">
        <h2 class="text-base font-bold text-white">Zonas</h2>
        <p class="text-xs truncate" style="color: rgba(232,230,240,0.4);">{{ $almacen->nombre }} · {{ $zonas->count() }} zona(s)</p>
    </div>
    <a href="{{ route('mobile.almacenes.zonas.nueva', $almacen) }}"
       class="flex-shrink-0 text-xs px-3 py-1.5 rounded-lg font-semibold"
       style="background: rgba(79,70,229,0.2); color: #a5b4fc; border: 1px solid rgba(79,70,229,0.35);">
        + Nueva
    </a>
</div>

@if($zonas->isEmpty())
    <div class="card p-8 text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4"
             style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);">
            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Sin zonas</p>
        <p class="text-xs mb-5" style="color: rgba(232,230,240,0.4);">Registra la primera zona de este almacén</p>
        <a href="{{ route('mobile.almacenes.zonas.nueva', $almacen) }}" class="btn-primary inline-block px-6 py-2.5 text-sm">
            Crear zona
        </a>
    </div>
@else
    @php
        $tiposLabels = \App\Models\ZonaAlmacen::tiposLabels();
        $tiposColor  = [
            'pasillo'          => 'rgba(99,102,241,0.2)',
            'estanteria'       => 'rgba(16,185,129,0.2)',
            'anaquel'          => 'rgba(245,158,11,0.2)',
            'area_refrigerada' => 'rgba(59,130,246,0.2)',
            'camara_fria'      => 'rgba(14,165,233,0.2)',
            'area_cuarentena'  => 'rgba(239,68,68,0.2)',
            'area_despacho'    => 'rgba(168,85,247,0.2)',
            'area_recepcion'   => 'rgba(236,72,153,0.2)',
            'piso'             => 'rgba(107,114,128,0.2)',
            'otro'             => 'rgba(107,114,128,0.2)',
        ];
        $tiposText   = [
            'pasillo'          => '#a5b4fc',
            'estanteria'       => '#6ee7b7',
            'anaquel'          => '#fcd34d',
            'area_refrigerada' => '#93c5fd',
            'camara_fria'      => '#7dd3fc',
            'area_cuarentena'  => '#f87171',
            'area_despacho'    => '#d8b4fe',
            'area_recepcion'   => '#f9a8d4',
            'piso'             => '#d1d5db',
            'otro'             => '#d1d5db',
        ];
    @endphp

    <div class="space-y-3" id="lista-zonas">
        @foreach($zonas as $zona)
        <div class="card p-4" id="card-zona-{{ $zona->id }}">
            <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-sm font-semibold text-white truncate">{{ $zona->nombre }}</span>
                        @if(!$zona->activo)
                        <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0"
                              style="background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                            Inactiva
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                              style="background: {{ $tiposColor[$zona->tipo] ?? 'rgba(99,102,241,0.2)' }}; color: {{ $tiposText[$zona->tipo] ?? '#a5b4fc' }};">
                            {{ $tiposLabels[$zona->tipo] ?? $zona->tipo }}
                        </span>
                        <span class="text-xs font-mono flex-shrink-0" style="color: rgba(232,230,240,0.35);">
                            {{ $zona->codigo }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('mobile.almacenes.zonas.editar', [$almacen, $zona]) }}"
                       class="w-8 h-8 flex items-center justify-center rounded-lg"
                       style="background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25);">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <button onclick="confirmarEliminar({{ $zona->id }}, '{{ addslashes($zona->nombre) }}')"
                            class="w-8 h-8 flex items-center justify-center rounded-lg"
                            style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- Modal eliminar --}}
<div id="modal-eliminar" class="hidden fixed inset-0 z-50 flex items-end justify-center px-4 pb-6"
     style="background: rgba(0,0,0,0.6);">
    <div class="card w-full p-5 max-w-sm">
        <p class="text-sm font-semibold text-white mb-1">¿Eliminar zona?</p>
        <p id="modal-nombre" class="text-xs mb-4" style="color: rgba(232,230,240,0.5);"></p>
        <div id="modal-error" class="hidden mb-3 px-3 py-2 rounded-xl text-xs text-red-300"
             style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>
        <div class="flex gap-2">
            <button onclick="cerrarModal()"
                    class="flex-1 py-2.5 text-sm rounded-xl"
                    style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.1);">
                Cancelar
            </button>
            <button id="btn-confirmar-eliminar" onclick="ejecutarEliminar()"
                    class="flex-1 py-2.5 text-sm rounded-xl font-semibold"
                    style="background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.35);">
                Eliminar
            </button>
        </div>
    </div>
</div>

<script>
const CSRF      = '{{ csrf_token() }}';
const ALMACEN_ID = {{ $almacen->id }};
let zonaAEliminar = null;

function confirmarEliminar(id, nombre) {
    zonaAEliminar = id;
    document.getElementById('modal-nombre').textContent = nombre;
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('modal-eliminar').classList.remove('hidden');
}

function cerrarModal() {
    zonaAEliminar = null;
    document.getElementById('modal-eliminar').classList.add('hidden');
}

function ejecutarEliminar() {
    if (!zonaAEliminar) return;
    const btn = document.getElementById('btn-confirmar-eliminar');
    btn.disabled = true; btn.textContent = 'Eliminando...';

    fetch(`/mobile/almacenes/${ALMACEN_ID}/zonas/${zonaAEliminar}/eliminar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ _method: 'DELETE' }),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Eliminar';
        if (data.error) {
            document.getElementById('modal-error').textContent = data.error;
            document.getElementById('modal-error').classList.remove('hidden');
            return;
        }
        const card = document.getElementById('card-zona-' + zonaAEliminar);
        if (card) card.remove();
        cerrarModal();
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Eliminar';
        document.getElementById('modal-error').textContent = 'Error de conexión.';
        document.getElementById('modal-error').classList.remove('hidden');
    });
}

document.getElementById('modal-eliminar').addEventListener('click', function (e) {
    if (e.target === this) cerrarModal();
});
</script>

@endsection
