@extends('mobile.layout')
@section('title', 'FAQs')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.ecommerce.index') }}" class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Preguntas Frecuentes</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $faqs->count() }} FAQs</p>
    </div>
    <a href="{{ route('mobile.cms.faqs.nuevo') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-blue-300"
       style="background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.25);">+ Nueva</a>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm text-emerald-300" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>

@if($faqs->isEmpty())
    <div class="card p-8 text-center"><p class="text-sm text-white">Sin FAQs registradas.</p></div>
@else
    <div class="space-y-2">
        @foreach($faqs as $f)
        <div class="card p-4 flex items-start justify-between gap-3" id="item-{{ $f->id }}">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white">{{ $f->pregunta }}</p>
                <p class="text-xs mt-0.5 line-clamp-1" style="color: rgba(232,230,240,0.45);">{{ $f->respuesta }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('mobile.cms.faqs.editar', $f->id) }}"
                   class="text-xs px-2 py-1 rounded-lg text-indigo-300"
                   style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);">Editar</a>
                <button onclick="eliminar({{ $f->id }}, '{{ route('mobile.cms.faqs.eliminar', $f->id) }}')" class="text-xs text-red-400">×</button>
            </div>
        </div>
        @endforeach
    </div>
@endif

<script>
const CSRF = '{{ csrf_token() }}';
async function eliminar(id, url) {
    if (!confirm('¿Eliminar esta FAQ?')) return;
    const res = await fetch(url, { method: 'POST', headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}, body: '{}' });
    if ((await res.json()).success) { document.getElementById('item-' + id).remove(); document.getElementById('msg-ok').textContent = 'Eliminada.'; document.getElementById('msg-ok').classList.remove('hidden'); }
}
</script>

@endsection
