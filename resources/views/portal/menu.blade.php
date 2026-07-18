@extends('portal.layout')
@section('content')

@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mi menú</h1>
        <p class="text-sm text-gray-500">Arma tu carta. Marca promociones y comparte el QR para que tus clientes lo vean.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- QR del menú --}}
    @if($customer->slug)
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            <div class="shrink-0 w-24 h-24 [&>svg]:w-full [&>svg]:h-full">{!! $customer->qrSvg(180) !!}</div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-800">Código QR de tu menú</p>
                <p class="text-xs text-gray-500 mb-1">Imprímelo o compártelo. Al escanearlo se abre tu carta.</p>
                <a href="{{ $customer->landingUrl() }}" target="_blank" rel="noopener" class="text-xs font-medium text-indigo-600 hover:underline break-all">{{ $customer->landingUrl() }}</a>
            </div>
        </div>
    @endif

    {{-- Alta de producto --}}
    <div x-data="{ promo: false }" class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Agregar producto</h2>
        <form method="POST" action="{{ route('portal.menu.items.store', $empresa->slug) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre</label>
                    <input type="text" name="nombre" required maxlength="200" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej. Hamburguesa clásica">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Precio</label>
                    <input type="text" inputmode="decimal" name="precio" required class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Detalle <span class="text-gray-400 font-normal">(opcional)</span></label>
                <textarea name="descripcion" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ingredientes, tamaño, etc."></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Imagen <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input type="file" name="imagen" accept="image/*" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-semibold hover:file:bg-indigo-100">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 select-none">
                    <input type="checkbox" name="es_promocion" value="1" x-model="promo" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Es promoción
                </label>
                <div x-show="promo" x-cloak>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Precio promo</label>
                    <input type="text" inputmode="decimal" name="precio_promo" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-4 py-2.5 shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Agregar al menú
            </button>
        </form>
    </div>

    {{-- Lista de productos --}}
    <div class="space-y-2">
        <h2 class="text-sm font-semibold text-gray-800">Productos ({{ $items->count() }})</h2>

        @forelse($items as $item)
            <div x-data="{ edit: false }" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {{-- Fila resumen --}}
                <div class="flex items-center gap-3 p-3">
                    @if($item->imagen)
                        <img src="{{ Storage::disk('public')->url($item->imagen) }}" alt="{{ $item->nombre }}" class="w-12 h-12 rounded-lg object-cover shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-gray-100 shrink-0 flex items-center justify-center text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $item->nombre }}</p>
                            @if($item->es_promocion)
                                <span class="shrink-0 inline-block rounded-full bg-rose-100 text-rose-700 text-[10px] font-semibold px-2 py-0.5">Promo</span>
                            @endif
                            @unless($item->activo)
                                <span class="shrink-0 inline-block rounded-full bg-gray-100 text-gray-500 text-[10px] font-semibold px-2 py-0.5">Oculto</span>
                            @endunless
                        </div>
                        <p class="text-sm text-gray-600">
                            @if($item->es_promocion && $item->precio_promo !== null)
                                <span class="line-through text-gray-400">${{ number_format($item->precio, 2) }}</span>
                                <span class="font-semibold text-rose-600">${{ number_format($item->precio_promo, 2) }}</span>
                            @else
                                <span class="font-semibold text-gray-800">${{ number_format($item->precio, 2) }}</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" @click="edit = !edit" class="shrink-0 text-xs font-medium text-indigo-600 hover:underline px-2 py-1">Editar</button>
                    <form method="POST" action="{{ route('portal.menu.items.destroy', [$empresa->slug, $item->id]) }}" onsubmit="return confirm('¿Eliminar este producto del menú?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="shrink-0 text-xs font-medium text-gray-400 hover:text-red-600 px-2 py-1">Eliminar</button>
                    </form>
                </div>

                {{-- Edición inline --}}
                <div x-show="edit" x-cloak class="border-t border-gray-100 p-3 bg-gray-50" x-data="{ promo: {{ $item->es_promocion ? 'true' : 'false' }} }">
                    <form method="POST" action="{{ route('portal.menu.items.update', [$empresa->slug, $item->id]) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre</label>
                                <input type="text" name="nombre" required maxlength="200" value="{{ $item->nombre }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Precio</label>
                                <input type="text" inputmode="decimal" name="precio" required value="{{ $item->precio }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Detalle</label>
                            <textarea name="descripcion" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $item->descripcion }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Imagen</label>
                                <input type="file" name="imagen" accept="image/*" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-semibold hover:file:bg-indigo-100">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 select-none">
                                <input type="checkbox" name="es_promocion" value="1" x-model="promo" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Promoción
                            </label>
                            <div x-show="promo" x-cloak>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Precio promo</label>
                                <input type="text" inputmode="decimal" name="precio_promo" value="{{ $item->precio_promo }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 select-none">
                                <input type="checkbox" name="activo" value="1" @checked($item->activo) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Visible
                            </label>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-4 py-2 shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition">Guardar</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center">
                <p class="text-gray-500 text-sm">Tu menú está vacío. Agrega tu primer producto arriba.</p>
            </div>
        @endforelse
    </div>

</div>

<style>[x-cloak]{display:none!important}</style>

@endsection
