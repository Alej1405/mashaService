{{--
    Miniatura en vivo de la página que se edita.

    Se redibuja con cada cambio del formulario. Al poner el cursor en una
    parte del formulario, se marca aquí la zona de la página que cambia.
    Cada zona dice si sale del panel o del contenido por defecto.
--}}
@php
    $ruta = '/' . ($slug === 'inicio' ? '' : str_replace('erp-modulos', 'erp/modulos', (string) $slug));
    $deSeccion = fn (string $clave) => array_values(array_filter($bloques, fn ($b) => ($b['seccion'] ?? null) === $clave));
    $libres = array_values(array_filter($bloques, fn ($b) => empty($b['seccion'])));
    $nombre = fn (array $b) => ($b['titulo'] ?? null) ?: (($b['accion'] ?? null) ?: (($b['texto'] ?? null) ? \Illuminate\Support\Str::limit($b['texto'], 40) : ucfirst($b['tipo'] ?? 'bloque')));
@endphp

<div
    class="mn"
    data-mapa='@json($mapa)'
    x-data="{ activa: null }"
    x-on:focusin.window="
        const sec = $event.target.closest('[data-zona]');
        if (! sec || $root.contains($event.target)) return;
        let zona = sec.dataset.zona;
        if (zona === 'bloques') {
            const item = $event.target.closest('.fi-fo-repeater-item');
            if (item) {
                const hermanos = [...item.parentElement.children].filter((n) => n.classList.contains('fi-fo-repeater-item'));
                zona = JSON.parse($root.dataset.mapa)[hermanos.indexOf(item)] ?? 'libres';
            }
        }
        activa = zona;
                // Solo se mueve el recuadro de la miniatura. scrollIntoView movía
        // también la ventana y el formulario saltaba arriba al enfocar un campo.
        $nextTick(() => {
            const caja = $root.querySelector('.mn-pagina');
            const zonaEl = $root.querySelector('[data-mn=\'' + zona + '\']');
            if (! caja || ! zonaEl || ! caja.contains(zonaEl)) return;
            const arriba = zonaEl.offsetTop - 8;
            if (arriba < caja.scrollTop || arriba + zonaEl.offsetHeight > caja.scrollTop + caja.clientHeight) {
                caja.scrollTo({ top: arriba, behavior: 'smooth' });
            }
        });
    "
>
    <div class="mn-barra">
        <span class="mn-puntos"><i></i><i></i><i></i></span>
        <span class="mn-url">mashaec.net{{ $ruta }}</span>
    </div>

    <div class="mn-pagina">
        {{-- Encabezado --}}
        <div class="mn-zona mn-oscura" data-mn="encabezado" :class="{ 'mn-activa': activa === 'encabezado' }">
            <p class="mn-rotulo">Encabezado</p>
            <div class="mn-hero">
                <div>
                    <p class="mn-titulo">{{ $titulo ?: 'Sin título' }}</p>
                    @if ($texto)<p class="mn-texto">{{ \Illuminate\Support\Str::limit($texto, 120) }}</p>@endif
                    @foreach ($deSeccion($slug === 'inicio' ? 'apertura' : 'portada') as $b)
                        @if ($b['accion'] ?? null)<span class="mn-boton">{{ $b['accion'] }}</span>@endif
                    @endforeach
                </div>
                @if ($imagen)
                    <img class="mn-foto" src="{{ $imagen }}" alt="">
                @endif
            </div>
        </div>

        {{-- Secciones de la página --}}
        @foreach ($secciones as $clave => $etiqueta)
            @php $propios = $deSeccion($clave); @endphp
            <div class="mn-zona" data-mn="{{ $clave }}" :class="{ 'mn-activa': activa === '{{ $clave }}' }">
                <p class="mn-rotulo">
                    {{ $etiqueta }}
                    <span class="mn-chip {{ count($propios) ? 'mn-chip-panel' : '' }}">{{ count($propios) ? 'del panel' : 'por defecto' }}</span>
                </p>
                @foreach ($propios as $b)
                    <p class="mn-linea"><b>{{ $b['tipo'] ?? 'tarjeta' }}</b> {{ $nombre($b) }}</p>
                @endforeach
            </div>
        @endforeach

        {{-- Planes --}}
        @if (! is_null($planes))
            <div class="mn-zona" data-mn="planes" :class="{ 'mn-activa': activa === 'planes' }">
                <p class="mn-rotulo">
                    Planes
                    <span class="mn-chip {{ count($planes) ? 'mn-chip-panel' : '' }}">{{ count($planes) ? 'del panel' : 'por defecto' }}</span>
                </p>
                <div class="mn-planes">
                    @foreach ($planes as $plan)<span>{{ $plan }}</span>@endforeach
                </div>
            </div>
        @endif

        {{-- Bloques sin sección --}}
        <div class="mn-zona" data-mn="libres" :class="{ 'mn-activa': activa === 'libres' }">
            <p class="mn-rotulo">Al final de la página</p>
            @forelse ($libres as $b)
                <p class="mn-linea"><b>{{ $b['tipo'] ?? 'tarjeta' }}</b> {{ $nombre($b) }}</p>
            @empty
                <p class="mn-vacio">Nada. Un bloque sin sección aparece aquí.</p>
            @endforelse
        </div>
    </div>

    {{-- Cómo se comparte --}}
    <div class="mn-zona mn-compartir" data-mn="compartir" :class="{ 'mn-activa': activa === 'compartir' }">
        <p class="mn-rotulo">Al compartir el enlace</p>
        <div class="mn-tarjeta">
            @if ($imagen)<img src="{{ $imagen }}" alt="">@endif
            <div>
                <p class="mn-dominio">mashaec.net</p>
                <p class="mn-titulo-c">{{ $compartir['titulo'] ?: 'Sin título' }}</p>
                @if ($compartir['texto'])<p class="mn-texto-c">{{ \Illuminate\Support\Str::limit($compartir['texto'], 110) }}</p>@endif
            </div>
        </div>
    </div>

    <p class="mn-pie">Se publica en la web uno o dos minutos después de guardar.</p>
