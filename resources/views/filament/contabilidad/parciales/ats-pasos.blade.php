{{--
    El ATS de un mes, paso a paso: generar → descargar → subir al SRI → subir la
    confirmación. Lo usan Cumplimiento y Declaraciones (las dos con GestionaDeclaraciones).
    Recibe $ats (Declaracion|null), $anio, $mes y $etiqueta.
--}}
@php $zip = sprintf('AT%02d%d.zip', $mes, $anio); @endphp

@if ($ats?->presentado_en)
    <p class="ct-paso ct-paso-hecho"><b>✓</b><span>ATS presentado el
        {{ \Illuminate\Support\Carbon::parse($ats->datos['confirmacion']['presentado_en'] ?? $ats->presentado_en)->format('d/m/Y H:i') }}.
        El SRI lo valida en 48 horas: si llega un archivo de errores, se corrige, se genera de nuevo y se vuelve a subir.</span></p>
@elseif (in_array($ats?->estado, ['pendiente', 'procesando'], true))
    <p class="ct-paso"><b>1</b><span>Generando el ATS de {{ $etiqueta }}… esta página se actualiza sola.</span></p>
@elseif (! $ats || $ats->estado === 'error')
    @if ($ats?->mensaje)
        <p class="ct-paso" style="color:var(--da)"><b>!</b><span>No se pudo generar: {{ $ats->mensaje }}</span></p>
    @endif
    <p class="ct-paso"><b>1</b><span>Genera el ATS de {{ $etiqueta }} con las compras y ventas registradas.</span></p>
    <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
            wire:click="pedir({{ $anio }}, {{ $mes }}, 'ats')" wire:loading.attr="disabled" wire:target="pedir">
        Generar el ATS
    </button>
@else
    <p class="ct-paso"><b>1</b><span>Descarga el archivo. Es el que se sube tal cual, sin cambiarle el nombre.</span></p>
    <a class="ct-btn-descarga" href="{{ route('contabilidad.declaracion', $ats->id) }}">Descargar {{ $zip }}</a>
    <p class="ct-paso"><b>2</b><span>En <a href="https://srienlinea.sri.gob.ec" target="_blank" rel="noopener">SRI en línea</a>
        entra a <strong>Anexos → Anexo transaccional simplificado – ATS → Carga de archivo xml</strong> y sube {{ $zip }}.</span></p>
    <p class="ct-paso"><b>3</b><span>El portal muestra la «Confirmación de Presentación de Archivo – ATS»: guárdala en PDF y súbela aquí.</span></p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px">
        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                wire:click="mountAction('subirConfirmacionAts', { id: {{ $ats->id }} })">
            Subir la confirmación del SRI
        </button>
        <button type="button" class="ct-pestana" wire:click="pedir({{ $anio }}, {{ $mes }}, 'ats')"
                wire:loading.attr="disabled" wire:target="pedir"
                title="Si registraste o corregiste facturas después de generarlo">
            Volver a generar
        </button>
        @if ($ats->generado_en)
            <span class="ct-pie2" style="align-self:center">generado el {{ $ats->generado_en->format('d/m/Y \a \l\a\s H:i') }}</span>
        @endif
    </div>
@endif
