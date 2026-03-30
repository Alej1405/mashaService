@extends('mobile.layout')
@section('title', $zona ? 'Editar Zona' : 'Nueva Zona')

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
    <div>
        <h2 class="text-base font-bold text-white">
            {{ $zona ? 'Editar Zona' : 'Nueva Zona' }}
        </h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">
            {{ $almacen->nombre }}
        </p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Identificación</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Código *</label>
                <input type="text" id="z-codigo" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. A-01"
                       value="{{ $zona?->codigo ?? '' }}"
                       maxlength="20">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Tipo *</label>
                <select id="z-tipo" class="input w-full px-3 py-2.5 text-sm">
                    @foreach(\App\Models\ZonaAlmacen::tiposLabels() as $val => $label)
                        <option value="{{ $val }}"
                            {{ ($zona?->tipo ?? 'estanteria') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Nombre *</label>
            <input type="text" id="z-nombre" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej. Estantería A"
                   value="{{ $zona?->nombre ?? '' }}"
                   maxlength="150">
        </div>
    </div>

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Detalles</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Descripción</label>
            <textarea id="z-descripcion" rows="2" class="input w-full px-3 py-2.5 text-sm"
                      placeholder="Descripción opcional...">{{ $zona?->descripcion ?? '' }}</textarea>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
            <div class="relative flex-shrink-0">
                <input type="checkbox" id="z-activo" class="sr-only"
                       {{ ($zona?->activo ?? true) ? 'checked' : '' }}>
                <div id="toggle-bg" class="w-10 h-6 rounded-full transition-colors"
                     style="{{ ($zona?->activo ?? true) ? 'background: #4f46e5;' : 'background: rgba(255,255,255,0.12);' }}">
                    <div id="toggle-dot" class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                         style="{{ ($zona?->activo ?? true) ? 'transform: translateX(1.25rem);' : 'transform: translateX(0.125rem);' }}"></div>
                </div>
            </div>
            <span class="text-sm" style="color: rgba(232,230,240,0.7);">Zona activa</span>
        </label>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarZona()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        {{ $zona ? 'Guardar cambios' : 'Crear zona' }}
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
        <a href="{{ route('mobile.almacenes.zonas.index', $almacen) }}"
           class="btn-primary block py-3 text-sm text-center">
            Ver todas las zonas
        </a>
        @if(!$zona)
        <button onclick="resetForm()"
                class="block py-3 text-sm rounded-xl"
                style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Crear otra zona
        </button>
        @endif
    </div>
</div>

<script>
const CSRF    = '{{ csrf_token() }}';
const ZONA_ID = {{ $zona ? $zona->id : 'null' }};
const ES_EDICION = {{ $zona ? 'true' : 'false' }};
const ALMACEN_ID = {{ $almacen->id }};

const chk = document.getElementById('z-activo');
const bg  = document.getElementById('toggle-bg');
const dot = document.getElementById('toggle-dot');

chk.addEventListener('change', function () {
    bg.style.background = this.checked ? '#4f46e5' : 'rgba(255,255,255,0.12)';
    dot.style.transform = this.checked ? 'translateX(1.25rem)' : 'translateX(0.125rem)';
});

function guardarZona() {
    const codigo = document.getElementById('z-codigo').value.trim();
    const nombre = document.getElementById('z-nombre').value.trim();
    const tipo   = document.getElementById('z-tipo').value;

    if (!codigo) { mostrarError('El código es obligatorio.'); return; }
    if (!nombre) { mostrarError('El nombre es obligatorio.'); return; }
    if (!tipo)   { mostrarError('Selecciona el tipo de zona.'); return; }

    const payload = {
        _token:      CSRF,
        zona_id:     ZONA_ID,
        codigo,
        nombre,
        tipo,
        descripcion: document.getElementById('z-descripcion').value.trim() || null,
        activo:      document.getElementById('z-activo').checked,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch(`/mobile/almacenes/${ALMACEN_ID}/zonas/guardar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear zona';
        if (data.error) { mostrarError(data.error); return; }

        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-titulo').textContent = data.modo === 'actualizado' ? '¡Zona actualizada!' : '¡Zona creada!';
        document.getElementById('exito-nombre').textContent = data.nombre;
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear zona';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function resetForm() {
    ['z-codigo', 'z-nombre', 'z-descripcion'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('z-tipo').value = 'estanteria';
    document.getElementById('z-activo').checked = true;
    bg.style.background = '#4f46e5';
    dot.style.transform = 'translateX(1.25rem)';
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
