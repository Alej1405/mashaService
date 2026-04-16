@extends('mobile.layout')
@section('title', 'Nuevo Embarque')

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
        <h2 class="text-base font-bold text-white">Nuevo Embarque</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Se generará número EMB-{{ now()->format('Y') }}-XXXXX</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    {{-- Tipo y bodega --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Datos del embarque</p>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Tipo de embarque *</label>
            <select id="e-tipo" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Seleccionar tipo —</option>
                @foreach(\App\Models\LogisticsShipment::TIPOS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Bodega de origen *</label>
            <select id="e-bodega" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Seleccionar bodega —</option>
                @foreach($bodegas as $bodega)
                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }} ({{ $bodega->pais }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Fechas --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">
            Fechas <span style="color: rgba(232,230,240,0.25);">(opcional)</span>
        </p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Fecha embarque</label>
                <input type="date" id="e-fecha-embarque" class="input w-full px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Llegada Ecuador</label>
                <input type="date" id="e-fecha-llegada" class="input w-full px-3 py-2.5 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">N.° guía aérea / BL</label>
            <input type="text" id="e-guia" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="Ej. 12345-XXXXX">
        </div>
    </div>

    {{-- Asignar cargas --}}
    @if($paquetesSinEmbarque->isNotEmpty())
    <div class="card p-4 space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">
                Cargas a embarcar
            </p>
            <label class="flex items-center gap-1.5 text-xs cursor-pointer" style="color: rgba(232,230,240,0.4);">
                <input type="checkbox" id="sel-todas"
                       onchange="document.querySelectorAll('.pkg-chk').forEach(c => c.checked = this.checked)"
                       class="rounded">
                Todas
            </label>
        </div>
        <div class="space-y-2 max-h-60 overflow-y-auto">
            @foreach($paquetesSinEmbarque as $pkg)
            <label class="flex items-center gap-3 px-3 py-2 rounded-xl cursor-pointer active:opacity-70"
                   style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                <input type="checkbox" class="pkg-chk rounded" value="{{ $pkg->id }}">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">
                        {{ $pkg->descripcion ?? 'Carga #' . $pkg->id }}
                    </p>
                    <p class="text-xs" style="color: rgba(232,230,240,0.35);">
                        {{ $pkg->numero_tracking ?? '—' }}
                        @if($pkg->storeCustomer) · {{ $pkg->storeCustomer->nombre }} @endif
                    </p>
                </div>
            </label>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Observaciones --}}
    <div class="card p-4">
        <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">
            Observaciones <span style="color: rgba(232,230,240,0.25);">(opcional)</span>
        </label>
        <textarea id="e-observaciones" rows="2" class="input w-full px-3 py-2.5 text-sm"
                  placeholder="Notas adicionales..."></textarea>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarEmbarque()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        Registrar Embarque
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3);">
        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-1">¡Embarque registrado!</h3>
    <p id="exito-numero" class="text-sm text-indigo-300 mb-1 font-mono"></p>
    <p id="exito-paquetes" class="text-xs mb-6" style="color:rgba(232,230,240,0.45);"></p>
    <div class="flex flex-col gap-3 w-full">
        <a href="{{ route('mobile.logistica.embarques.lista') }}"
           class="block btn-primary py-3 text-sm text-center">
            Ver embarques
        </a>
        <a href="{{ route('mobile.logistica.index') }}"
           class="block py-3 text-sm text-center rounded-xl"
           style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Volver a logística
        </a>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function guardarEmbarque() {
    const tipo    = document.getElementById('e-tipo').value;
    const bodega  = document.getElementById('e-bodega').value;
    if (!tipo)   { mostrarError('Selecciona el tipo de embarque.'); return; }
    if (!bodega) { mostrarError('Selecciona la bodega de origen.'); return; }

    const pkgIds = Array.from(document.querySelectorAll('.pkg-chk:checked')).map(c => parseInt(c.value));

    const body = {
        tipo,
        bodega_id:              bodega,
        fecha_embarque:         document.getElementById('e-fecha-embarque').value || null,
        fecha_llegada_ecuador:  document.getElementById('e-fecha-llegada').value || null,
        numero_guia_aerea:      document.getElementById('e-guia').value.trim() || null,
        observaciones:          document.getElementById('e-observaciones').value.trim() || null,
        package_ids:            pkgIds,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Registrando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.logistica.embarque.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.textContent = 'Registrar Embarque';
        if (!ok) {
            const msg = data.errors ? Object.values(data.errors).flat()[0] : (data.error || 'Error.');
            mostrarError(msg);
            return;
        }
        document.getElementById('exito-numero').textContent = data.numero;
        document.getElementById('exito-paquetes').textContent =
            data.paquetes > 0 ? `${data.paquetes} carga(s) asignada(s)` : 'Sin cargas asignadas aún';
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Registrar Embarque';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

@endsection
