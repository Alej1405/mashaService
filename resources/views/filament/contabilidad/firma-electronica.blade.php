<x-filament-panels::page>
@include('filament.contabilidad._estilos')

<div class="ct">
    @if (! $firma)
        <div class="ct-aviso">
            <p>
                <strong>No hay certificado cargado.</strong>
                Sin firma electrónica no se puede firmar ni un comprobante ni un anexo del SRI.
                Súbelo con el botón de arriba.
            </p>
        </div>
    @else
        @php $estado = $firma->estado; @endphp

        <div class="ct-rej">
            <div class="ct-card">
                <div class="ct-card-cab">
                    <span class="ct-tit">Estado</span>
                    <span class="ct-chip ct-chip-{{ $estado['tono'] }}">{{ ucfirst($estado['texto']) }}</span>
                </div>
                <p class="ct-val">
                    {{ $firma->dias_restantes !== null ? max($firma->dias_restantes, 0) : '—' }}
                </p>
                <p class="ct-tit" style="margin-top:7px">Días de vida</p>
                <p class="ct-pie2">
                    {{ $firma->valido_hasta ? 'hasta el ' . $firma->valido_hasta->format('d/m/Y') : 'sin fecha' }}
                </p>
            </div>

            <div class="ct-card">
                <span class="ct-tit">Titular</span>
                <p class="ct-nom">{{ $firma->titular ?? 'sin nombre en el certificado' }}</p>
                <p class="ct-pie">{{ $firma->identificacion ?? '—' }}</p>
                <p class="ct-pie2">emitido por {{ $firma->emisor ?? 'emisora no identificada' }}</p>
            </div>

            <div class="ct-card">
                <span class="ct-tit">Vigencia</span>
                <p class="ct-nom">
                    {{ $firma->valido_desde?->format('d/m/Y') ?? '—' }} —
                    {{ $firma->valido_hasta?->format('d/m/Y') ?? '—' }}
                </p>
                <p class="ct-pie">serie {{ \Illuminate\Support\Str::limit($firma->numero_serie ?? '—', 24) }}</p>
                <p class="ct-pie2">cargado el {{ $firma->created_at->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- La barra de vida: dos años completos, para ver de un vistazo dónde estamos --}}
        @php
            $total = $firma->valido_desde && $firma->valido_hasta
                ? max($firma->valido_desde->diffInDays($firma->valido_hasta), 1) : null;
            $restan = max($firma->dias_restantes ?? 0, 0);
            $porcentaje = $total ? min(100, max(0, round($restan / $total * 100))) : 0;
            $color = $porcentaje > 25 ? 'var(--ok)' : ($porcentaje > 10 ? 'var(--wa)' : 'var(--da)');
        @endphp

        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Tiempo de vida del certificado</h2>
                <span class="ct-pie2">{{ $porcentaje }} % restante</span>
            </header>
            <div style="padding:18px">
                <div style="height:10px;border-radius:999px;background:var(--s3);overflow:hidden">
                    <div style="height:100%;width:{{ $porcentaje }}%;background:{{ $color }};transition:width .3s"></div>
                </div>
                <p class="ct-pie2" style="margin-top:10px">
                    @if ($firma->dias_restantes !== null && $firma->dias_restantes < 0)
                        Caducó. Renuévalo con tu emisora y vuelve a subirlo: hasta entonces no se puede firmar nada.
                    @elseif ($firma->dias_restantes !== null && $firma->dias_restantes <= \App\Models\FirmaElectronica::AVISO_DIAS)
                        Queda poco. La renovación con la emisora toma días, así que conviene empezarla ya.
                    @else
                        Los certificados del SRI duran dos años. Aquí verás el aviso 45 días antes.
                    @endif
                </p>
            </div>
        </section>
    @endif

    @if ($historial->count() > 1)
        <section class="ct-panel">
            <header><h2 class="ct-tit">Certificados anteriores</h2></header>
            @foreach ($historial->skip(1) as $h)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">{{ $h->titular ?? 'sin nombre' }}</p>
                        <p class="ct-fila-s">
                            {{ $h->valido_desde?->format('d/m/Y') }} — {{ $h->valido_hasta?->format('d/m/Y') }}
                            · {{ $h->emisor }}
                        </p>
                    </div>
                    <span class="ct-chip ct-chip-n">Reemplazado</span>
                </div>
            @endforeach
        </section>
    @endif

    <section class="ct-panel">
        <header><h2 class="ct-tit">Para qué se usa</h2></header>
        @foreach ([
            ['Comprobantes electrónicos', 'facturas, notas de crédito y retenciones que se envían al SRI', $firma?->vigente],
            ['Anexo transaccional', 'el ATS se firma antes de subirlo al portal', $firma?->vigente],
            ['Declaraciones', 'el 104 y el 103 se presentan con la clave del contribuyente, no con la firma', true],
        ] as [$titulo, $detalle, $listo])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $detalle }}</p></div>
                <span class="ct-chip ct-chip-{{ $listo ? 'ok' : 'da' }}">{{ $listo ? 'Cubierto' : 'Sin firma' }}</span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
