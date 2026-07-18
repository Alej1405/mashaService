@extends('portal.layout')
@section('content')

@php use Illuminate\Support\Facades\Storage; @endphp

<form method="POST" action="{{ route('portal.web.update', $empresa->slug) }}" enctype="multipart/form-data" class="space-y-5 max-w-2xl">
    @csrf

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mi página web</h1>
        <p class="text-sm text-gray-500">Así te ven tus clientes en la web. Estos datos alimentan tu landing pública.</p>
        @if($customer->slug)
            <a href="{{ $customer->landingUrl() }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1 mt-1 text-xs font-medium text-indigo-600 hover:underline">
                Ver mi página
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        @endif
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div>
            <label for="descripcion_web" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea id="descripcion_web" name="descripcion_web" rows="3" maxlength="2000"
                      class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Cuenta de qué se trata tu negocio.">{{ old('descripcion_web', $web->descripcion_web) }}</textarea>
        </div>

        <div>
            <label for="horario" class="block text-sm font-medium text-gray-700 mb-1">Horario de atención</label>
            <input type="text" id="horario" name="horario" maxlength="180" value="{{ old('horario', $web->horario) }}"
                   class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="Lun a Vie 9:00 a 18:00">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="latitud" class="block text-sm font-medium text-gray-700 mb-1">Latitud</label>
                <input type="text" inputmode="decimal" id="latitud" name="latitud" value="{{ old('latitud', $web->latitud) }}"
                       class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="-0.1807">
            </div>
            <div>
                <label for="longitud" class="block text-sm font-medium text-gray-700 mb-1">Longitud</label>
                <input type="text" inputmode="decimal" id="longitud" name="longitud" value="{{ old('longitud', $web->longitud) }}"
                       class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="-78.4678">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($web->logo)
                    <img src="{{ Storage::disk('public')->url($web->logo) }}" alt="Logo" class="w-16 h-16 rounded-lg object-cover border border-gray-200 mb-2">
                @endif
                <input type="file" name="logo" accept="image/*" class="block w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-semibold hover:file:bg-indigo-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Banner / portada</label>
                @if($web->banner)
                    <img src="{{ Storage::disk('public')->url($web->banner) }}" alt="Banner" class="w-full h-16 rounded-lg object-cover border border-gray-200 mb-2">
                @endif
                <input type="file" name="banner" accept="image/*" class="block w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-semibold hover:file:bg-indigo-100">
            </div>
        </div>
    </div>

    <div>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-5 py-2.5 shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Guardar cambios
        </button>
    </div>
</form>

@endsection
