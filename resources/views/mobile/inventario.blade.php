@extends('mobile.layout')
@section('title', 'Nuevo Ítem de Inventario')

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
        <h2 class="text-base font-bold text-white">Nuevo Ítem de Inventario</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Se agregará al inventario activo</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    {{-- Datos básicos --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Información básica</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Nombre del ítem *</label>
            <input type="text" id="i-nombre" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej. Arroz blanco 50kg">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Tipo de ítem *</label>
            <select id="i-type" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Selecciona un tipo —</option>
                <option value="insumo">Insumo</option>
                <option value="materia_prima">Materia Prima</option>
                <option value="producto_terminado">Producto Terminado</option>
                <option value="activo_fijo">Activo Fijo</option>
                <option value="servicio">Servicio</option>
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Unidad de medida</label>
            <select id="i-unidad" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin unidad —</option>
                @foreach($unidades as $u)
                    <option value="{{ $u->id }}">{{ $u->nombre }} ({{ $u->abreviatura }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Descripción</label>
            <textarea id="i-descripcion" rows="2" class="input w-full px-3 py-2.5 text-sm"
                      placeholder="Descripción opcional..."></textarea>
        </div>
    </div>

    {{-- Foto del producto --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Foto del producto <span style="color: rgba(232,230,240,0.25);">(opcional)</span></p>

        <div>
            <input type="file" id="i-foto" accept="image/*" capture="environment" class="hidden">
            <div id="foto-preview-wrap" class="hidden mb-2">
                <img id="foto-preview" src="" alt="Vista previa"
                     class="w-full rounded-xl object-cover" style="max-height: 160px;">
            </div>
            <button type="button" onclick="document.getElementById('i-foto').click()"
                    id="btn-foto"
                    class="w-full py-3 text-sm rounded-xl flex items-center justify-center gap-2"
                    style="background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); color: rgba(232,230,240,0.5);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span id="btn-foto-text">Tomar foto o seleccionar imagen</span>
            </button>
        </div>
    </div>

    {{-- Ubicación en almacén --}}
    @if($almacenes->isNotEmpty())
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Ubicación en almacén <span style="color: rgba(232,230,240,0.25);">(opcional)</span></p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Almacén</label>
            <select id="i-almacen" class="input w-full px-3 py-2.5 text-sm" onchange="cargarZonas()">
                <option value="">— Sin ubicación —</option>
                @foreach($almacenes as $alm)
                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div id="wrap-zona" class="hidden">
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Zona</label>
            <select id="i-zona" class="input w-full px-3 py-2.5 text-sm" onchange="cargarUbicaciones()">
                <option value="">— Sin zona específica —</option>
            </select>
        </div>

        <div id="wrap-ubicacion" class="hidden">
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Posición</label>
            <select id="i-ubicacion" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin posición específica —</option>
            </select>
            <p class="text-xs mt-1" style="color: rgba(232,230,240,0.3);">Si no hay posiciones, créalas primero en el módulo de Almacenes.</p>
        </div>
    </div>
    @endif

    {{-- Presentación --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">
            Presentación <span style="color: rgba(232,230,240,0.25);">(opcional)</span>
        </p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">
            Ej: Caja de 24 unidades, Paquete de 12 botellas.
        </p>

        <select id="i-pres-select" class="input w-full px-3 py-2.5 text-sm" onchange="alCambiarPres(this)">
            <option value="">— Sin presentación —</option>
            @foreach($presentaciones as $pres)
                <option value="{{ $pres->id }}"
                        data-cap="{{ $pres->capacidad }}"
                        data-unit="{{ $pres->measurementUnit->abreviatura ?? '' }}">
                    {{ $pres->nombre }}
                    @if($pres->capacidad)
                        ({{ number_format($pres->capacidad, 0) }}
                        {{ $pres->measurementUnit->abreviatura ?? 'u.' }})
                    @endif
                </option>
            @endforeach
            <option value="__nueva__">+ Registrar nueva presentación…</option>
        </select>

        <div id="pres-resumen" class="hidden px-3 py-2 rounded-xl text-xs"
             style="background:rgba(79,70,229,0.1); color:#a5b4fc; border:1px solid rgba(79,70,229,0.2);"></div>

        {{-- Campos ocultos --}}
        <input type="hidden" id="i-pres-id">
        <input type="hidden" id="i-new-pres-nombre">
        <input type="hidden" id="i-new-pres-unit">
        <input type="hidden" id="i-new-pres-capacidad">
    </div>

    {{-- Precios y stock --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Precios y stock</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Precio de compra</label>
                <input type="number" id="i-precio-compra" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0.00" min="0" step="0.01">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Precio de venta</label>
                <input type="number" id="i-precio-venta" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0.00" min="0" step="0.01">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Stock inicial</label>
                <input type="number" id="i-stock" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0" min="0" step="0.001" value="0">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Stock mínimo</label>
                <input type="number" id="i-stock-min" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0" min="0" step="0.001" value="0">
            </div>
        </div>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarItem()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        Agregar al Inventario
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
    <h3 class="text-lg font-bold text-white mb-1">¡Ítem registrado!</h3>
    <p id="exito-codigo" class="text-sm text-emerald-400 mb-1"></p>
    <p id="exito-nombre" class="text-xs mb-6" style="color:rgba(232,230,240,0.5);"></p>
    <div class="flex flex-col gap-3 w-full">
        <button onclick="resetForm()" class="btn-primary py-3 text-sm">Agregar otro ítem</button>
        <a href="{{ route('mobile.index') }}"
           class="block py-3 text-sm text-center rounded-xl"
           style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Volver al inicio
        </a>
    </div>
</div>

{{-- Modal: nueva presentación --}}
<div id="modal-pres" class="hidden fixed inset-0 z-50 flex items-end justify-center px-4 pb-6"
     style="background: rgba(0,0,0,0.65);">
    <div class="card w-full p-5 max-w-sm space-y-4">
        <p class="text-sm font-semibold text-white">Nueva Presentación</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Nombre *</label>
            <input type="text" id="m-pres-nombre" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej: Caja x24, Paquete x12">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Unidad</label>
            <select id="m-pres-unit" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin especificar —</option>
                @foreach($unidades as $u)
                    <option value="{{ $u->id }}">{{ $u->nombre }} ({{ $u->abreviatura }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Capacidad</label>
            <input type="number" id="m-pres-capacidad" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej: 24" min="0.0001" step="any">
            <p class="text-xs mt-1" style="color: rgba(232,230,240,0.3);">
                Cuántas unidades contiene. Ej: una caja de 24 botellas → 24.
            </p>
        </div>

        <div id="modal-pres-error" class="hidden text-xs text-red-300 px-3 py-2 rounded-xl"
             style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

        <div class="flex gap-2 pt-1">
            <button onclick="cerrarModalPres()"
                    class="flex-1 py-2.5 text-sm rounded-xl"
                    style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">
                Cancelar
            </button>
            <button onclick="confirmarPres()"
                    class="flex-1 py-2.5 text-sm rounded-xl font-semibold"
                    style="background:rgba(79,70,229,0.25); color:#a5b4fc; border:1px solid rgba(79,70,229,0.4);">
                Agregar
            </button>
        </div>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

// Foto: preview
document.getElementById('i-foto').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('foto-preview').src = e.target.result;
        document.getElementById('foto-preview-wrap').classList.remove('hidden');
        document.getElementById('btn-foto-text').textContent = 'Cambiar foto';
    };
    reader.readAsDataURL(file);
});

// Cascading: almacén → zonas
function cargarZonas() {
    const almacenId = document.getElementById('i-almacen').value;
    const wrapZona  = document.getElementById('wrap-zona');
    const wrapUbic  = document.getElementById('wrap-ubicacion');
    const selZona   = document.getElementById('i-zona');
    const selUbic   = document.getElementById('i-ubicacion');

    selZona.innerHTML = '<option value="">— Sin zona específica —</option>';
    selUbic.innerHTML = '<option value="">— Sin posición específica —</option>';
    wrapZona.classList.add('hidden');
    wrapUbic.classList.add('hidden');

    if (!almacenId) return;

    fetch(`/mobile/almacenes/${almacenId}/zonas-json`, {
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(zonas => {
        if (!Array.isArray(zonas) || zonas.length === 0) return;
        zonas.forEach(z => {
            const opt = document.createElement('option');
            opt.value = z.id;
            opt.textContent = `${z.nombre} (${z.codigo})`;
            selZona.appendChild(opt);
        });
        wrapZona.classList.remove('hidden');
    })
    .catch(() => {});
}

// Cascading: zona → ubicaciones
function cargarUbicaciones() {
    const zonaId  = document.getElementById('i-zona').value;
    const wrapUbic = document.getElementById('wrap-ubicacion');
    const selUbic  = document.getElementById('i-ubicacion');

    selUbic.innerHTML = '<option value="">— Sin posición específica —</option>';
    wrapUbic.classList.add('hidden');

    if (!zonaId) return;

    fetch(`/mobile/zonas/${zonaId}/ubicaciones-json`, {
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(ubicaciones => {
        if (!Array.isArray(ubicaciones) || ubicaciones.length === 0) return;
        ubicaciones.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = `${u.nombre} (${u.codigo_ubicacion})`;
            selUbic.appendChild(opt);
        });
        wrapUbic.classList.remove('hidden');
    })
    .catch(() => {});
}

// ── Presentación ──────────────────────────────────────────────────────────
function alCambiarPres(sel) {
    if (sel.value === '__nueva__') {
        sel.value = '';
        abrirModalPres();
        return;
    }
    // Limpiar datos de "nueva"
    document.getElementById('i-pres-id').value          = sel.value;
    document.getElementById('i-new-pres-nombre').value  = '';
    document.getElementById('i-new-pres-unit').value    = '';
    document.getElementById('i-new-pres-capacidad').value = '';

    const resumen = document.getElementById('pres-resumen');
    if (sel.value) {
        const opt = sel.selectedOptions[0];
        const cap  = opt.dataset.cap  ? parseFloat(opt.dataset.cap) : null;
        const unit = opt.dataset.unit || 'u.';
        resumen.textContent = cap ? `${opt.text.split('(')[0].trim()} — ${cap} ${unit} por presentación` : opt.text;
        resumen.classList.remove('hidden');
    } else {
        resumen.classList.add('hidden');
    }
}

function abrirModalPres() {
    document.getElementById('m-pres-nombre').value   = '';
    document.getElementById('m-pres-unit').value     = '';
    document.getElementById('m-pres-capacidad').value = '';
    document.getElementById('modal-pres-error').classList.add('hidden');
    document.getElementById('modal-pres').classList.remove('hidden');
    setTimeout(() => document.getElementById('m-pres-nombre').focus(), 100);
}

function cerrarModalPres() {
    document.getElementById('modal-pres').classList.add('hidden');
}

function confirmarPres() {
    const nombre    = document.getElementById('m-pres-nombre').value.trim();
    const unitId    = document.getElementById('m-pres-unit').value;
    const capacidad = document.getElementById('m-pres-capacidad').value;
    const errEl     = document.getElementById('modal-pres-error');

    if (!nombre) {
        errEl.textContent = 'El nombre es obligatorio.';
        errEl.classList.remove('hidden');
        return;
    }

    // Guardar en campos ocultos
    document.getElementById('i-pres-id').value           = '';
    document.getElementById('i-new-pres-nombre').value   = nombre;
    document.getElementById('i-new-pres-unit').value     = unitId;
    document.getElementById('i-new-pres-capacidad').value = capacidad;

    // Agregar al select y seleccionar
    const sel  = document.getElementById('i-pres-select');
    const prev = sel.querySelector('option[data-custom]');
    if (prev) prev.remove();

    const opt = document.createElement('option');
    opt.value          = '__nueva_registrada__';
    opt.dataset.custom = '1';
    const unitText = document.getElementById('m-pres-unit').selectedOptions[0]?.text?.split('(')[1]?.replace(')','') || 'u.';
    opt.textContent = capacidad
        ? `${nombre} (${parseFloat(capacidad)} ${unitText.trim()})`
        : nombre;
    sel.appendChild(opt);
    sel.value = '__nueva_registrada__';

    const resumen = document.getElementById('pres-resumen');
    resumen.textContent = capacidad
        ? `${nombre} — ${parseFloat(capacidad)} ${unitText.trim()} por presentación`
        : nombre;
    resumen.classList.remove('hidden');

    cerrarModalPres();
}

document.getElementById('modal-pres').addEventListener('click', function (e) {
    if (e.target === this) cerrarModalPres();
});

function guardarItem() {
    const nombre = document.getElementById('i-nombre').value.trim();
    const type   = document.getElementById('i-type').value;

    if (!nombre) { mostrarError('El nombre del ítem es obligatorio.'); return; }
    if (!type)   { mostrarError('Selecciona el tipo de ítem.'); return; }

    const fotoInput = document.getElementById('i-foto');
    const ubicId    = document.getElementById('i-ubicacion')?.value || null;

    // Usar FormData para soportar el archivo de foto
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('nombre', nombre);
    fd.append('type', type);

    const unidad = document.getElementById('i-unidad').value;
    if (unidad) fd.append('measurement_unit_id', unidad);

    const desc = document.getElementById('i-descripcion').value.trim();
    if (desc) fd.append('descripcion', desc);

    const pc = document.getElementById('i-precio-compra').value;
    if (pc) fd.append('purchase_price', pc);

    const pv = document.getElementById('i-precio-venta').value;
    if (pv) fd.append('sale_price', pv);

    fd.append('stock_actual', document.getElementById('i-stock').value || 0);
    fd.append('stock_minimo', document.getElementById('i-stock-min').value || 0);

    if (ubicId) fd.append('ubicacion_almacen_id', ubicId);

    if (fotoInput.files[0]) fd.append('foto', fotoInput.files[0]);

    // Presentación
    const presId = document.getElementById('i-pres-id').value;
    if (presId) {
        fd.append('presentation_id', presId);
    } else {
        const newNombre = document.getElementById('i-new-pres-nombre').value;
        if (newNombre) {
            fd.append('new_pres_nombre', newNombre);
            const newUnit = document.getElementById('i-new-pres-unit').value;
            if (newUnit) fd.append('new_pres_unit', newUnit);
            const newCap = document.getElementById('i-new-pres-capacidad').value;
            if (newCap) fd.append('new_pres_capacidad', newCap);
        }
    }

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.inventario.guardar") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.textContent = 'Agregar al Inventario';
        if (!ok) {
            // Errores de validación Laravel: { errors: { campo: ['msg'] } }
            if (data.errors) {
                const primer = Object.values(data.errors).flat()[0];
                mostrarError(primer || data.message || 'Error de validación.');
            } else {
                mostrarError(data.error || data.message || 'Error al guardar.');
            }
            return;
        }
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-codigo').textContent = data.codigo;
        document.getElementById('exito-nombre').textContent = data.nombre;
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Agregar al Inventario';
        mostrarError('Error de conexión. Verifica tu red e intenta de nuevo.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function resetForm() {
    document.getElementById('i-nombre').value = '';
    document.getElementById('i-type').value = '';
    document.getElementById('i-unidad').value = '';
    document.getElementById('i-descripcion').value = '';
    document.getElementById('i-precio-compra').value = '';
    document.getElementById('i-precio-venta').value = '';
    document.getElementById('i-stock').value = '0';
    document.getElementById('i-stock-min').value = '0';
    document.getElementById('i-foto').value = '';
    document.getElementById('foto-preview-wrap').classList.add('hidden');
    document.getElementById('btn-foto-text').textContent = 'Tomar foto o seleccionar imagen';

    const selAlmacen = document.getElementById('i-almacen');
    if (selAlmacen) {
        selAlmacen.value = '';
        document.getElementById('wrap-zona').classList.add('hidden');
        document.getElementById('wrap-ubicacion').classList.add('hidden');
    }

    // Limpiar presentación
    const presSel = document.getElementById('i-pres-select');
    const prevOpt = presSel.querySelector('option[data-custom]');
    if (prevOpt) prevOpt.remove();
    presSel.value = '';
    document.getElementById('i-pres-id').value           = '';
    document.getElementById('i-new-pres-nombre').value   = '';
    document.getElementById('i-new-pres-unit').value     = '';
    document.getElementById('i-new-pres-capacidad').value = '';
    document.getElementById('pres-resumen').classList.add('hidden');

    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
