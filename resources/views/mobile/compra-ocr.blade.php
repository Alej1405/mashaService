@extends('mobile.layout')
@section('title', 'Registrar Compra')

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
        <h2 class="text-base font-bold text-white">Registrar Compra</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Foto o ingreso manual</p>
    </div>
</div>

{{-- PASO 1: Tomar foto --}}
<div id="paso-foto" class="space-y-4">

    <div class="card p-4 text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-3"
             style="background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.25);">
            <svg class="w-7 h-7 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-white mb-1">Toma una foto a la factura</p>
        <p class="text-xs mb-4" style="color: rgba(232,230,240,0.45);">
            El sistema detectará automáticamente los campos del SRI
        </p>

        <input type="file" id="input-foto" accept="image/*" capture="environment" class="hidden">

        <button onclick="document.getElementById('input-foto').click()"
                class="btn-primary w-full py-3 text-sm mb-2">
            Tomar foto / Seleccionar imagen
        </button>

        <button onclick="saltarOcr()"
                class="w-full py-2.5 text-xs rounded-xl"
                style="background: rgba(255,255,255,0.05); color: rgba(232,230,240,0.5); border: 1px solid rgba(255,255,255,0.08);">
            Ingresar manualmente sin foto
        </button>
    </div>

    {{-- Preview imagen seleccionada --}}
    <div id="preview-container" class="hidden card p-3">
        <img id="preview-img" class="w-full rounded-xl max-h-48 object-contain mb-3">
        <button id="btn-procesar" onclick="procesarOcr()"
                class="btn-primary w-full py-3 text-sm">
            Analizar factura con IA
        </button>
    </div>

    {{-- Estado de procesamiento --}}
    <div id="estado-ocr" class="hidden card p-4 text-center">
        <div class="w-8 h-8 mx-auto mb-3 rounded-full border-2 border-indigo-500 border-t-transparent animate-spin"></div>
        <p class="text-sm text-white">Analizando factura...</p>
        <p class="text-xs mt-1" style="color: rgba(232,230,240,0.4);">Esto puede tomar unos segundos</p>
    </div>

    {{-- Error OCR --}}
    <div id="error-ocr" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);">
    </div>

</div>

