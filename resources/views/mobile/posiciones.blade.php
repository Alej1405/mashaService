@extends('mobile.layout')
@section('title', 'Posiciones · ' . $zona->nombre)

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.almacenes.zonas.index', $almacen) }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl flex-shrink-0"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1 min-w-0">
        <h2 class="text-base font-bold text-white">Posiciones</h2>
        <p class="text-xs truncate" style="color: rgba(232,230,240,0.4);">
            {{ $almacen->nombre }} › {{ $zona->nombre }} · {{ $posiciones->count() }} posición(es)
        </p>
    </div>
    <a href="{{ route('mobile.almacenes.zonas.posiciones.nueva', [$almacen, $zona]) }}"
       class="flex-shrink-0 text-xs px-3 py-1.5 rounded-lg font-semibold"
       style="background: rgba(79,70,229,0.2); color: #a5b4fc; border: 1px solid rgba(79,70,229,0.35);">
        + Nueva
    </a>
</div>

@if($posiciones->isEmpty())
    <div class="card p-8 text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4"
             style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);">
            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Sin posiciones</p>
        <p class="text-xs mb-5" style="color: rgba(232,230,240,0.4);">Registra la primera posición de esta zona</p>
        <a href="{{ route('mobile.almacenes.zonas.posiciones.nueva', [$almacen, $zona]) }}"
           class="btn-primary inline-block px-6 py-2.5 text-sm">
            Crear posición
        </a>
    </div>
@else
    <div class="space-y-3" id="lista-posiciones">
        @foreach($posiciones as $pos)
        <div class="card p-4" id="card-pos-{{ $pos->id }}">
            <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-sm font-semibold text-white truncate">{{ $pos->nombre }}</span>
                        @if(!$pos->activo)
                        <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0"
                              style="background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                            Inactiva
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-mono flex-shrink-0" style="color: rgba(232,230,240,0.35);">
                            {{ $pos->codigo_ubicacion }}
                        </span>
                        @if($pos->capacidad_maxima)
                        <span class="text-xs flex-shrink-0" style="color: rgba(232,230,240,0.4);">
                            · Cap. {{ number_format($pos->capacidad_maxima, 0) }}
                            {{ $pos->unidad_capacidad }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('mobile.almacenes.zonas.posiciones.editar', [$almacen, $zona, $pos]) }}"
                       class="w-8 h-8 flex items-center justify-center rounded-lg"
                       style="background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25);">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <button onclick="confirmarEliminar({{ $pos->id }}, '{{ addslashes($pos->nombre) }}')"
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
        <p class="text-sm font-semibold text-white mb-1">¿Eliminar posición?</p>
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
const ZONA_ID   = {{ $zona->id }};
let posAEliminar = null;

function confirmarEliminar(id, nombre) {
    posAEliminar = id;
    document.getElementById('modal-nombre').textContent = nombre;
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('modal-eliminar').classList.remove('hidden');
}

function cerrarModal() {
    posAEliminar = null;
    document.getElementById('modal-eliminar').classList.add('hidden');
}

function ejecutarEliminar() {
    if (!posAEliminar) return;
    const btn = document.getElementById('btn-confirmar-eliminar');
    btn.disabled = true; btn.textContent = 'Eliminando...';

    fetch(`/mobile/almacenes/${ALMACEN_ID}/zonas/${ZONA_ID}/posiciones/${posAEliminar}/eliminar`, {
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
        const card = document.getElementById('card-pos-' + posAEliminar);
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
