@extends('mobile.layout')
@section('title', $producto ? 'Editar Producto' : 'Nuevo Producto')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.tienda.productos.index') }}" class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h2 class="text-base font-bold text-white">{{ $producto ? 'Editar Producto' : 'Nuevo Producto' }}</h2>
</div>

<div id="msg-ok" class="hidden mb-4 p-3 rounded-xl text-sm text-emerald-300" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);"></div>
<div id="msg-err" class="hidden mb-4 p-3 rounded-xl text-sm text-red-300" style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);"></div>

<form id="form" class="space-y-4">
    @csrf
    <input type="hidden" name="id" value="{{ $producto?->id }}">

    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Información General</p>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Nombre *</label>
            <input type="text" name="nombre" required maxlength="200" value="{{ $producto?->nombre }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Descripción</label>
            <textarea name="descripcion" rows="3"
                      class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">{{ $producto?->descripcion }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Categoría</label>
            <select name="store_category_id"
                    class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                    style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                <option value="">— Sin categoría —</option>
                @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ $producto?->store_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Presentación vinculada</label>
            <select name="product_presentation_id"
                    class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                    style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                <option value="">— Sin presentación —</option>
                @foreach($presentaciones as $pres)
                <option value="{{ $pres->id }}" {{ $producto?->product_presentation_id == $pres->id ? 'selected' : '' }}>
                    {{ $pres->productDesign?->nombre }} — {{ $pres->nombre ?? 'Base' }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card p-4 space-y-4">
        <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Precios</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Precio Venta *</label>
                <input type="number" name="precio_venta" required min="0" step="0.01"
                       value="{{ $producto?->precio_venta }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Precio Distribuidor</label>
                <input type="number" name="precio_distribuidor" min="0" step="0.01"
                       value="{{ $producto?->precio_distribuidor }}"
                       class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1.5" style="color: rgba(232,230,240,0.7);">Orden</label>
            <input type="number" name="orden" value="{{ $producto?->orden ?? 0 }}"
                   class="w-full rounded-xl px-3 py-2.5 text-sm text-white"
                   style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
        </div>
        <div class="flex items-center justify-between">
            <p class="text-sm text-white font-medium">Publicado</p>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="publicado" value="1" class="sr-only peer" {{ ($producto?->publicado ?? false) ? 'checked' : '' }}>
                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                     style="background: rgba(255,255,255,0.15);"></div>
            </label>
        </div>
        <div class="flex items-center justify-between">
            <p class="text-sm text-white font-medium">Destacado</p>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="destacado" value="1" class="sr-only peer" {{ ($producto?->destacado ?? false) ? 'checked' : '' }}>
                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"
                     style="background: rgba(255,255,255,0.15);"></div>
            </label>
        </div>
    </div>

    <button type="submit" id="btn-guardar" class="w-full py-3.5 rounded-xl text-sm font-semibold text-white"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">Guardar Producto</button>
</form>

<script>
document.getElementById('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    const fd = new FormData(this);
    const res = await fetch('{{ route('mobile.tienda.productos.guardar') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': fd.get('_token'),'Accept':'application/json'}, body: fd });
    const json = await res.json();
    if (json.success) { document.getElementById('msg-ok').textContent = json.message; document.getElementById('msg-ok').classList.remove('hidden'); setTimeout(() => window.location.href = '{{ route('mobile.tienda.productos.index') }}', 1200); }
    else { document.getElementById('msg-err').textContent = json.error || 'Error.'; document.getElementById('msg-err').classList.remove('hidden'); btn.disabled = false; btn.textContent = 'Guardar Producto'; }
});
</script>

@endsection
