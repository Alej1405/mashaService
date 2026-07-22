@extends('portal.layout')
@section('content')

@php
    use Illuminate\Support\Facades\Storage;
    $cp = old('color_primario', $web->color_primario);
    $cs = old('color_secundario', $web->color_secundario);
    $ca = old('color_acento', $web->color_acento);
    $tieneColores = filled($cp) || filled($cs) || filled($ca);
    // El portal es cross-tenant (sesión, sin tenant de Filament). Si el cliente abre
    // /mi-web con una sesión de admin/app activa en el mismo navegador, EmpresaScope
    // filtraría la galería y se vería vacía → withoutGlobalScopes la hace determinista.
    $imagenes = $customer->webImages()->withoutGlobalScopes()->get();
@endphp

<style>
    /* Motion solo de feedback de estado; se anula bajo reduced-motion. */
    @media (prefers-reduced-motion: reduce) { .wb-tr { transition: none !important; } }
</style>

<form method="POST" action="{{ route('portal.web.update', $empresa->slug) }}" enctype="multipart/form-data" class="space-y-5 max-w-2xl">
    @csrf

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mi página web</h1>
        <p class="text-sm text-gray-500">Así te ven tus clientes en la web. Estos datos alimentan tu landing pública.</p>
        @if($customer->slug)
            <a href="{{ $customer->landingUrl() }}" target="_blank" rel="noopener"
               class="wb-tr inline-flex items-center gap-1 mt-1 text-xs font-medium text-indigo-600 hover:underline">
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

    {{-- ── Identidad visual: colores de marca (los define el cliente) ── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5"
         x-data="{ custom: {{ $tieneColores ? 'true' : 'false' }}, p: '{{ $cp ?: '#4f46e5' }}', s: '{{ $cs ?: '#0f172a' }}', a: '{{ $ca ?: '#f59e0b' }}' }">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Identidad visual</h2>
                <p class="text-xs text-gray-500 mt-0.5">Los colores de tu marca para tu página pública. Si lo dejas apagado, usamos un estilo neutro.</p>
            </div>
            <button type="button" role="switch" :aria-checked="custom" @click="custom = !custom"
                    class="wb-tr relative shrink-0 w-11 h-6 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                    :class="custom ? 'bg-indigo-600' : 'bg-gray-200'">
                <span class="wb-tr absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow" :class="custom ? 'translate-x-5' : ''"></span>
            </button>
        </div>

        <div x-show="custom" x-transition.opacity.duration.200ms class="mt-4 space-y-4" x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach(['p' => 'Primario', 's' => 'Secundario', 'a' => 'Acento'] as $var => $label)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ $label }}</label>
                        <div class="wb-tr flex items-center gap-2 rounded-lg border border-gray-200 pl-1.5 pr-2 py-1.5 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                            <input type="color" x-model="{{ $var }}" :disabled="!custom" aria-label="{{ $label }}"
                                   class="w-7 h-7 shrink-0 cursor-pointer rounded-md border border-gray-200 bg-transparent p-0 disabled:cursor-not-allowed [&::-webkit-color-swatch-wrapper]:p-0.5 [&::-webkit-color-swatch]:rounded [&::-webkit-color-swatch]:border-0 [&::-moz-color-swatch]:rounded [&::-moz-color-swatch]:border-0">
                            <input type="text" name="color_{{ ['p'=>'primario','s'=>'secundario','a'=>'acento'][$var] }}"
                                   x-model="{{ $var }}" :disabled="!custom" maxlength="7" spellcheck="false"
                                   class="w-full border-0 p-0 text-sm font-mono uppercase text-gray-700 focus:ring-0 bg-transparent" placeholder="#4F46E5">
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Vista previa en vivo --}}
            <div class="flex items-center gap-3 rounded-lg bg-gray-50 border border-gray-100 px-3 py-2.5">
                <span class="text-xs text-gray-500">Vista previa</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full border border-black/5" :style="{ backgroundColor: p }"></span>
                    <span class="w-5 h-5 rounded-full border border-black/5" :style="{ backgroundColor: s }"></span>
                    <span class="w-5 h-5 rounded-full border border-black/5" :style="{ backgroundColor: a }"></span>
                </span>
                <span class="ml-auto inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg text-white shadow-sm" :style="{ backgroundColor: p }">Botón de ejemplo</span>
            </div>
        </div>
    </div>

    {{-- ── Galería: hasta 5 imágenes para la landing ── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5" x-data="galeriaWeb({{ $imagenes->count() }})">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Galería</h2>
                <p class="text-xs text-gray-500 mt-0.5">Hasta 5 imágenes que se muestran en distintas partes de tu página.</p>
            </div>
            <span class="wb-tr text-xs font-semibold tabular-nums px-2 py-1 rounded-md"
                  :class="total > 5 ? 'text-red-600 bg-red-50' : 'text-gray-500 bg-gray-50'"
                  x-text="`${total} / 5`"></span>
        </div>

        @if($imagenes->count())
            <div class="mt-4 grid grid-cols-3 sm:grid-cols-5 gap-2.5">
                @foreach($imagenes as $img)
                    <label class="group relative aspect-square rounded-lg overflow-hidden border border-gray-200 cursor-pointer block"
                           x-data="{ rm: false }">
                        <img src="{{ Storage::disk('public')->url($img->imagen) }}" alt="{{ $img->alt ?? 'Imagen de la galería' }}"
                             class="wb-tr w-full h-full object-cover" :class="rm ? 'opacity-30 grayscale' : ''">
                        <input type="checkbox" name="remove_images[]" value="{{ $img->id }}" class="sr-only"
                               x-model="rm" @change="removed += $event.target.checked ? 1 : -1">
                        <span class="wb-tr absolute top-1 right-1 w-6 h-6 rounded-full grid place-items-center text-white shadow-sm"
                              :class="rm ? 'bg-red-500' : 'bg-black/45 group-hover:bg-black/65'">
                            <svg x-show="!rm" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <svg x-show="rm" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-400">Toca una imagen para marcarla y quitarla al guardar.</p>
        @endif

        <label class="wb-tr mt-3 flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/40"
               :class="total >= 5 ? 'opacity-50 pointer-events-none' : ''">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <span class="text-sm font-medium text-gray-700">Agregar imágenes</span>
            <span class="text-xs text-gray-400" x-text="total >= 5 ? 'Llegaste al máximo de 5' : `Puedes agregar ${Math.max(0, 5 - (existing - removed))} más`"></span>
            <input type="file" name="galeria[]" accept="image/*" multiple class="sr-only" x-ref="fileInput" @change="onPick($event)">
        </label>

        <div class="mt-2.5 grid grid-cols-3 sm:grid-cols-5 gap-2.5" x-show="previews.length" x-cloak>
            <template x-for="(src, i) in previews" :key="src">
                <div class="relative aspect-square rounded-lg overflow-hidden border border-indigo-200">
                    <img :src="src" alt="" class="w-full h-full object-cover">
                    <span class="absolute bottom-1 left-1 text-[10px] font-semibold text-white bg-indigo-600 px-1.5 py-0.5 rounded">Nueva</span>
                    <button type="button" @click="removeAt(i)" aria-label="Quitar imagen"
                            class="wb-tr absolute top-1 right-1 w-6 h-6 rounded-full grid place-items-center text-white bg-black/45 hover:bg-red-500 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>
        <p class="mt-2 text-xs text-red-600" x-show="total > 5" x-cloak>Te pasaste del máximo de 5. Quita algunas (toca la <span class="font-semibold">✕</span> en las nuevas o marca las existentes) antes de guardar.</p>
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

    // Galería: administra un array de File (se puede quitar de a una) y reconstruye
    // el FileList del input con DataTransfer. total = existentes - quitadas + nuevas.
    function galeriaWeb(existing) {
        return {
            existing,
            removed: 0,
            files: [],
            previews: [],
            get total() { return this.existing - this.removed + this.files.length; },
            onPick(e) {
                this.files = this.files.concat(Array.from(e.target.files || []));
                this.sync();
            },
            removeAt(i) {
                this.files.splice(i, 1);
                this.sync();
            },
            sync() {
                // Reconstruye el FileList del input desde el array (permite quitar de a una).
                const dt = new DataTransfer();
                this.files.forEach(f => dt.items.add(f));
                this.$refs.fileInput.files = dt.files;
                this.previews.forEach(URL.revokeObjectURL);
                this.previews = this.files.map(f => URL.createObjectURL(f));
            },
        };
    }
</script>

@endsection
