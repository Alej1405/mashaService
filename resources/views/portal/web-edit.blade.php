@extends('portal.layout')
@section('content')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

<style>
    /* Motion solo de feedback de estado; se anula bajo reduced-motion. */
    @media (prefers-reduced-motion: reduce) { .wb-tr { transition: none !important; } }
</style>

<form method="POST" action="{{ route('portal.web.update', $empresa->slug) }}" enctype="multipart/form-data" class="space-y-5 max-w-2xl">
    @csrf

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mi información</h1>
        <p class="text-sm text-gray-500">Así te ven tus clientes: es la ficha que se abre con tu nombre.</p>
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
                      class="wb-tr w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                      placeholder="Cuenta de qué se trata tu negocio.">{{ old('descripcion_web', $web->descripcion_web) }}</textarea>
        </div>

        <div>
            <label for="horario" class="block text-sm font-medium text-gray-700 mb-1">Horario de atención</label>
            <input type="text" id="horario" name="horario" maxlength="180" value="{{ old('horario', $web->horario) }}"
                   class="wb-tr w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="Lun a Vie 9:00 a 18:00">
        </div>

        {{-- Ubicación: geolocalización del navegador o link de Google Maps --}}
        <div x-data="ubicacionWeb({ lat: '{{ old('latitud', $web->latitud) }}', lng: '{{ old('longitud', $web->longitud) }}', maps: '{{ old('google_maps_url', $web->google_maps_url) }}' })" class="space-y-2.5">
            <label class="block text-sm font-medium text-gray-700">Ubicación</label>
            <input type="hidden" name="latitud" x-model="lat">
            <input type="hidden" name="longitud" x-model="lng">

            <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                <button type="button" @click="detectar()" :disabled="detectando"
                        class="wb-tr w-full sm:w-auto shrink-0 inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:border-indigo-400 hover:bg-indigo-50/40 active:scale-[0.98] disabled:opacity-60">
                    <svg x-show="!detectando" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="detectando" x-cloak class="w-4 h-4 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="detectando ? 'Detectando…' : 'Usar mi ubicación'"></span>
                </button>

                <span class="hidden sm:block text-xs text-gray-400 shrink-0">o</span>

                <label class="wb-tr flex flex-1 min-w-0 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 cursor-text focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <input type="url" name="google_maps_url" x-model="maps" @input.debounce.400ms="parseMaps()"
                           placeholder="Pega el enlace de Google Maps"
                           class="w-full border-0 p-0 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-0 bg-transparent">
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs min-h-[1.25rem]">
                <template x-if="lat && lng">
                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                        Ubicación lista
                        <span class="font-mono text-gray-400" x-text="`(${(+lat).toFixed(5)}, ${(+lng).toFixed(5)})`"></span>
                        <a :href="`https://www.google.com/maps?q=${lat},${lng}`" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Ver en mapa</a>
                    </span>
                </template>
                <template x-if="!(lat && lng)">
                    <span class="text-gray-400">Sin ubicación. Usa el botón o pega un link de Google Maps.</span>
                </template>
                <span x-show="hint" x-cloak class="text-gray-400" x-text="hint"></span>
                <span x-show="error" x-cloak class="text-red-600" x-text="error"></span>
            </div>
        </div>

        <div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($web->logo)
                    <img src="{{ Storage::disk('public')->url($web->logo) }}" alt="Logo" class="w-16 h-16 rounded-lg object-cover border border-gray-200 mb-2">
                @endif
                <input type="file" name="logo" accept="image/*" class="block w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-semibold hover:file:bg-indigo-100">
            </div>
        </div>
    </div>

    <div>
        <button type="submit" class="wb-tr inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-5 py-2.5 shadow-sm hover:bg-indigo-700 active:scale-[0.98]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Guardar cambios
        </button>
    </div>
</form>

<script>
    // Ubicación: geolocalización del navegador + parseo del link de Google Maps.
    function ubicacionWeb(init) {
        const PATRONES = [
            /@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/,
            /[?&](?:q|query|ll|destination)=(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/,
            /!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/,
        ];
        return {
            lat: init.lat || '', lng: init.lng || '', maps: init.maps || '',
            detectando: false, error: '', hint: '',
            detectar() {
                this.error = ''; this.hint = '';
                if (!navigator.geolocation) { this.error = 'Tu navegador no soporta geolocalización.'; return; }
                this.detectando = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.lat = pos.coords.latitude.toFixed(6);
                        this.lng = pos.coords.longitude.toFixed(6);
                        this.detectando = false;
                        this.hint = 'Ubicación detectada.';
                    },
                    (err) => {
                        this.detectando = false;
                        this.error = err.code === 1 ? 'Permiso de ubicación denegado.' : 'No pudimos obtener tu ubicación.';
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            },
            parseMaps() {
                this.error = ''; this.hint = '';
                const u = (this.maps || '').trim();
                if (!u) return;
                for (const re of PATRONES) {
                    const m = u.match(re);
                    if (m) { this.lat = m[1]; this.lng = m[2]; this.hint = 'Ubicación tomada del link.'; return; }
                }
                if (/goo\.gl|maps\.app\.goo\.gl/.test(u)) { this.hint = 'Link corto: lo resolveremos al guardar.'; }
            },
        };
    }

</script>

@endsection