{{-- PASO 2: Formulario con datos extraídos --}}
<div id="paso-formulario" class="hidden space-y-4">

    <div class="flex items-center gap-2 mb-1">
        <div id="ocr-dot" class="w-2 h-2 rounded-full bg-emerald-400"></div>
        <p class="text-xs text-emerald-400 font-medium" id="estado-ocr-label"></p>
    </div>

    {{-- Datos de la factura --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold text-white mb-1">Datos de la Factura</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">N° Factura *</label>
            <input type="text" id="f-numero" placeholder="001-001-000000001"
                   class="input w-full px-3 py-2.5 text-sm">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Fecha *</label>
            <input type="date" id="f-fecha" class="input w-full px-3 py-2.5 text-sm">
        </div>
    </div>

    {{-- Proveedor --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold text-white mb-1">Proveedor</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Proveedor existente</label>
            <select id="f-supplier" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin proveedor / nuevo —</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>

        <p class="text-xs mt-1" style="color: rgba(232,230,240,0.35);">
            Si el proveedor no aparece en la lista, guarda como borrador y completa desde el panel ERP.
        </p>
    </div>

    {{-- Ítems --}}
    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-white">Ítems de la Compra</p>
            <button onclick="agregarItem()"
                    class="text-xs px-2.5 py-1 rounded-lg text-indigo-300"
                    style="background: rgba(79,70,229,0.15); border: 1px solid rgba(79,70,229,0.3);">
                + Agregar
            </button>
        </div>

        <div id="lista-items" class="space-y-3"></div>
    </div>

    {{-- Totales --}}
    <div class="card p-4 space-y-2">
        <p class="text-xs font-semibold text-white mb-1">Totales</p>

        <div class="flex justify-between items-center">
            <label class="text-xs" style="color: rgba(232,230,240,0.5);">Subtotal</label>
            <input type="number" id="f-subtotal" step="0.01" min="0"
                   class="input w-28 px-3 py-1.5 text-sm text-right">
        </div>
        <div class="flex justify-between items-center">
            <label class="text-xs" style="color: rgba(232,230,240,0.5);">IVA</label>
            <input type="number" id="f-iva" step="0.01" min="0"
                   class="input w-28 px-3 py-1.5 text-sm text-right">
        </div>
        <div class="flex justify-between items-center pt-1" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <label class="text-sm font-semibold text-white">Total</label>
            <input type="number" id="f-total" step="0.01" min="0"
                   class="input w-28 px-3 py-1.5 text-sm text-right font-semibold text-white">
        </div>
    </div>

    {{-- Error guardado --}}
    <div id="error-guardar" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);">
    </div>

    <button onclick="guardarCompra()"
            id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        Registrar Compra
    </button>

    <button onclick="reiniciar()"
            class="w-full py-2.5 text-xs rounded-xl"
            style="background: rgba(255,255,255,0.04); color: rgba(232,230,240,0.4); border: 1px solid rgba(255,255,255,0.07);">
        Empezar de nuevo
    </button>

</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3);">
        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-2">¡Compra registrada!</h3>
    <p id="exito-numero" class="text-sm text-emerald-400 mb-6"></p>
    <button onclick="reiniciar()" class="btn-primary px-8 py-3 text-sm">Nueva compra</button>
    <a href="{{ route('mobile.index') }}" class="mt-3 text-xs" style="color: rgba(232,230,240,0.4);">
        Volver al inicio
    </a>
</div>

{{-- Template de ítem --}}
<template id="tmpl-item">
    <div class="item-row p-3 rounded-xl space-y-2" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
        <div class="flex justify-between items-center">
            <p class="text-xs font-medium text-white">Ítem #<span class="item-num"></span></p>
            <button onclick="eliminarItem(this)" class="text-xs text-red-400 px-2 py-0.5 rounded"
                    style="background: rgba(239,68,68,0.1);">✕</button>
        </div>

        {{-- Descripción --}}
        <input type="text" class="item-desc input w-full px-3 py-2 text-xs" placeholder="Descripción del producto">

        {{-- Vincular a inventario --}}
        <select class="item-inv input w-full px-3 py-2 text-xs" onchange="alCambiarProducto(this)">
            <option value="">— Sin vincular a inventario —</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" data-nombre="{{ $item->nombre }}">
                    {{ $item->codigo }} — {{ $item->nombre }}
                </option>
            @endforeach
        </select>

        {{-- Presentación / Empaque --}}
        <div class="item-pres-wrap" style="display:none;">
            <p class="text-xs mb-1" style="color:rgba(232,230,240,0.4);">Presentación / Empaque</p>
            <select class="item-pres input w-full px-3 py-2 text-xs" onchange="alCambiarPresentacion(this)">
                <option value="">— Unidad base (sin presentación) —</option>
            </select>
            <p class="item-pres-hint text-xs mt-1 hidden" style="color:#a5b4fc;"></p>
        </div>

        {{-- Cantidad y precio --}}
        <div class="flex gap-2">
            <div class="flex-1">
                <p class="text-xs mb-1" style="color: rgba(232,230,240,0.4);">Cantidad</p>
                <input type="number" class="item-qty input w-full px-3 py-2 text-xs"
                       placeholder="0" min="0.001" step="0.001" oninput="actualizarHint(this)">
            </div>
            <div class="flex-1">
                <p class="text-xs mb-1" style="color: rgba(232,230,240,0.4);">Precio unit.</p>
                <input type="number" class="item-price input w-full px-3 py-2 text-xs"
                       placeholder="0.00" min="0" step="0.01">
            </div>
        </div>

        {{-- Factor oculto --}}
        <input type="hidden" class="item-factor" value="1">
    </div>
</template>

<script>
const CSRF          = '{{ csrf_token() }}';
const PRESENTATIONS = @json($presentationsByItem);
let itemCount = 0;

// ── Foto ─────────────────────────────────────────────────────────────────
document.getElementById('input-foto').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('preview-container').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
});

function procesarOcr() {
    const file = document.getElementById('input-foto').files[0];
    if (!file) return;

    document.getElementById('preview-container').classList.add('hidden');
    document.getElementById('estado-ocr').classList.remove('hidden');
    document.getElementById('error-ocr').classList.add('hidden');

    const form = new FormData();
    form.append('foto', file);
    form.append('_token', CSRF);

    fetch('{{ route("mobile.compra.procesar-ocr") }}', {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
        body:    form,
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        document.getElementById('estado-ocr').classList.add('hidden');
        if (!ok || data.error) {
            mostrarErrorOcr(data.error || data.message || 'Error al procesar la imagen.');
            return;
        }
        rellenarFormulario(data, true);
    })
    .catch(() => {
        document.getElementById('estado-ocr').classList.add('hidden');
        mostrarErrorOcr('Error de conexión al procesar la imagen.');
    });
}

