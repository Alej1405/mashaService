@extends('mobile.layout')
@section('title', 'Diseño de Producto')

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
        <h2 class="text-base font-bold text-white">Nuevo Diseño de Producto</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Información general y fórmula</p>
    </div>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-emerald-300"
     style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-red-300"
     style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form-diseno" class="space-y-4">
    @csrf

    {{-- ── Información General ── --}}
    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Información General</p>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Nombre del producto *</label>
            <input type="text" name="nombre" required maxlength="150" placeholder="Ej: Salsa Picante Premium"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Propuesta de valor</label>
            <textarea name="propuesta_valor" rows="3" placeholder="¿Qué hace único a este producto? ¿A quién va dirigido?"
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);"></textarea>
        </div>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Notas estratégicas</label>
            <textarea name="notas_estrategicas" rows="2" placeholder="Diferenciadores, mercado objetivo, observaciones..."
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);"></textarea>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-white font-medium">¿Múltiples presentaciones?</p>
                <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Ej: 250ml, 500ml, 1L</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="toggle-multi" name="tiene_multiples_presentaciones" value="1" class="sr-only peer" onchange="toggleMulti(this)">
                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                     style="background: rgba(255,255,255,0.15);"></div>
            </label>
        </div>
    </div>

    {{-- ── Fórmula / Presentaciones ── --}}
    <div class="card p-4 space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider" id="label-formula">Fórmula del Producto</p>
            <button type="button" onclick="agregarPresentacion()"
                    class="text-xs px-3 py-1.5 rounded-xl font-medium text-indigo-300"
                    id="btn-add-pres" style="display:none; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);">
                + Presentación
            </button>
        </div>

        <div id="presentaciones-container" class="space-y-4"></div>
    </div>

    <button type="submit" id="btn-guardar"
            class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
        Guardar Diseño de Producto
    </button>
</form>

<script>
const UNIDADES = @json($unidades->map(fn($u) => ['id' => $u->id, 'nombre' => $u->nombre]));
const INSUMOS  = @json($insumos->map(fn($i) => ['id' => $i->id, 'nombre' => $i->nombre, 'codigo' => $i->codigo]));

let multiPres = false;
let presIdx   = 0;

function toggleMulti(chk) {
    multiPres = chk.checked;
    document.getElementById('label-formula').textContent = multiPres ? 'Presentaciones y Fórmulas' : 'Fórmula del Producto';
    document.getElementById('btn-add-pres').style.display = multiPres ? 'block' : 'none';

    const container = document.getElementById('presentaciones-container');
    container.innerHTML = '';
    presIdx = 0;
    agregarPresentacion();
}

function agregarPresentacion() {
    const idx = presIdx++;
    const container = document.getElementById('presentaciones-container');
    const div = document.createElement('div');
    div.id = `pres-${idx}`;
    div.className = 'rounded-xl p-3 space-y-3';
    div.style = 'background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);';

    div.innerHTML = `
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold text-indigo-300">${multiPres ? 'Presentación ' + (idx + 1) : 'Fórmula base'}</p>
            ${multiPres && idx > 0 ? `<button type="button" onclick="document.getElementById('pres-${idx}').remove()" class="text-xs text-red-400">Eliminar</button>` : ''}
        </div>
        ${multiPres ? `
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs mb-1" style="color: rgba(232,230,240,0.6);">Nombre / Tamaño *</label>
                <input type="text" name="presentaciones[${idx}][nombre]" placeholder="250ml, Talla S..."
                       class="w-full rounded-xl px-3 py-2 text-xs text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color: rgba(232,230,240,0.6);">Unidad de Medida</label>
                ${selectUnidad(`presentaciones[${idx}][measurement_unit_id]`)}
            </div>
        </div>` : ''}
        <div>
            <label class="block text-xs mb-1" style="color: rgba(232,230,240,0.6);">Lote base (unidades de referencia) *</label>
            <input type="number" name="presentaciones[${idx}][cantidad_minima_produccion]" min="0.0001" step="0.0001" value="1" required
                   class="w-full rounded-xl px-3 py-2 text-xs text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-medium" style="color: rgba(232,230,240,0.7);">Insumos / Materias primas</p>
                <button type="button" onclick="agregarLinea(${idx})"
                        class="text-xs px-2.5 py-1 rounded-lg text-indigo-300"
                        style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);">
                    + Agregar insumo
                </button>
            </div>
            <div id="formula-${idx}" class="space-y-2"></div>
        </div>
    `;
    container.appendChild(div);
    agregarLinea(idx);
}

function selectUnidad(name) {
    return `<select name="${name}" class="w-full rounded-xl px-2 py-2 text-xs text-white" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        <option value="">— Sin unidad —</option>
        ${UNIDADES.map(u => `<option value="${u.id}">${u.nombre}</option>`).join('')}
    </select>`;
}

let lineaIdx = {};

