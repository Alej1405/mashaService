@extends('mobile.layout')
@section('title', 'Categorías')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.ecommerce.index') }}" class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Categorías</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $categorias->count() }} categorías</p>
    </div>
    <a href="{{ route('mobile.tienda.categorias.nuevo') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-emerald-300"
       style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);">+ Nueva</a>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm text-emerald-300" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>

@if($categorias->isEmpty())
    <div class="card p-8 text-center"><p class="text-sm text-white">Sin categorías registradas.</p></div>
@else
    <div class="space-y-2">
        @foreach($categorias as $cat)
        <div class="card p-4 flex items-center justify-between gap-3" id="item-{{ $cat->id }}">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-white">{{ $cat->nombre }}</p>
                    @if($cat->publicado)
                        <span class="text-xs px-1.5 py-0.5 rounded-full" style="background: rgba(16,185,129,0.1); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.2);">Pub.</span>
                    @endif
                </div>
                @if($cat->descripcion)
                <p class="text-xs truncate" style="color: rgba(232,230,240,0.45);">{{ $cat->descripcion }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('mobile.tienda.categorias.editar', $cat->id) }}"
                   class="text-xs px-2 py-1 rounded-lg text-indigo-300"
                   style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);">Editar</a>
                <button onclick="eliminar({{ $cat->id }}, '{{ route('mobile.tienda.categorias.eliminar', $cat->id) }}')" class="text-xs text-red-400">×</button>
            </div>
        </div>
        @endforeach
    </div>
@endif

<script>
const CSRF = '{{ csrf_token() }}';
async function eliminar(id, url) {
    if (!confirm('¿Eliminar esta categoría?')) return;
    const res = await fetch(url, { method: 'POST', headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}, body: '{}' });
    if ((await res.json()).success) { document.getElementById('item-' + id).remove(); document.getElementById('msg-ok').textContent = 'Eliminada.'; document.getElementById('msg-ok').classList.remove('hidden'); }
}
</script>

@endsection
