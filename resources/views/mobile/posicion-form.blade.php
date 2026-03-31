@extends('mobile.layout')
@section('title', $ubicacion ? 'Editar Posición' : 'Nueva Posición')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.almacenes.zonas.posiciones.index', [$almacen, $zona]) }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl flex-shrink-0"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">
            {{ $ubicacion ? 'Editar Posición' : 'Nueva Posición' }}
        </h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">
            {{ $almacen->nombre }} › {{ $zona->nombre }}
        </p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Identificación</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Código *</label>
                <input type="text" id="u-codigo" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. A-01-03"
                       value="{{ $ubicacion?->codigo_ubicacion ?? '' }}"
                       maxlength="20">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Nombre *</label>
                <input type="text" id="u-nombre" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. Anaquel 3, Fila 1"
                       value="{{ $ubicacion?->nombre ?? '' }}"
                       maxlength="150">
            </div>
        </div>
    </div>

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">
            Capacidad <span style="color: rgba(232,230,240,0.25);">(opcional)</span>
        </p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Capacidad máxima</label>
                <input type="number" id="u-capacidad" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. 50" min="0" step="1"
                       value="{{ $ubicacion?->capacidad_maxima ?? '' }}">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Unidad</label>
                <input type="text" id="u-unidad" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. cajas, kg"
                       value="{{ $ubicacion?->unidad_capacidad ?? '' }}"
                       maxlength="50">
            </div>
        </div>
    </div>

    <div class="card p-4">
        <label class="flex items-center gap-3 cursor-pointer">
            <div class="relative flex-shrink-0">
                <input type="checkbox" id="u-activo" class="sr-only"
                       {{ ($ubicacion?->activo ?? true) ? 'checked' : '' }}>
                <div id="toggle-bg" class="w-10 h-6 rounded-full transition-colors"
                     style="{{ ($ubicacion?->activo ?? true) ? 'background: #4f46e5;' : 'background: rgba(255,255,255,0.12);' }}">
                    <div id="toggle-dot" class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                         style="{{ ($ubicacion?->activo ?? true) ? 'transform: translateX(1.25rem);' : 'transform: translateX(0.125rem);' }}"></div>
                </div>
            </div>
            <span class="text-sm" style="color: rgba(232,230,240,0.7);">Posición activa</span>
        </label>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarUbicacion()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        {{ $ubicacion ? 'Guardar cambios' : 'Crear posición' }}
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3);">
        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h3 id="exito-titulo" class="text-lg font-bold text-white mb-1"></h3>
    <p id="exito-nombre" class="text-sm text-emerald-400 mb-6"></p>
    <div class="flex flex-col gap-3 w-full">
        <a href="{{ route('mobile.almacenes.zonas.posiciones.index', [$almacen, $zona]) }}"
           class="btn-primary block py-3 text-sm text-center">
            Ver todas las posiciones
        </a>
        @if(!$ubicacion)
        <button onclick="resetForm()"
                class="block py-3 text-sm rounded-xl"
                style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Crear otra posición
        </button>
        @endif
    </div>
</div>

<script>
const CSRF        = '{{ csrf_token() }}';
const UBICACION_ID = {{ $ubicacion ? $ubicacion->id : 'null' }};
const ES_EDICION  = {{ $ubicacion ? 'true' : 'false' }};
const ALMACEN_ID  = {{ $almacen->id }};
const ZONA_ID     = {{ $zona->id }};

const chk = document.getElementById('u-activo');
const bg  = document.getElementById('toggle-bg');
const dot = document.getElementById('toggle-dot');

chk.addEventListener('change', function () {
    bg.style.background = this.checked ? '#4f46e5' : 'rgba(255,255,255,0.12)';
    dot.style.transform = this.checked ? 'translateX(1.25rem)' : 'translateX(0.125rem)';
});

function guardarUbicacion() {
    const codigo = document.getElementById('u-codigo').value.trim();
    const nombre = document.getElementById('u-nombre').value.trim();

    if (!codigo) { mostrarError('El código es obligatorio.'); return; }
    if (!nombre) { mostrarError('El nombre es obligatorio.'); return; }

    const payload = {
        _token:          CSRF,
        ubicacion_id:    UBICACION_ID,
        codigo_ubicacion: codigo,
        nombre,
        capacidad_maxima: document.getElementById('u-capacidad').value || null,
        unidad_capacidad: document.getElementById('u-unidad').value.trim() || null,
        activo:          document.getElementById('u-activo').checked,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch(`/mobile/almacenes/${ALMACEN_ID}/zonas/${ZONA_ID}/posiciones/guardar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear posición';
        if (!ok) {
            if (data.errors) {
                mostrarError(Object.values(data.errors).flat()[0] || 'Error de validación.');
            } else {
                mostrarError(data.error || 'Error al guardar.');
            }
            return;
        }
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-titulo').textContent = data.modo === 'actualizado' ? '¡Posición actualizada!' : '¡Posición creada!';
        document.getElementById('exito-nombre').textContent = data.nombre;
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear posición';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function resetForm() {
    ['u-codigo', 'u-nombre', 'u-capacidad', 'u-unidad'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('u-activo').checked = true;
    bg.style.background = '#4f46e5';
    dot.style.transform = 'translateX(1.25rem)';
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
