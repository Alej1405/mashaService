@extends('mobile.layout')
@section('title', $almacen ? 'Editar Almacén' : 'Nuevo Almacén')

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
    <div>
        <h2 class="text-base font-bold text-white">
            {{ $almacen ? 'Editar Almacén' : 'Nuevo Almacén' }}
        </h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">
            {{ $almacen ? $almacen->nombre : 'Registrar nuevo almacén' }}
        </p>
    </div>
</div>

{{-- Formulario --}}
<div id="paso-form" class="space-y-4">

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Identificación</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Código *</label>
                <input type="text" id="a-codigo" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="Ej. BOD-01"
                       value="{{ $almacen?->codigo ?? '' }}"
                       maxlength="20">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Tipo *</label>
                <select id="a-tipo" class="input w-full px-3 py-2.5 text-sm">
                    @php
                        $tipos = [
                            'bodega_propia'    => 'Bodega Propia',
                            'deposito_externo' => 'Depósito Externo',
                            'area_produccion'  => 'Área Producción',
                            'punto_venta'      => 'Punto de Venta',
                            'transito'         => 'Tránsito',
                        ];
                    @endphp
                    @foreach($tipos as $val => $label)
                        <option value="{{ $val }}"
                            {{ ($almacen?->tipo ?? 'bodega_propia') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Nombre *</label>
            <input type="text" id="a-nombre" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej. Bodega Central"
                   value="{{ $almacen?->nombre ?? '' }}"
                   maxlength="150">
        </div>
    </div>

    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Detalles</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Responsable / Bodeguero</label>
            <input type="text" id="a-responsable" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Nombre del encargado"
                   value="{{ $almacen?->responsable ?? '' }}"
                   maxlength="150">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Dirección / Ubicación física</label>
            <input type="text" id="a-direccion" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Dirección del almacén"
                   value="{{ $almacen?->direccion ?? '' }}"
                   maxlength="255">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Descripción</label>
            <textarea id="a-descripcion" rows="2" class="input w-full px-3 py-2.5 text-sm"
                      placeholder="Descripción opcional...">{{ $almacen?->descripcion ?? '' }}</textarea>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
            <div class="relative flex-shrink-0">
                <input type="checkbox" id="a-activo" class="sr-only"
                       {{ ($almacen?->activo ?? true) ? 'checked' : '' }}>
                <div id="toggle-bg" class="w-10 h-6 rounded-full transition-colors"
                     style="{{ ($almacen?->activo ?? true) ? 'background: #4f46e5;' : 'background: rgba(255,255,255,0.12);' }}">
                    <div id="toggle-dot" class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                         style="{{ ($almacen?->activo ?? true) ? 'transform: translateX(1.25rem);' : 'transform: translateX(0.125rem);' }}"></div>
                </div>
            </div>
            <span class="text-sm" style="color: rgba(232,230,240,0.7);">Almacén activo</span>
        </label>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarAlmacen()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        {{ $almacen ? 'Guardar cambios' : 'Crear almacén' }}
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
        <a href="{{ route('mobile.almacenes.index') }}"
           class="btn-primary block py-3 text-sm text-center">
            Ver todos los almacenes
        </a>
        @if(!$almacen)
        <button onclick="resetForm()"
                class="block py-3 text-sm rounded-xl"
                style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Crear otro almacén
        </button>
        @endif
    </div>
</div>

<script>
const CSRF      = '{{ csrf_token() }}';
const ALMACEN_ID = {{ $almacen ? $almacen->id : 'null' }};
const ES_EDICION = {{ $almacen ? 'true' : 'false' }};

// Toggle activo
const chk = document.getElementById('a-activo');
const bg  = document.getElementById('toggle-bg');
const dot = document.getElementById('toggle-dot');

chk.addEventListener('change', function () {
    if (this.checked) {
        bg.style.background  = '#4f46e5';
        dot.style.transform  = 'translateX(1.25rem)';
    } else {
        bg.style.background  = 'rgba(255,255,255,0.12)';
        dot.style.transform  = 'translateX(0.125rem)';
    }
});

function guardarAlmacen() {
    const codigo = document.getElementById('a-codigo').value.trim();
    const nombre = document.getElementById('a-nombre').value.trim();
    const tipo   = document.getElementById('a-tipo').value;

    if (!codigo) { mostrarError('El código es obligatorio.'); return; }
    if (!nombre) { mostrarError('El nombre es obligatorio.'); return; }
    if (!tipo)   { mostrarError('Selecciona el tipo de almacén.'); return; }

    const payload = {
        _token:      CSRF,
        almacen_id:  ALMACEN_ID,
        codigo,
        nombre,
        tipo,
        responsable: document.getElementById('a-responsable').value.trim() || null,
        direccion:   document.getElementById('a-direccion').value.trim() || null,
        descripcion: document.getElementById('a-descripcion').value.trim() || null,
        activo:      document.getElementById('a-activo').checked,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.almacenes.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear almacén';
        if (data.error) { mostrarError(data.error); return; }

        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-titulo').textContent = data.modo === 'actualizado'
            ? '¡Almacén actualizado!'
            : '¡Almacén creado!';
        document.getElementById('exito-nombre').textContent = data.nombre;
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = ES_EDICION ? 'Guardar cambios' : 'Crear almacén';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function resetForm() {
    ['a-codigo','a-nombre','a-responsable','a-direccion','a-descripcion'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('a-tipo').value   = 'bodega_propia';
    document.getElementById('a-activo').checked = true;
    bg.style.background = '#4f46e5';
    dot.style.transform = 'translateX(1.25rem)';
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
