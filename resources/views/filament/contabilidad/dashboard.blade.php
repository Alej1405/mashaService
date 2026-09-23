<x-filament-panels::page>
@php
    $money = fn ($n, $d = 2) => '$ ' . number_format((float) $n, $d, ',', '.');

    $rutas = [
        'cierre'       => \App\Filament\Contabilidad\Pages\CierreEjercicio::getUrl(),
        'tributaria'   => \App\Filament\Contabilidad\Pages\ConfiguracionTributaria::getUrl(),
        'declaraciones'=> \App\Filament\Contabilidad\Pages\Declaraciones::getUrl(),
        'estados'      => \App\Filament\Contabilidad\Pages\EstadosFinancieros::getUrl(),
        'asientos'     => \App\Filament\Contabilidad\Resources\AsientoResource::getUrl(),
        'cuentas'      => \App\Filament\Contabilidad\Resources\PlanDeCuentasResource::getUrl(),
    ];
@endphp

{{-- Estilos propios: el panel no carga el tema del panel App y el CSS base de
     Filament no trae todas las utilidades. Mismo patrón que Operaciones. --}}
<style>
    .ct { --s:#fff; --s2:#f8fafc; --s3:#f1f5f9; --b:#e2e8f0; --b2:#f1f5f9;
          --t:#0f172a; --t2:#1e293b; --m:#64748b; --m2:#94a3b8;
          --ac:#4f46e5; --ok:#047857; --ok-s:#ecfdf5; --wa:#b45309; --wa-s:#fffbeb;
          --da:#be123c; --da-s:#fff1f2; --r:14px;
          display:flex; flex-direction:column; gap:20px; }

    .ct-tit { font-size:11px; font-weight:700; letter-spacing:.9px; text-transform:uppercase; color:var(--m); margin:0; }
    .ct-rej { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
    .ct-rej-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
    .ct-cols { display:grid; grid-template-columns:1fr 1fr; gap:16px; }

    .ct-card { background:var(--s); border:1px solid var(--b); border-radius:var(--r); padding:18px; }
    .ct-card-cab { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .ct-chip { flex-shrink:0; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
    .ct-chip-wa { background:var(--wa-s); color:var(--wa); }
    .ct-chip-ok { background:var(--ok-s); color:var(--ok); }
    .ct-chip-da { background:var(--da-s); color:var(--da); }
    .ct-chip-n  { background:var(--s3);   color:var(--m); }
    .ct-nom { font-size:17px; font-weight:700; color:var(--t); margin:10px 0 0; }
    .ct-val { font-size:26px; font-weight:700; letter-spacing:-.8px; color:var(--t); margin:6px 0 0; }
    .ct-pie { font-size:12px; color:var(--m); margin:2px 0 0; }
    .ct-pie2 { font-size:12px; color:var(--m2); margin:2px 0 0; }

    .ct-panel { background:var(--s); border:1px solid var(--b); border-radius:var(--r); overflow:hidden; }
    .ct-panel > header { display:flex; align-items:center; justify-content:space-between;
                         gap:12px; padding:14px 18px; border-bottom:1px solid var(--b2); }
    .ct-fila { display:flex; align-items:center; justify-content:space-between; gap:12px;
               padding:12px 18px; border-bottom:1px solid var(--b2); }
    .ct-fila:last-child { border-bottom:0; }
    .ct-fila-t { font-size:14px; font-weight:700; color:var(--t); }
    .ct-fila-s { font-size:12px; color:var(--m); margin-top:2px; }
    .ct-vacio { padding:28px 18px; text-align:center; font-size:14px; color:var(--m); }

    .ct-aviso { display:flex; align-items:center; gap:12px; padding:14px 16px;
                border-radius:var(--r); background:var(--wa-s); border:1px solid #fde68a; }
    .ct-aviso p { margin:0; font-size:14px; color:var(--wa); flex:1; }
    .ct-aviso a { flex-shrink:0; font-size:12px; font-weight:700; color:var(--wa); text-decoration:underline; }

    .ct-enlaces { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
    .ct-enlace { display:block; padding:16px; background:var(--s); border:1px solid var(--b);
                 border-radius:var(--r); text-decoration:none; transition:border-color .15s ease; }
    .ct-enlace:hover { border-color:var(--ac); }
    .ct-enlace strong { display:block; font-size:15px; font-weight:700; color:var(--t); }
    .ct-enlace span { display:block; font-size:12px; color:var(--m); margin-top:3px; }

    @media (max-width:1100px) { .ct-rej,.ct-rej-4,.ct-enlaces { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:820px)  { .ct-cols { grid-template-columns:1fr; } }
    @media (max-width:560px)  { .ct-rej,.ct-rej-4,.ct-enlaces { grid-template-columns:1fr; } }
</style>

<div class="ct">

    @if ($sinMapear > 0)
        <div class="ct-aviso">
            <p>
                {{ $sinMapear }} {{ $sinMapear === 1 ? 'cuenta' : 'cuentas' }} sin línea del estado asignada:
                no {{ $sinMapear === 1 ? 'aparecerá' : 'aparecerán' }} en el balance que recibe la Superintendencia.
            </p>
            <a href="{{ $rutas['cuentas'] }}">Revisar el plan de cuentas</a>
        </div>
    @endif

    {{-- Lo que se declara este mes --}}
    <p class="ct-tit">Declaraciones de {{ $periodo }}</p>
    <div class="ct-rej">
        @foreach ($declaraciones as $d)
            <div class="ct-card">
                <div class="ct-card-cab">
                    <span class="ct-tit">{{ $d['codigo'] }}</span>
                    <span class="ct-chip ct-chip-wa">{{ $d['estado'] }}</span>
                </div>
                <p class="ct-nom">{{ $d['nombre'] }}</p>
                <p class="ct-val">{{ $d['cifra'] }}</p>
                <p class="ct-pie">{{ $d['pie'] }}</p>
                <p class="ct-pie2">vence según el 9.º dígito del RUC</p>
            </div>
        @endforeach
    </div>

    {{-- El estado de situación, que es lo que la SCVS recibe cada abril --}}
    <p class="ct-tit">Estado de situación · al {{ now()->translatedFormat('j \d\e F') }}</p>
    <div class="ct-rej-4">
        @foreach ([
            ['Activo', $saldos['activo'], 'corriente y no corriente'],
            ['Pasivo', $saldos['pasivo'], 'incluye cuentas por pagar'],
            ['Patrimonio', $saldos['patrimonio'], 'capital y resultados'],
            ['Resultado del ejercicio', $saldos['resultado'], 'ingresos menos costos y gastos'],
        ] as [$etiqueta, $valor, $pie])
            <div class="ct-card">
                <p class="ct-val">{{ $money($valor) }}</p>
                <p class="ct-tit" style="margin-top:7px">{{ $etiqueta }}</p>
                <p class="ct-pie2">{{ $pie }}</p>
            </div>
        @endforeach
    </div>

    <div class="ct-cols">
        {{-- Superintendencia --}}
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Superintendencia de Compañías · {{ now()->year }}</h2>
                <span class="ct-pie2">{{ $clasificacion['marco'] }}</span>
            </header>
            @foreach ($obligaciones as $o)
                @php
                    $vence = $o['vence'];
                    $dias = $vence ? now()->startOfDay()->diffInDays($vence, false) : null;
                    $tono = $dias === null ? 'n' : ($dias < 0 ? 'ok' : ($dias <= 15 ? 'wa' : 'n'));
                    $texto = $dias === null ? '—' : ($dias < 0 ? 'Cumplido' : ($dias === 0 ? 'Vence hoy' : "Vence en {$dias} días"));
                @endphp
                <div class="ct-fila">
                    <div style="min-width:0">
                        <p class="ct-fila-t">{{ $o['titulo'] }}</p>
                        <p class="ct-fila-s">{{ $o['detalle'] }}</p>
                    </div>
                    <span class="ct-chip ct-chip-{{ $tono }}">{{ $texto }}</span>
                </div>
            @endforeach
        </section>

        {{-- Cierre y compañía --}}
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Cierre y compañía</h2>
                <a href="{{ $rutas['cierre'] }}" class="ct-pie2">Ver cierre →</a>
            </header>
            @forelse ($ejercicios as $e)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">Ejercicio {{ $e->anio }}</p>
                        <p class="ct-fila-s">
                            {{ $e->cerrado_en ? 'cerrado el ' . $e->cerrado_en->format('d/m/Y') : 'admite asientos' }}
                        </p>
                    </div>
                    <span class="ct-chip ct-chip-{{ $e->cerrado_en ? 'ok' : 'wa' }}">
                        {{ $e->cerrado_en ? 'Cerrado' : 'Abierto' }}
                    </span>
                </div>
            @empty
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">Ejercicio {{ now()->year }}</p>
                        <p class="ct-fila-s">sin cerrar todavía</p>
                    </div>
                    <span class="ct-chip ct-chip-wa">Abierto</span>
                </div>
            @endforelse
            <div class="ct-fila">
                <div>
                    <p class="ct-fila-t">{{ $societario['socios'] }} socios · {{ $societario['administradores'] }} administradores</p>
                    <p class="ct-fila-s">capital {{ $money($societario['capital']) }} · anexo de la SCVS</p>
                </div>
                <span class="ct-chip ct-chip-{{ $societario['socios'] ? 'ok' : 'da' }}">
                    {{ $societario['socios'] ? 'Registrados' : 'Sin registrar' }}
                </span>
            </div>
            <div class="ct-fila">
                <div>
                    <p class="ct-fila-t">IVA {{ $tarifaIva ? rtrim(rtrim(number_format($tarifaIva->porcentaje, 2, ',', '.'), '0'), ',') . ' %' : 'sin tarifa' }}</p>
                    <p class="ct-fila-s">{{ $tarifaIva?->descripcion ?? 'no hay tarifa vigente configurada' }}</p>
                </div>
                <a href="{{ $rutas['tributaria'] }}" class="ct-chip ct-chip-n" style="text-decoration:none">Configurar</a>
            </div>
        </section>
    </div>

    {{-- Libros y estados --}}
    <p class="ct-tit">Libros y estados</p>
    <div class="ct-enlaces">
        <a class="ct-enlace" href="{{ $rutas['asientos'] }}">
            <strong>Libro diario</strong><span>{{ $asientosDelMes }} asientos este mes</span>
        </a>
        <a class="ct-enlace" href="{{ $rutas['cuentas'] }}">
            <strong>Plan de cuentas</strong><span>raíz 1 a 6 y su línea del estado</span>
        </a>
        <a class="ct-enlace" href="{{ $rutas['estados'] }}">
            <strong>Estados financieros</strong><span>con comparativo del año anterior</span>
        </a>
        <a class="ct-enlace" href="{{ $rutas['declaraciones'] }}">
            <strong>Declaraciones</strong><span>104, 103 y anexo transaccional</span>
        </a>
    </div>
</div>
</x-filament-panels::page>
