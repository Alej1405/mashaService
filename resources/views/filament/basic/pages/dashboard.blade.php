<x-filament-panels::page>
<div class="space-y-6">

{{-- ══════════════════════════════════════════════════════════════
     HEADER — Saludo + estado del servicio
══════════════════════════════════════════════════════════════ --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 p-6 shadow-xl">

    {{-- Círculos decorativos de fondo --}}
    <div style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;border-radius:50%;background:rgba(99,102,241,.15);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;right:120px;width:160px;height:160px;border-radius:50%;background:rgba(139,92,246,.1);pointer-events:none;"></div>

    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-2xl font-black text-slate-400 leading-tight">{{ $saludo }}</p>
            <p class="text-sm text-slate-400 mt-1">{{ $fecha }}</p>
            <div class="flex items-center gap-2 mt-3">
                <span class="text-xs font-semibold text-slate-300 bg-slate-700/60 px-3 py-1 rounded-full">
                    {{ $empresa->name }}
                </span>
                <span class="text-xs font-bold px-3 py-1 rounded-full
                    @if($empresa->plan === 'enterprise') bg-amber-400/20 text-amber-300
                    @elseif($empresa->plan === 'pro') bg-indigo-400/20 text-indigo-300
                    @else bg-slate-500/30 text-slate-300 @endif">
                    Plan {{ ucfirst($empresa->plan ?? 'basic') }}
                </span>
            </div>
        </div>

        {{-- Estado del servicio de correo --}}
        <div class="shrink-0">
            @if($configurado)
            <div class="flex items-center gap-2 bg-emerald-500/15 border border-emerald-500/30 rounded-xl px-4 py-3">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <div>
                    <p class="text-xs font-bold text-emerald-300">Servicio activo</p>
                    <p class="text-xs text-emerald-500 font-mono">{{ $empresa->mailgun_domain }}</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-2 bg-amber-500/15 border border-amber-500/30 rounded-xl px-4 py-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-400 shrink-0"/>
                <div>
                    <p class="text-xs font-bold text-amber-300">Servicio inactivo</p>
                    <p class="text-xs text-amber-500">Contacta al administrador</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
        KPIs — 4 tarjetas
══════════════════════════════════════════════════════════════ --}}
<div class="flex flex-4 flex-row gap-4 justify-center w-full">

    {{-- Entregados --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col gap-3 w-full">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Entregados</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <x-heroicon-o-paper-airplane class="w-4 h-4 text-indigo-500"/>
            </div>
        </div>
        <div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">
                {{ $configurado ? number_format($stats['delivered'] ?? 0) : '—' }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">últimos 30 días</p>
        </div>
        @if($configurado && ($stats7['delivered'] ?? 0) > 0)
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            <p class="text-xs text-indigo-500 font-semibold">
                +{{ number_format($stats7['delivered']) }} esta semana
            </p>
        </div>
        @endif
    </div>

    {{-- Tasa de entrega --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col gap-3 w-full">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Entrega</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                <x-heroicon-o-check-badge class="w-4 h-4 text-emerald-500"/>
            </div>
        </div>
        <div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">
                {{ $configurado ? ($stats['delivery_rate'] ?? 0) . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">tasa de entrega</p>
        </div>
        @if($configurado)
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            @php $rate = $stats['delivery_rate'] ?? 0; @endphp
            <p class="text-xs font-semibold {{ $rate >= 95 ? 'text-emerald-500' : ($rate >= 85 ? 'text-amber-500' : 'text-rose-500') }}">
                {{ $rate >= 95 ? '✓ Excelente' : ($rate >= 85 ? '⚠ Aceptable' : '✗ Revisar rebotes') }}
            </p>
        </div>
        @endif
    </div>

    {{-- Apertura --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col gap-3 w-full">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Aperturas</span>
            <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center">
                <x-heroicon-o-eye class="w-4 h-4 text-sky-500"/>
            </div>
        </div>
        <div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">
                {{ $configurado ? ($stats['open_rate'] ?? 0) . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">tasa de apertura</p>
        </div>
        @if($configurado)
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            <p class="text-xs text-gray-400">
                {{ number_format($stats['opened'] ?? 0) }} correos abiertos
            </p>
        </div>
        @endif
    </div>

    {{-- Plantillas --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex flex-col gap-3 w-full">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Plantillas</span>
            <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                <x-heroicon-o-document-text class="w-4 h-4 text-violet-500"/>
            </div>
        </div>
        <div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ $plantillas }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $plantillas === 1 ? 'plantilla creada' : 'plantillas creadas' }}</p>
        </div>
        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
            <a href="{{ \App\Filament\App\Resources\MailTemplateResource::getUrl('create', tenant: \Filament\Facades\Filament::getTenant()) }}"
               class="text-xs font-semibold text-violet-500 hover:text-violet-600 transition-colors">
                + Nueva plantilla →
            </a>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════
    CONTENIDO PRINCIPAL — Eventos + Acciones rápidas
══════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Eventos recientes (2/3) --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Actividad reciente</h3>
                <p class="text-xs text-gray-400 mt-0.5">Últimos eventos del servicio de correo</p>
            </div>
            @if($configurado)
            <a href="{{ \App\Filament\Basic\Pages\MailingDashboard::getUrl(tenant: \Filament\Facades\Filament::getTenant()) }}"
                class="text-xs font-semibold text-indigo-500 hover:text-indigo-600 transition-colors">
                Ver todo →
            </a>
            @endif
        </div>

        @if(! $configurado)
        <div class="px-6 py-12 text-center">
            <x-heroicon-o-envelope class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3"/>
            <p class="text-sm font-medium text-gray-400">El servicio de correo no está activo</p>
            <p class="text-xs text-gray-400 mt-1">Contacta al administrador para configurarlo.</p>
        </div>
        @elseif(empty($events))
        <div class="px-6 py-12 text-center">
            <x-heroicon-o-inbox class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3"/>
            <p class="text-sm font-medium text-gray-400">Sin actividad reciente</p>
            <p class="text-xs text-gray-400 mt-1">Los eventos aparecerán aquí cuando envíes correos.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @foreach($events as $event)
            @php
                $tipo      = $event['event'] ?? 'unknown';
                $recipient = $event['recipient'] ?? ($event['envelope']['targets'] ?? '—');
                $subject   = $event['message']['headers']['subject'] ?? '(sin asunto)';
                $ts        = ! empty($event['timestamp'])
                    ? \Carbon\Carbon::createFromTimestamp((int) $event['timestamp'])
                    : null;
            @endphp
            <div class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                {{-- Icono del evento --}}
                <div class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                    @if($tipo === 'delivered') bg-emerald-100 dark:bg-emerald-900/30
                    @elseif($tipo === 'opened') bg-sky-100 dark:bg-sky-900/30
                    @elseif($tipo === 'clicked') bg-violet-100 dark:bg-violet-900/30
                    @elseif(in_array($tipo, ['bounced','failed'])) bg-rose-100 dark:bg-rose-900/30
                    @elseif($tipo === 'complained') bg-orange-100 dark:bg-orange-900/30
                    @else bg-gray-100 dark:bg-gray-800 @endif">
                    @if($tipo === 'delivered')
                        <x-heroicon-s-check class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"/>
                    @elseif($tipo === 'opened')
                        <x-heroicon-s-eye class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400"/>
                    @elseif($tipo === 'clicked')
                        <x-heroicon-s-cursor-arrow-rays class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400"/>
                    @elseif(in_array($tipo, ['bounced','failed']))
                        <x-heroicon-s-arrow-uturn-left class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"/>
                    @elseif($tipo === 'complained')
                        <x-heroicon-s-hand-raised class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400"/>
                    @else
                        <x-heroicon-s-minus class="w-3.5 h-3.5 text-gray-400"/>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate font-medium">{{ $recipient }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $subject }}</p>
                </div>

                <div class="shrink-0 text-right">
                    <span class="text-xs font-medium
                        @if($tipo === 'delivered') text-emerald-600 dark:text-emerald-400
                        @elseif($tipo === 'opened') text-sky-600 dark:text-sky-400
                        @elseif($tipo === 'clicked') text-violet-600 dark:text-violet-400
                        @elseif(in_array($tipo, ['bounced','failed'])) text-rose-600 dark:text-rose-400
                        @elseif($tipo === 'complained') text-orange-600 dark:text-orange-400
                        @else text-gray-400 @endif">
                        {{ match($tipo) {
                            'delivered'  => 'Entregado',
                            'opened'     => 'Abierto',
                            'clicked'    => 'Clic',
                            'bounced', 'failed' => 'Rebote',
                            'complained' => 'Spam',
                            'unsubscribed' => 'Baja',
                            default      => ucfirst($tipo),
                        } }}
                    </span>
                    @if($ts)
                    <p class="text-xs text-gray-300 dark:text-gray-600 mt-0.5">{{ $ts->format('d/m H:i') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Panel derecho (1/3) --}}
    <div class="space-y-4">

        {{-- Acciones rápidas --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Acciones rápidas</h3>
            </div>
            <div class="p-3 space-y-1">

                <a href="{{ \App\Filament\App\Resources\MailTemplateResource::getUrl('create', tenant: \Filament\Facades\Filament::getTenant()) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                        <x-heroicon-o-plus class="w-4 h-4 text-violet-600 dark:text-violet-400"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Nueva plantilla</p>
                        <p class="text-xs text-gray-400">Diseña un correo</p>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300 group-hover:text-gray-400 ml-auto shrink-0 transition-colors"/>
                </a>

                <a href="{{ \App\Filament\App\Resources\MailTemplateResource::getUrl('index', tenant: \Filament\Facades\Filament::getTenant()) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                        <x-heroicon-o-document-text class="w-4 h-4 text-indigo-600 dark:text-indigo-400"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Mis plantillas</p>
                        <p class="text-xs text-gray-400">{{ $plantillas }} {{ $plantillas === 1 ? 'plantilla' : 'plantillas' }}</p>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300 group-hover:text-gray-400 ml-auto shrink-0 transition-colors"/>
                </a>

                <a href="{{ \App\Filament\Basic\Pages\MailingDashboard::getUrl(tenant: \Filament\Facades\Filament::getTenant()) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0">
                        <x-heroicon-o-chart-bar class="w-4 h-4 text-sky-600 dark:text-sky-400"/>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Estadísticas</p>
                        <p class="text-xs text-gray-400">Reportes detallados</p>
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300 group-hover:text-gray-400 ml-auto shrink-0 transition-colors"/>
                </a>

            </div>
        </div>

        {{-- Resumen 7 días --}}
        @if($configurado && ($stats7['delivered'] ?? 0) + ($stats7['bounced'] ?? 0) > 0)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Últimos 7 días</h3>
            <div class="space-y-3">
                @php
                    $items7 = [
                        ['label' => 'Entregados',  'val' => $stats7['delivered'] ?? 0,   'color' => 'bg-emerald-500'],
                        ['label' => 'Abiertos',    'val' => $stats7['opened'] ?? 0,      'color' => 'bg-sky-500'],
                        ['label' => 'Clics',       'val' => $stats7['clicked'] ?? 0,     'color' => 'bg-violet-500'],
                        ['label' => 'Rebotes',     'val' => $stats7['bounced'] ?? 0,     'color' => 'bg-rose-500'],
                    ];
                    $maxVal = max(collect($items7)->pluck('val')->max(), 1);
                @endphp
                @foreach($items7 as $item)
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ number_format($item['val']) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                        <div class="{{ $item['color'] }} h-1.5 rounded-full transition-all"
                             style="width: {{ $maxVal > 0 ? round(($item['val'] / $maxVal) * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Guía de inicio rápido (si no está configurado) --}}
        @if(! $configurado)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-amber-200 dark:border-amber-800/50 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-4">
                <x-heroicon-o-rocket-launch class="w-4 h-4 text-amber-500"/>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Cómo empezar</h3>
            </div>
            <ol class="space-y-3">
                <li class="flex gap-3">
                    <span class="shrink-0 w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-bold flex items-center justify-center">1</span>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Contacta al <strong class="text-gray-800 dark:text-gray-200">administrador</strong> para activar el servicio de correo.</p>
                </li>
                <li class="flex gap-3">
                    <span class="shrink-0 w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 text-xs font-bold flex items-center justify-center">2</span>
                    <p class="text-xs text-gray-400">Diseña tus <strong>plantillas</strong> de correo con el editor visual.</p>
                </li>
                <li class="flex gap-3">
                    <span class="shrink-0 w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 text-xs font-bold flex items-center justify-center">3</span>
                    <p class="text-xs text-gray-400">Envía un <strong>correo de prueba</strong> para verificar el diseño.</p>
                </li>
            </ol>
        </div>
        @endif

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     STATS SECUNDARIAS (solo si está configurado)
══════════════════════════════════════════════════════════════ --}}
@if($configurado)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center shrink-0">
            <x-heroicon-o-arrow-uturn-left class="w-5 h-5 text-rose-500"/>
        </div>
        <div>
            <p class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($stats['bounced'] ?? 0) }}</p>
            <p class="text-xs text-gray-400">Rebotes (30d)</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center shrink-0">
            <x-heroicon-o-hand-raised class="w-5 h-5 text-orange-500"/>
        </div>
        <div>
            <p class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($stats['complained'] ?? 0) }}</p>
            <p class="text-xs text-gray-400">Reportes de spam (30d)</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center shrink-0">
            <x-heroicon-o-cursor-arrow-rays class="w-5 h-5 text-gray-400"/>
        </div>
        <div>
            <p class="text-xl font-black text-gray-900 dark:text-white">{{ ($stats['click_rate'] ?? 0) }}%</p>
            <p class="text-xs text-gray-400">Tasa de clics (30d)</p>
        </div>
    </div>

</div>
@endif

</div>
</x-filament-panels::page>
