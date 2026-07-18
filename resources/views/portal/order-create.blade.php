@extends('portal.layout')
@section('content')

@php
    use Illuminate\Support\Facades\Storage;

    $productosData = $productos->map(fn ($p) => [
        'id'        => $p->id,
        'nombre'    => $p->nombre,
        'precio'    => (float) $p->precio_venta,
        'categoria' => $p->storeCategory?->nombre,
    ])->values();
@endphp

<form method="POST" action="{{ route('portal.orders.store', $empresa->slug) }}"
      x-data="pedido(@js($productosData))"
      class="space-y-5 pb-28">
    @csrf

    {{-- Encabezado --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <a href="{{ route('portal.orders', $empresa->slug) }}"
               class="text-xs text-gray-500 hover:text-gray-800 inline-flex items-center gap-1 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Mis órdenes
            </a>
            <h1 class="text-xl font-bold text-gray-800">Nuevo pedido</h1>
            <p class="text-sm text-gray-500">Elige los productos y las cantidades. El total final se confirma con {{ $empresa->name }}.</p>
        </div>
    </div>

    @error('items')
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if($productos->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
            <p class="text-gray-500 text-sm">Todavía no hay productos disponibles para pedir.</p>
        </div>
    @else
        {{-- Catálogo --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($productos as $p)
                @php $img = $p->imagen_principal ? Storage::disk('public')->url($p->imagen_principal) : null; @endphp
                <div class="bg-white rounded-xl border transition flex flex-col overflow-hidden"
                     :class="qty({{ $p->id }}) > 0 ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200'">
                    <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                        @if($img)
                            <img src="{{ $img }}" alt="{{ $p->nombre }}" class="w-full h-full object-cover" loading="lazy">
                        @else
                            <svg class="w-9 h-9 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                    <div class="p-3 flex flex-col flex-1 gap-2">
                        <div class="flex-1 min-w-0">
                            @if($p->storeCategory)
                                <span class="text-[10px] uppercase tracking-wide text-gray-400">{{ $p->storeCategory->nombre }}</span>
                            @endif
                            <p class="text-sm font-medium text-gray-800 leading-tight line-clamp-2">{{ $p->nombre }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">${{ number_format($p->precio_venta, 2) }}</p>
                        </div>

                        {{-- Stepper --}}
                        <div class="flex items-center justify-between gap-2">
                            <button type="button" @click="dec({{ $p->id }})"
                                    class="w-8 h-8 shrink-0 rounded-lg border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-gray-50 active:scale-95 transition disabled:opacity-40"
                                    :disabled="qty({{ $p->id }}) === 0" aria-label="Quitar uno">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <input type="text" inputmode="numeric"
                                   class="w-12 text-center text-sm font-semibold text-gray-800 border-0 focus:ring-0 p-0"
                                   :value="qty({{ $p->id }})"
                                   @input="setQty({{ $p->id }}, $event.target.value)">
                            <button type="button" @click="inc({{ $p->id }})"
                                    class="w-8 h-8 shrink-0 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-100 active:scale-95 transition"
                                    aria-label="Agregar uno">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Notas --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <label for="notas" class="block text-sm font-medium text-gray-700 mb-1">Notas para el pedido <span class="text-gray-400 font-normal">(opcional)</span></label>
            <textarea id="notas" name="notas" rows="2" maxlength="500"
                      class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Ej. entregar en la mañana, sin cebolla, etc.">{{ old('notas') }}</textarea>
        </div>

        {{-- Inputs ocultos con las líneas del carrito --}}
        <template x-for="linea in lineas()" :key="linea.id">
            <span class="hidden">
                <input type="hidden" :name="`items[${linea.id}][id]`" :value="linea.id">
                <input type="hidden" :name="`items[${linea.id}][cantidad]`" :value="linea.cantidad">
            </span>
        </template>

        {{-- Resumen fijo --}}
        <div class="fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-gray-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">
                        <span x-text="totalItems()">0</span> <span x-text="totalItems() === 1 ? 'unidad' : 'unidades'">unidades</span>
                    </p>
                    <p class="text-lg font-bold text-gray-900 leading-tight">
                        <span x-text="'$' + totalEstimado().toFixed(2)">$0.00</span>
                        <span class="text-xs font-normal text-gray-400">estimado</span>
                    </p>
                </div>
                <button type="submit"
                        class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-5 py-3 shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition disabled:opacity-40 disabled:pointer-events-none"
                        :disabled="totalItems() === 0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Confirmar pedido
                </button>
            </div>
        </div>
    @endif
</form>

<script>
    function pedido(productos) {
        return {
            productos: productos,
            cart: {},
            qty(id) { return this.cart[id] || 0; },
            inc(id) { this.cart[id] = this.qty(id) + 1; },
            dec(id) { var n = this.qty(id) - 1; if (n <= 0) { delete this.cart[id]; } else { this.cart[id] = n; } },
            setQty(id, v) {
                var n = Math.max(0, Math.floor(Number(v) || 0));
                if (n <= 0) { delete this.cart[id]; } else { this.cart[id] = n; }
            },
            lineas() {
                return this.productos
                    .filter(function (p) { return (this.cart[p.id] || 0) > 0; }.bind(this))
                    .map(function (p) { return { id: p.id, cantidad: this.cart[p.id] }; }.bind(this));
            },
            totalItems() {
                var t = 0; for (var k in this.cart) { t += this.cart[k]; } return t;
            },
            totalEstimado() {
                var t = 0;
                this.productos.forEach(function (p) { t += (this.cart[p.id] || 0) * p.precio; }.bind(this));
                return t;
            },
        };
    }
</script>

@endsection
