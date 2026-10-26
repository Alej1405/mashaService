{{--
    Aviso fijo del 104 que falta presentar. No se cierra: se apaga solo cuando se
    sube el comprobante del SRI. Amarillo con tiempo; rojo a dos días o vencido.
--}}
@php
    $rojo = $alerta['urgente'];
    $cuando = match (true) {
        $alerta['dias'] === null => 'falta el RUC para calcular el vencimiento',
        $alerta['dias'] < 0      => 'venció el ' . $alerta['vence']->format('d/m/Y') . ' (hace ' . abs($alerta['dias']) . ($alerta['dias'] === -1 ? ' día' : ' días') . ')',
        $alerta['dias'] === 0    => 'vence hoy',
        $alerta['dias'] === 1    => 'vence mañana',
        default                  => 'vence el ' . $alerta['vence']->format('d/m/Y') . ' · faltan ' . $alerta['dias'] . ' días',
    };
@endphp
<div role="alert" style="display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px; margin-bottom:16px;
     padding:12px 16px; border-radius:12px; border:1px solid {{ $rojo ? '#fca5a5' : '#fcd34d' }};
     background:{{ $rojo ? '#fef2f2' : '#fffbeb' }}; color:{{ $rojo ? '#991b1b' : '#92400e' }}; font-size:14px">
    <p style="margin:0; flex:1 1 320px">
        @php
            $que = match (true) {
                ($alerta['falta_104'] ?? true) && ($alerta['falta_ats'] ?? false) => 'Declaración del IVA y ATS',
                $alerta['falta_ats'] ?? false => 'ATS',
                default => 'Declaración del IVA',
            };
        @endphp
        <strong>{{ $que }} de {{ $alerta['etiqueta'] }}:</strong> {{ $cuando }}.
        @if ($alerta['otros'] > 0)
            Y {{ $alerta['otros'] === 1 ? 'otro mes también falta' : "otros {$alerta['otros']} meses también faltan" }}.
        @endif
    </p>
    <a href="{{ $url }}" style="padding:7px 14px; border-radius:999px; font-weight:600; text-decoration:none;
       background:{{ $rojo ? '#b91c1c' : '#b45309' }}; color:#fff; white-space:nowrap">
        Declarar
    </a>
</div>
