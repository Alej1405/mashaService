@extends('mobile.layout')
@section('title', 'Nueva Carga')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.logistica.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Nueva Carga</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Registrar paquete recibido</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    {{-- Identificación --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Identificación</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Descripción del contenido *</label>
            <textarea id="c-descripcion" rows="2" class="input w-full px-3 py-2.5 text-sm"
                      placeholder="Ej. Ropa, electrónicos, accesorios..."></textarea>
        </div>
        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Número de tracking</label>
            <input type="text" id="c-tracking" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="1Z999AA10123456784">
        </div>
        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Referencia interna</label>
            <input type="text" id="c-referencia" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Opcional">
        </div>
    </div>

    {{-- Cliente y bodega --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Cliente y origen</p>

        @if($clientes->isNotEmpty())
        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Cliente</label>
            <select id="c-cliente" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Sin asignar —</option>
                @foreach($clientes as $cli)
                    <option value="sc_{{ $cli->id }}">{{ $cli->nombre_completo }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Bodega de origen *</label>
            <select id="c-bodega" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Seleccionar bodega —</option>
                @foreach($bodegas as $bodega)
                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }} ({{ $bodega->pais }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Dimensiones y valor --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">
            Peso y valor <span style="color: rgba(232,230,240,0.25);">(opcional)</span>
        </p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Peso (kg)</label>
                <input type="number" id="c-peso" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0.000" step="0.001" min="0">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Valor declarado ($)</label>
                <input type="number" id="c-valor" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0.00" step="0.01" min="0">
            </div>
        </div>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarCarga()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        Registrar Carga
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(6,182,212,0.15); border:1px solid rgba(6,182,212,0.3);">
        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-1">¡Carga registrada!</h3>
    <p id="exito-tracking" class="text-sm text-cyan-400 mb-6 font-mono"></p>
    <div class="flex flex-col gap-3 w-full">
        <button onclick="resetForm()" class="btn-primary py-3 text-sm">Registrar otra carga</button>
        <a href="{{ route('mobile.logistica.cargas.lista') }}"
           class="block py-3 text-sm text-center rounded-xl"
           style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Ver todas las cargas
        </a>
        <a href="{{ route('mobile.logistica.index') }}"
           class="block py-3 text-sm text-center rounded-xl"
           style="color: rgba(232,230,240,0.4);">
            Volver a logística
        </a>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function guardarCarga() {
    const descripcion = document.getElementById('c-descripcion').value.trim();
    const bodegaId    = document.getElementById('c-bodega').value;

    if (!descripcion) { mostrarError('La descripción es obligatoria.'); return; }
    if (!bodegaId)    { mostrarError('Selecciona una bodega de origen.'); return; }

    const clienteEl = document.getElementById('c-cliente');
    const trackingEl = document.getElementById('c-tracking');
    const refEl      = document.getElementById('c-referencia');
    const pesoEl     = document.getElementById('c-peso');
    const valorEl    = document.getElementById('c-valor');

    const body = {
        descripcion,
        bodega_id:         bodegaId,
        store_customer_id: clienteEl ? clienteEl.value : null,
        numero_tracking:   trackingEl.value.trim() || null,
        referencia:        refEl.value.trim() || null,
        peso_kg:           pesoEl.value || null,
        valor_declarado:   valorEl.value || null,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Registrando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.logistica.carga.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.textContent = 'Registrar Carga';
        if (!ok) {
            const msg = data.errors ? Object.values(data.errors).flat()[0] : (data.error || data.message || 'Error.');
            mostrarError(msg);
            return;
        }
        document.getElementById('exito-tracking').textContent = data.tracking;
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Registrar Carga';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetForm() {
    document.getElementById('c-descripcion').value = '';
    document.getElementById('c-tracking').value    = '';
    document.getElementById('c-referencia').value  = '';
    document.getElementById('c-peso').value        = '';
    document.getElementById('c-valor').value       = '';
    const cli = document.getElementById('c-cliente');
    if (cli) cli.value = '';
    document.getElementById('c-bodega').value = '';
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
