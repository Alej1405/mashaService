@extends('mobile.layout')
@section('title', 'Nueva Deuda')

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
        <h2 class="text-base font-bold text-white">Nueva Deuda</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Se registra en borrador. El admin la activa.</p>
    </div>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-emerald-300"
     style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-red-300"
     style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form-deuda" class="space-y-4">
    @csrf

    {{-- Tipo --}}
    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Información General</p>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Tipo de deuda</label>
            <select name="tipo" required
                    class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                    style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                <option value="prestamo_bancario">Préstamo Bancario</option>
                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                <option value="prestamo_personal">Préstamo Personal</option>
                <option value="prestamo_empresarial">Préstamo Empresarial</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Institución / Acreedor</label>
            <select name="bank_id" id="bank-select"
                    class="w-full rounded-xl px-3 py-2.5 text-sm text-white mb-2"
                    style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);"
                    onchange="autoAcreedor(this)">
                <option value="">— Seleccionar banco (opcional) —</option>
                @foreach($bancos as $banco)
                    <option value="{{ $banco->id }}" data-nombre="{{ $banco->nombre }}">{{ $banco->nombre }}</option>
                @endforeach
            </select>
            <input type="text" name="acreedor" id="acreedor-input" required
                   placeholder="Nombre del acreedor / institución"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>

        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">¿Para qué se solicitó?</label>
            <textarea name="descripcion" required rows="2" placeholder="Capital de trabajo, maquinaria, remodelación..."
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);"></textarea>
        </div>
    </div>

    {{-- Condiciones --}}
    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Condiciones del Préstamo</p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Monto ($)</label>
                <input type="number" name="monto_original" required min="0.01" step="0.01" placeholder="0.00"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Tasa TNA (%)</label>
                <input type="number" name="tasa_interes" required min="0" step="0.01" placeholder="15.60"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Desgravamen (% anual)</label>
                <input type="number" name="seguro_desgravamen_anual" min="0" step="0.0001" value="0" placeholder="0.35"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Plazo (meses)</label>
                <input type="number" name="plazo_meses" required min="1" step="1" placeholder="36"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Sistema de Amortización</label>
                <select name="sistema_amortizacion" required
                        class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                        style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                    <option value="frances">Francés (cuota fija)</option>
                    <option value="aleman">Alemán (capital fijo)</option>
                    <option value="americano">Americano (solo intereses)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Fecha de inicio</label>
                <input type="date" name="fecha_inicio" required value="{{ now()->format('Y-m-d') }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
        </div>
    </div>

    {{-- Notas --}}
    <div class="card p-4">
        <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Notas adicionales (opcional)</label>
        <textarea name="notas" rows="2" placeholder="Condiciones especiales, observaciones..."
                  class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                  style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);"></textarea>
    </div>

    <button type="submit" id="btn-guardar"
            class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
        Registrar Deuda en Borrador
    </button>
</form>

<script>
function autoAcreedor(sel) {
    const opt = sel.options[sel.selectedIndex];
    const input = document.getElementById('acreedor-input');
    if (opt.value) {
        input.value = opt.dataset.nombre || '';
    }
}

document.getElementById('form-deuda').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const data = Object.fromEntries(new FormData(this));

    try {
        const res = await fetch('{{ route('mobile.deuda.guardar') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token, 'Accept': 'application/json' },
            body: JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success) {
            document.getElementById('msg-ok').textContent = json.message;
            document.getElementById('msg-ok').classList.remove('hidden');
            document.getElementById('msg-err').classList.add('hidden');
            this.reset();
            document.getElementById('acreedor-input').value = '';
            setTimeout(() => document.getElementById('msg-ok').classList.add('hidden'), 5000);
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
    btn.textContent = 'Registrar Deuda en Borrador';
});
</script>

@endsection