function mostrarErrorOcr(msg) {
    const el = document.getElementById('error-ocr');
    el.textContent = msg;
    el.classList.remove('hidden');
    document.getElementById('preview-container').classList.remove('hidden');
}

function saltarOcr() {
    rellenarFormulario({}, false);
}

// ── Formulario ────────────────────────────────────────────────────────────
function rellenarFormulario(data, desdeOcr) {
    document.getElementById('paso-foto').classList.add('hidden');
    document.getElementById('paso-formulario').classList.remove('hidden');

    const label = document.getElementById('estado-ocr-label');
    const dot   = document.getElementById('ocr-dot');
    label.textContent = desdeOcr ? 'Datos extraídos por IA — revisa y corrige si es necesario' : 'Ingreso manual';
    dot.className = desdeOcr ? 'w-2 h-2 rounded-full bg-emerald-400' : 'w-2 h-2 rounded-full bg-amber-400';
    label.className = desdeOcr ? 'text-xs text-emerald-400 font-medium' : 'text-xs text-amber-400 font-medium';

    if (data.numero_factura)   document.getElementById('f-numero').value   = data.numero_factura;
    if (data.fecha)            document.getElementById('f-fecha').value    = data.fecha;
    if (data.subtotal_sin_iva != null) document.getElementById('f-subtotal').value = data.subtotal_sin_iva;
    if (data.iva_monto    != null) document.getElementById('f-iva').value    = data.iva_monto;
    if (data.total        != null) document.getElementById('f-total').value  = data.total;

    if (data.supplier_id) {
        document.getElementById('f-supplier').value = data.supplier_id;
    }

    if (data.items && data.items.length > 0) {
        data.items.forEach(item => agregarItem(item));
    } else {
        agregarItem();
    }
}

// ── Ítems ─────────────────────────────────────────────────────────────────
function agregarItem(data = null) {
    itemCount++;
    const tmpl = document.getElementById('tmpl-item').content.cloneNode(true);
    tmpl.querySelector('.item-num').textContent = itemCount;
    if (data) {
        if (data.descripcion)     tmpl.querySelector('.item-desc').value  = data.descripcion;
        if (data.cantidad)        tmpl.querySelector('.item-qty').value   = data.cantidad;
        if (data.precio_unitario) tmpl.querySelector('.item-price').value = data.precio_unitario;
    }
    document.getElementById('lista-items').appendChild(tmpl);
}

function eliminarItem(btn) {
    btn.closest('.item-row').remove();
}

// ── Al cambiar producto ───────────────────────────────────────────────────
function alCambiarProducto(sel) {
    const row    = sel.closest('.item-row');
    const itemId = sel.value;

    // Rellenar descripción si está vacía
    const opt = sel.selectedOptions[0];
    if (opt && opt.dataset.nombre) {
        const descEl = row.querySelector('.item-desc');
        if (!descEl.value) descEl.value = opt.dataset.nombre;
    }

    // Presentaciones
    const presWrap = row.querySelector('.item-pres-wrap');
    const presSel  = row.querySelector('.item-pres');
    row.querySelector('.item-factor').value = 1;
    presSel.innerHTML = '<option value="">— Unidad base (sin presentación) —</option>';

    const pres = itemId ? (PRESENTATIONS[itemId] || []) : [];
    if (pres.length > 0) {
        pres.forEach(p => {
            const o = document.createElement('option');
            o.value          = p.id;
            o.dataset.factor = p.factor_conversion;
            o.textContent    = p.nombre + ' (×' + parseFloat(p.factor_conversion) + ')';
            presSel.appendChild(o);
        });
        presWrap.style.display = '';
    } else {
        presWrap.style.display = 'none';
    }
}

// ── Al cambiar presentación ───────────────────────────────────────────────
function alCambiarPresentacion(sel) {
    const row   = sel.closest('.item-row');
    const opt   = sel.selectedOptions[0];
    const fact  = opt && opt.dataset.factor ? parseFloat(opt.dataset.factor) : 1;
    const hint  = row.querySelector('.item-pres-hint');
    row.querySelector('.item-factor').value = fact;

    if (fact !== 1) {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 1;
        hint.textContent = `${qty} × ${fact} = ${Math.round(qty * fact * 10000) / 10000} unidades de compra`;
        hint.classList.remove('hidden');
    } else {
        hint.classList.add('hidden');
    }
}

