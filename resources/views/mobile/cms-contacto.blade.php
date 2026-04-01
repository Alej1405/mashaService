@extends('mobile.layout')
@section('title', 'Contacto')

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
        <h2 class="text-base font-bold text-white">Información de Contacto</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Dirección, teléfono y redes sociales</p>
    </div>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-emerald-300"
     style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm font-medium text-red-300"
     style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form-contacto" class="space-y-4">
    @csrf

    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Datos de Contacto</p>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Dirección</label>
            <input type="text" name="direccion" maxlength="300" value="{{ $contacto?->direccion }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Teléfono</label>
                <input type="text" name="telefono" maxlength="50" value="{{ $contacto?->telefono }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">WhatsApp</label>
                <input type="text" name="whatsapp" maxlength="50" value="{{ $contacto?->whatsapp }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Email</label>
            <input type="email" name="email" maxlength="150" value="{{ $contacto?->email }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
    </div>

    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Redes Sociales</p>
        @foreach([['facebook','Facebook'],['instagram','Instagram'],['linkedin','LinkedIn'],['youtube','YouTube'],['tiktok','TikTok']] as [$key,$label])
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">{{ $label }}</label>
            <input type="text" name="{{ $key }}" maxlength="300" value="{{ $contacto?->$key }}"
                   placeholder="URL o usuario"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        @endforeach
        <div class="flex items-center justify-between">
            <p class="text-sm text-white font-medium">Activo</p>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="activo" value="1" class="sr-only peer" {{ $contacto?->activo ? 'checked' : '' }}>
                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                     style="background: rgba(255,255,255,0.15);"></div>
            </label>
        </div>
    </div>

    <button type="submit" id="btn-guardar"
            class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
        Guardar Contacto
    </button>
</form>

<script>
document.getElementById('form-contacto').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const fd = new FormData(this);
    try {
        const res = await fetch('{{ route('mobile.cms.contacto.guardar') }}', {
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
    btn.disabled = false; btn.textContent = 'Guardar Contacto';
});
</script>

@endsection