function agregarLinea(presI) {
    if (!lineaIdx[presI]) lineaIdx[presI] = 0;
    const li = lineaIdx[presI]++;
    const container = document.getElementById(`formula-${presI}`);
    const div = document.createElement('div');
    div.id = `linea-${presI}-${li}`;
    div.className = 'rounded-xl p-3 space-y-2';
    div.style = 'background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);';

    div.innerHTML = `
        <div class="flex items-center justify-between">
            <p class="text-xs" style="color: rgba(232,230,240,0.5);">Insumo #${li + 1}</p>
            <button type="button" onclick="document.getElementById('linea-${presI}-${li}').remove()" class="text-xs text-red-400">×</button>
        </div>
        <select name="presentaciones[${presI}][formula][${li}][inventory_item_id]"
                class="w-full rounded-xl px-2 py-2 text-xs text-white"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            <option value="">— Seleccionar insumo —</option>
            ${INSUMOS.map(i => `<option value="${i.id}">${i.codigo} — ${i.nombre}</option>`).join('')}
        </select>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs mb-1" style="color: rgba(232,230,240,0.5);">Cantidad *</label>
                <input type="number" name="presentaciones[${presI}][formula][${li}][cantidad]"
                       min="0.000001" step="0.000001" placeholder="1.0" required
                       class="w-full rounded-xl px-2 py-2 text-xs text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color: rgba(232,230,240,0.5);">Unidad</label>
                ${selectUnidad(`presentaciones[${presI}][formula][${li}][measurement_unit_id]`)}
            </div>
        </div>
        <input type="text" name="presentaciones[${presI}][formula][${li}][notas]" placeholder="Notas (opcional)"
               class="w-full rounded-xl px-2 py-2 text-xs text-white"
               style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
    `;
    container.appendChild(div);
}

// Inicializar con una presentación vacía
agregarPresentacion();

// ── Submit ──
document.getElementById('form-diseno').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const formData = new FormData(this);
    const token    = formData.get('_token');

    // Construir payload manualmente para arrays anidados
    const payload = {
        nombre:                        formData.get('nombre'),
        propuesta_valor:               formData.get('propuesta_valor'),
        notas_estrategicas:            formData.get('notas_estrategicas'),
        tiene_multiples_presentaciones: multiPres ? 1 : 0,
        presentaciones: [],
    };

    // Recolectar presentaciones desde el DOM
    document.querySelectorAll('[id^="pres-"]').forEach(presEl => {
        const presId = presEl.id.replace('pres-', '');
        const pres = {
            nombre:                     formData.get(`presentaciones[${presId}][nombre]`) || null,
            cantidad_minima_produccion: formData.get(`presentaciones[${presId}][cantidad_minima_produccion]`),
            measurement_unit_id:        formData.get(`presentaciones[${presId}][measurement_unit_id]`) || null,
            formula: [],
        };

        document.querySelectorAll(`[id^="linea-${presId}-"]`).forEach(lineaEl => {
            const liId = lineaEl.id.replace(`linea-${presId}-`, '');
            const iid  = formData.get(`presentaciones[${presId}][formula][${liId}][inventory_item_id]`);
            if (!iid) return;
            pres.formula.push({
                inventory_item_id:  iid,
                cantidad:           formData.get(`presentaciones[${presId}][formula][${liId}][cantidad]`),
                measurement_unit_id: formData.get(`presentaciones[${presId}][formula][${liId}][measurement_unit_id]`) || null,
                notas:              formData.get(`presentaciones[${presId}][formula][${liId}][notas]`) || null,
            });
        });

        payload.presentaciones.push(pres);
    });

    try {
        const res = await fetch('{{ route('mobile.diseno-producto.guardar') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (json.success) {
            document.getElementById('msg-ok').textContent = '✓ Diseño "' + json.nombre + '" guardado correctamente.';
            document.getElementById('msg-ok').classList.remove('hidden');
            document.getElementById('msg-err').classList.add('hidden');
            this.reset();
            document.getElementById('presentaciones-container').innerHTML = '';
            presIdx = 0; lineaIdx = {}; multiPres = false;
            document.getElementById('toggle-multi').checked = false;
            document.getElementById('label-formula').textContent = 'Fórmula del Producto';
            document.getElementById('btn-add-pres').style.display = 'none';
            agregarPresentacion();
            window.scrollTo(0, 0);
            setTimeout(() => document.getElementById('msg-ok').classList.add('hidden'), 6000);
        } else {
            document.getElementById('msg-err').textContent = json.error || 'Error al guardar.';
            document.getElementById('msg-err').classList.remove('hidden');
            document.getElementById('msg-ok').classList.add('hidden');
        }
    } catch(err) {
        document.getElementById('msg-err').textContent = 'Error de conexión.';
        document.getElementById('msg-err').classList.remove('hidden');
    }

    btn.disabled = false;
    btn.textContent = 'Guardar Diseño de Producto';
});
</script>

@endsection
