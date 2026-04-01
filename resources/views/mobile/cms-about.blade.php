@extends('mobile.layout')
@section('title', 'Nosotros')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.ecommerce.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Sección Nosotros</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Descripción de la empresa</p>
    </div>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-emerald-300"
     style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-red-300"
     style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form-about" class="space-y-4">
    @csrf
    <div class="card p-4 space-y-4">
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Título *</label>
            <input type="text" name="titulo" required maxlength="200"
                   value="{{ $about?->titulo }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Descripción</label>
            <textarea name="descripcion" rows="4"
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">{{ $about?->descripcion }}</textarea>
        </div>
        <div class="flex items-center justify-between">
            <p class="text-sm text-white font-medium">Activo</p>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="activo" value="1" class="sr-only peer" {{ $about?->activo ? 'checked' : '' }}>
                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                     style="background: rgba(255,255,255,0.15);"></div>
            </label>
        </div>
    </div>

    <button type="submit" id="btn-guardar"
            class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
        Guardar Nosotros
    </button>
</form>

<script>
document.getElementById('form-about').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const fd = new FormData(this);
    try {
        const res = await fetch('{{ route('mobile.cms.about.guardar') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': fd.get('_token'), 'Accept': 'application/json' },
            body: fd,
        });
        const json = await res.json();
        if (json.success) {
            document.getElementById('msg-ok').textContent = json.message;
            document.getElementById('msg-ok').classList.remove('hidden');
            document.getElementById('msg-err').classList.add('hidden');
            setTimeout(() => document.getElementById('msg-ok').classList.add('hidden'), 4000);
        } else {
            document.getElementById('msg-err').textContent = json.error || 'Error al guardar.';
            document.getElementById('msg-err').classList.remove('hidden');
        }
    } catch { document.getElementById('msg-err').textContent = 'Error de conexión.'; document.getElementById('msg-err').classList.remove('hidden'); }
    btn.disabled = false; btn.textContent = 'Guardar Nosotros';
});
</script>

@endsection
