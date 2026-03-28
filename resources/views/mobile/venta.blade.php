@extends('mobile.layout')
@section('title', 'Nueva Venta')

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
        <h2 class="text-base font-bold text-white">Nueva Venta</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Se guardará como borrador</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    {{-- Datos generales --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold text-white mb-1">Datos Generales</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Fecha *</label>
            <input type="date" id="v-fecha" class="input w-full px-3 py-2.5 text-sm"
                   value="{{ now()->toDateString() }}">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Cliente</label>
            <select id="v-customer" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Consumidor final —</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Tipo de venta</label>
                <select id="v-tipo" class="input w-full px-3 py-2.5 text-sm">
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Forma de pago</label>
                <select id="v-forma-pago" class="input w-full px-3 py-2.5 text-sm">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta_credito">Tarjeta</option>
                    <option value="credito">Crédito</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Ítems --}}
    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-white">Productos / Servicios</p>
            <button onclick="agregarItem()"
                    class="text-xs px-2.5 py-1 rounded-lg text-indigo-300"
                    style="background: rgba(79,70,229,0.15); border: 1px solid rgba(79,70,229,0.3);">
                + Agregar
            </button>
        </div>
        <div id="lista-items" class="space-y-3"></div>
    </div>

    {{-- Resumen --}}
    <div class="card p-4 space-y-2" id="resumen" style="display:none;">
        <p class="text-xs font-semibold text-white mb-1">Resumen</p>
        <div class="flex justify-between text-xs">
            <span style="color:rgba(232,230,240,0.5);">Subtotal sin IVA</span>
            <span class="text-white" id="r-subtotal">$0.00</span>
        </div>
        <div class="flex justify-between text-xs">
            <span style="color:rgba(232,230,240,0.5);">IVA 15%</span>
            <span class="text-white" id="r-iva">$0.00</span>
        </div>
        <div class="flex justify-between text-sm font-bold pt-1" style="border-top:1px solid rgba(255,255,255,0.08);">
            <span class="text-white">Total</span>
            <span class="text-white" id="r-total">$0.00</span>
        </div>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarVenta()" id="btn-guardar" class="btn-primary w-full py-3.5 text-sm font-semibold">
        Registrar Venta
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3);">
        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-2">¡Venta registrada!</h3>
    <p id="exito-ref" class="text-sm text-emerald-400 mb-6"></p>
    <a href="{{ route('mobile.index') }}" class="btn-primary px-8 py-3 text-sm inline-block">Volver al inicio</a>
</div>

<template id="tmpl-item">
    <div class="item-row p-3 rounded-xl space-y-2" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
        <div class="flex justify-between items-center">
            <p class="text-xs font-medium text-white">Ítem #<span class="item-num"></span></p>
            <button onclick="eliminarItem(this)" class="text-xs text-red-400 px-2 py-0.5 rounded" style="background:rgba(239,68,68,0.1);">✕</button>
        </div>
        <select class="item-inv input w-full px-3 py-2 text-xs">
            <option value="">— Producto libre (sin inventario) —</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" data-precio="{{ $item->sale_price ?? 0 }}">{{ $item->codigo }} — {{ $item->nombre }}</option>
            @endforeach
        </select>
        <input type="text" class="item-desc input w-full px-3 py-2 text-xs" placeholder="Descripción">
        <div class="flex gap-2">
            <div class="flex-1">
                <p class="text-xs mb-1" style="color:rgba(232,230,240,0.4);">Cantidad</p>
                <input type="number" class="item-qty input w-full px-3 py-2 text-xs" placeholder="0" min="0.001" step="0.001" oninput="recalcular()">
            </div>
            <div class="flex-1">
                <p class="text-xs mb-1" style="color:rgba(232,230,240,0.4);">Precio unit.</p>
                <input type="number" class="item-price input w-full px-3 py-2 text-xs" placeholder="0.00" min="0" step="0.01" oninput="recalcular()">
            </div>
        </div>
        <label class="flex items-center gap-2 text-xs" style="color:rgba(232,230,240,0.5);">
            <input type="checkbox" class="item-iva" checked> Aplica IVA 15%
        </label>
    </div>
</template>

<script>
const CSRF = '{{ csrf_token() }}';
let itemCount = 0;

function agregarItem(data = null) {
    itemCount++;
    const tmpl = document.getElementById('tmpl-item').content.cloneNode(true);
    tmpl.querySelector('.item-num').textContent = itemCount;
    const sel = tmpl.querySelector('.item-inv');
    sel.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        const precio = opt?.dataset?.precio ?? '';
        const row = this.closest('.item-row');
        if (precio) row.querySelector('.item-price').value = precio;
        const nombre = opt?.text?.split('—')[1]?.trim() ?? '';
        if (nombre) row.querySelector('.item-desc').value = nombre;
        recalcular();
    });
    document.getElementById('lista-items').appendChild(tmpl);
    if (itemCount === 1) agregarItem = agregarItem; // keep reference
}

function eliminarItem(btn) { btn.closest('.item-row').remove(); recalcular(); }

function recalcular() {
    let sub = 0, iva = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const prc = parseFloat(row.querySelector('.item-price').value) || 0;
        const s = qty * prc;
        sub += s;
        if (row.querySelector('.item-iva').checked) iva += s * 0.15;
    });
    document.getElementById('r-subtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('r-iva').textContent      = '$' + iva.toFixed(2);
    document.getElementById('r-total').textContent    = '$' + (sub + iva).toFixed(2);
    document.getElementById('resumen').style.display = document.querySelectorAll('.item-row').length ? '' : 'none';
}

function guardarVenta() {
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        items.push({
            inventory_item_id: row.querySelector('.item-inv').value || null,
            descripcion:       row.querySelector('.item-desc').value,
            cantidad:          parseFloat(row.querySelector('.item-qty').value) || 0,
            precio_unitario:   parseFloat(row.querySelector('.item-price').value) || 0,
            aplica_iva:        row.querySelector('.item-iva').checked,
        });
    });

    const payload = {
        _token:      CSRF,
        fecha:       document.getElementById('v-fecha').value,
        customer_id: document.getElementById('v-customer').value || null,
        tipo_venta:  document.getElementById('v-tipo').value,
        forma_pago:  document.getElementById('v-forma-pago').value,
        items,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.venta.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Registrar Venta';
        if (data.error) {
            document.getElementById('error-msg').textContent = data.error;
            document.getElementById('error-msg').classList.remove('hidden');
            return;
        }
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
        document.getElementById('exito-ref').textContent = data.referencia;
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Registrar Venta';
        document.getElementById('error-msg').textContent = 'Error de conexión.';
        document.getElementById('error-msg').classList.remove('hidden');
    });
}

// Agregar primer ítem al cargar
agregarItem();
</script>

@endsection