</div>

<style>
    .mn { border: 1px solid rgb(127 127 127 / .25); border-radius: 12px; overflow: hidden; font-size: 12px; background: rgb(127 127 127 / .04); }
    .mn-barra { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-bottom: 1px solid rgb(127 127 127 / .2); }
    .mn-puntos { display: flex; gap: 4px; }
    .mn-puntos i { width: 7px; height: 7px; border-radius: 50%; background: rgb(127 127 127 / .35); }
    .mn-url { opacity: .65; }
    .mn-pagina { position: relative; max-height: 62vh; overflow-y: auto; padding: 10px; display: grid; gap: 8px; }
    .mn-zona { border: 1.5px solid rgb(127 127 127 / .2); border-radius: 8px; padding: 10px; transition: border-color .15s, box-shadow .15s; }
    .mn-activa { border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 3px rgb(var(--primary-500) / .18); }
    .mn-oscura { background: #0d1010; color: #fff; }
    .mn-rotulo { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 10px; letter-spacing: .06em; text-transform: uppercase; opacity: .7; margin-bottom: 6px; }
    .mn-chip { font-size: 9px; letter-spacing: .04em; padding: 1px 6px; border-radius: 99px; background: rgb(127 127 127 / .18); }
    .mn-chip-panel { background: rgb(var(--success-500) / .2); color: rgb(var(--success-600)); }
    .mn-hero { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center; }
    .mn-titulo { font-size: 15px; font-weight: 700; line-height: 1.2; }
    .mn-texto { margin-top: 4px; opacity: .7; line-height: 1.4; }
    .mn-boton { display: inline-block; margin-top: 8px; margin-right: 4px; padding: 3px 8px; border-radius: 5px; background: #fff; color: #0d1010; font-weight: 600; font-size: 10px; }
    .mn-foto { width: 96px; height: 72px; object-fit: cover; border-radius: 6px; }
    .mn-linea { padding: 3px 0; border-top: 1px dashed rgb(127 127 127 / .2); }
    .mn-linea b { font-weight: 600; opacity: .55; margin-right: 4px; }
    .mn-vacio { opacity: .5; }
    .mn-planes { display: flex; flex-wrap: wrap; gap: 4px; }
    .mn-planes span { padding: 2px 7px; border: 1px solid rgb(127 127 127 / .3); border-radius: 5px; }
    .mn-compartir { margin: 0 10px 10px; }
    .mn-tarjeta { display: grid; grid-template-columns: 72px 1fr; gap: 8px; align-items: center; }
    .mn-tarjeta img { width: 72px; height: 54px; object-fit: cover; border-radius: 5px; }
    .mn-dominio { font-size: 9px; text-transform: uppercase; opacity: .55; }
    .mn-titulo-c { font-weight: 600; }
    .mn-texto-c { opacity: .7; }
    .mn-pie { padding: 0 12px 10px; opacity: .55; font-size: 11px; }
</style>