// ── Hint al cambiar cantidad ──────────────────────────────────────────────
function actualizarHint(qtySel) {
    const row   = qtySel.closest('.item-row');
    const fact  = parseFloat(row.querySelector('.item-factor').value) || 1;
    const hint  = row.querySelector('.item-pres-hint');
    if (fact !== 1) {
        const qty = parseFloat(qtySel.value) || 0;
        hint.textContent = `${qty} × ${fact} = ${Math.round(qty * fact * 10000) / 10000} unidades de compra`;
        hint.classList.remove('hidden');
    }
}

// ── Guardar ───────────────────────────────────────────────────────────────
function guardarCompra() {
    const errEl = document.getElementById('error-guardar');
    errEl.classList.add('hidden');

    const numero = document.getElementById('f-numero').value.trim();
    const fecha  = document.getElementById('f-fecha').value;
    if (!numero) { mostrarErrorGuardar('El número de factura es obligatorio.'); return; }
    if (!fecha)  { mostrarErrorGuardar('La fecha es obligatoria.'); return; }

    const rows  = document.querySelectorAll('.item-row');
    if (rows.length === 0) { mostrarErrorGuardar('Agrega al menos un ítem.'); return; }

    const items = [];
    let valido  = true;
    rows.forEach((row, i) => {
        const desc = row.querySelector('.item-desc').value.trim();
        const qty  = parseFloat(row.querySelector('.item-qty').value);
        const prc  = parseFloat(row.querySelector('.item-price').value);
        const fact = parseFloat(row.querySelector('.item-factor').value) || 1;

        if (!desc)           { mostrarErrorGuardar(`Ítem ${i + 1}: la descripción es obligatoria.`); valido = false; }
        if (!(qty > 0))      { mostrarErrorGuardar(`Ítem ${i + 1}: la cantidad debe ser mayor a 0.`); valido = false; }

        // Cantidad real = cantidad en presentaciones × factor
        const cantidadReal = Math.round(qty * fact * 1000000) / 1000000;

        items.push({
            descripcion:        desc,
            cantidad:           cantidadReal,
            precio_unitario:    prc || 0,
            inventory_item_id:  row.querySelector('.item-inv').value || null,
        });
    });

    if (!valido) return;

    const subtotal = parseFloat(document.getElementById('f-subtotal').value) || 0;
    const iva      = parseFloat(document.getElementById('f-iva').value)      || 0;
    const total    = parseFloat(document.getElementById('f-total').value)    || (subtotal + iva);

    const payload = {
        _token:         CSRF,
        numero_factura: numero,
        fecha,
        supplier_id:    document.getElementById('f-supplier').value || null,
        subtotal:       subtotal || total / 1.15,
        iva:            iva      || total - (total / 1.15),
        total:          total    || subtotal + iva,
        items,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    fetch('{{ route("mobile.compra.guardar") }}', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept':       'application/json',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled    = false;
        btn.textContent = 'Registrar Compra';
        if (!ok) {
            if (data.errors) {
                const primer = Object.values(data.errors).flat()[0];
                mostrarErrorGuardar(primer || data.message || 'Error de validación.');
            } else {
                mostrarErrorGuardar(data.error || data.message || 'Error al guardar.');
            }
            return;
        }
        document.getElementById('paso-formulario').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-numero').textContent = data.number;
    })
    .catch(() => {
        btn.disabled    = false;
        btn.textContent = 'Registrar Compra';
        mostrarErrorGuardar('Error de conexión. Revisa tu red e intenta de nuevo.');
    });
}

function mostrarErrorGuardar(msg) {
    const el = document.getElementById('error-guardar');
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function reiniciar() {
    itemCount = 0;
    document.getElementById('lista-items').innerHTML = '';
    document.getElementById('input-foto').value      = '';
    document.getElementById('preview-container').classList.add('hidden');
    ['f-numero','f-fecha','f-subtotal','f-iva','f-total'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('f-supplier').value = '';
    document.getElementById('error-ocr').classList.add('hidden');
    document.getElementById('error-guardar').classList.add('hidden');
    document.getElementById('paso-formulario').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-foto').classList.remove('hidden');
}
</script>

@endsection
