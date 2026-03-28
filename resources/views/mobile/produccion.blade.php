@extends('mobile.layout')
@section('title', 'Orden de Producción')

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
        <h2 class="text-base font-bold text-white">Nueva Orden de Producción</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Se guardará como borrador</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    <div class="card p-4 space-y-3">

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Presentación del Producto *</label>
            @if($presentaciones->isEmpty())
                <p class="text-xs py-3 text-center rounded-xl" style="color:rgba(232,230,240,0.4); background:rgba(255,255,255,0.04);">
                    No hay presentaciones activas. Créalas desde el panel ERP.
                </p>
            @else
            <select id="p-presentacion" class="input w-full px-3 py-2.5 text-sm">
                <option value="">— Selecciona una presentación —</option>
                @foreach($presentaciones as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->productDesign->nombre }} · {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
            @endif
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Fecha *</label>
            <input type="date" id="p-fecha" class="input w-full px-3 py-2.5 text-sm"
                   value="{{ now()->toDateString() }}">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Cantidad a producir *</label>
            <input type="number" id="p-cantidad" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="0" min="0.001" step="0.001">
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Notas</label>
            <textarea id="p-notas" rows="3" class="input w-full px-3 py-2.5 text-sm"
                      placeholder="Observaciones opcionales..."></textarea>
        </div>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarOrden()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold"
            @if($presentaciones->isEmpty()) disabled style="opacity:0.4;" @endif>
        Crear Orden de Producción
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3);">
        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-2">¡Orden creada!</h3>
    <p id="exito-ref" class="text-sm text-amber-400 mb-6"></p>
    <a href="{{ route('mobile.index') }}" class="btn-primary px-8 py-3 text-sm inline-block">Volver al inicio</a>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function guardarOrden() {
    const payload = {
        _token:                   CSRF,
        product_presentation_id:  document.getElementById('p-presentacion').value,
        fecha:                    document.getElementById('p-fecha').value,
        cantidad_producida:       document.getElementById('p-cantidad').value,
        notas:                    document.getElementById('p-notas').value,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Creando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.produccion.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Crear Orden de Producción';
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
        btn.disabled = false; btn.textContent = 'Crear Orden de Producción';
        document.getElementById('error-msg').textContent = 'Error de conexión.';
        document.getElementById('error-msg').classList.remove('hidden');
    });
}
</script>

@endsection
