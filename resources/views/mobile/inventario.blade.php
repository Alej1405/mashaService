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

<script>
const CSRF = '{{ csrf_token() }}';

function guardarItem() {
    const nombre = document.getElementById('i-nombre').value.trim();
    const type   = document.getElementById('i-type').value;

    if (!nombre) { mostrarError('El nombre del ítem es obligatorio.'); return; }
    if (!type)   { mostrarError('Selecciona el tipo de ítem.'); return; }

    const payload = {
        _token:              CSRF,
        nombre,
        type,
        measurement_unit_id: document.getElementById('i-unidad').value || null,
        descripcion:         document.getElementById('i-descripcion').value.trim() || null,
        purchase_price:      document.getElementById('i-precio-compra').value || null,
        sale_price:          document.getElementById('i-precio-venta').value || null,
        stock_actual:        document.getElementById('i-stock').value || 0,
        stock_minimo:        document.getElementById('i-stock-min').value || 0,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.inventario.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Agregar al Inventario';
        if (data.error) { mostrarError(data.error); return; }
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-codigo').textContent = data.codigo;
        document.getElementById('exito-nombre').textContent = data.nombre;
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Agregar al Inventario';
        mostrarError('Error de conexión.');
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
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
