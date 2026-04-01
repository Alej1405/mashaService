@extends('mobile.layout')
@section('title', $miembro ? 'Editar Miembro' : 'Nuevo Miembro')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.cms.equipo.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h2 class="text-base font-bold text-white">{{ $miembro ? 'Editar Miembro' : 'Nuevo Miembro' }}</h2>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm text-emerald-300" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm text-red-300" style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form" class="space-y-4">
    @csrf
    <input type="hidden" name="id" value="{{ $miembro?->id }}">

    <div class="card p-4 space-y-4">
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Nombre *</label>
            <input type="text" name="nombre" required maxlength="150" value="{{ $miembro?->nombre }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Cargo</label>
            <input type="text" name="cargo" maxlength="150" value="{{ $miembro?->cargo }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Biografía</label>
            <textarea name="bio" rows="3"
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">{{ $miembro?->bio }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Orden</label>
                <input type="number" name="sort_order" value="{{ $miembro?->sort_order ?? 0 }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div class="flex items-end pb-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="activo" value="1" class="sr-only peer" {{ ($miembro?->activo ?? true) ? 'checked' : '' }}>
                    <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                         style="background: rgba(255,255,255,0.15);"></div>
                    <span class="ml-2 text-xs text-white">Activo</span>
                </label>
            </div>
        </div>
    </div>

    <button type="submit" id="btn-guardar" class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">Guardar Miembro</button>
</form>

<script>
document.getElementById('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const fd = new FormData(this);
    const res = await fetch('{{ route('mobile.cms.equipo.guardar') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': fd.get('_token'),'Accept':'application/json'}, body: fd });
    const json = await res.json();
    if (json.success) { document.getElementById('msg-ok').textContent = json.message; document.getElementById('msg-ok').classList.remove('hidden'); setTimeout(() => window.location.href = '{{ route('mobile.cms.equipo.index') }}', 1200); }
    else { document.getElementById('msg-err').textContent = json.error || 'Error.'; document.getElementById('msg-err').classList.remove('hidden'); btn.disabled = false; btn.textContent = 'Guardar Miembro'; }
});
</script>

@endsection
