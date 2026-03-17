<x-filament-panels::page>
<div class="space-y-6">

    {{-- ── Banner de estado ─────────────────────────────────────────────── --}}
    @if(! $configurado)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-5 flex items-start gap-4">
        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 shrink-0 mt-0.5"/>
        <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300">Servicio de correo no configurado</p>
            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                El servicio de envío de correos aún no ha sido activado para tu empresa.
                Contacta al <strong>administrador</strong> para que lo configure.
            </p>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-700 p-5 flex items-start gap-4">
        <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-500 shrink-0 mt-0.5"/>
        <div>
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">Servicio de correo activo</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mt-1">
                Dominio: <strong>{{ $empresa->mailgun_domain }}</strong> —
                Remitente: <strong>{{ $empresa->mailgun_from_name ?: $empresa->name }}</strong>
                &lt;{{ $empresa->mailgun_from_email }}&gt;
            </p>
        </div>
    </div>
    @endif

    @if($configurado)

    {{-- ── KPIs principales ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Entregados --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-indigo-100 dark:bg-indigo-900/30">
                <x-heroicon-o-paper-airplane class="w-6 h-6 text-indigo-600 dark:text-indigo-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['delivered'] ?? 0) }}</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Entregados</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">últimos 30 días</p>
            </div>
        </div>

        {{-- Tasa de entrega --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-emerald-100 dark:bg-emerald-900/30">
                <x-heroicon-o-check-badge class="w-6 h-6 text-emerald-600 dark:text-emerald-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['delivery_rate'] ?? 0 }}%</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Tasa de entrega</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ number_format($stats['bounced'] ?? 0) }} rebotes</p>
            </div>
        </div>

        {{-- Tasa de apertura --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-sky-100 dark:bg-sky-900/30">
                <x-heroicon-o-eye class="w-6 h-6 text-sky-600 dark:text-sky-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['open_rate'] ?? 0 }}%</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Tasa de apertura</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ number_format($stats['opened'] ?? 0) }} abiertos</p>
            </div>
        </div>

        {{-- Clics --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-violet-100 dark:bg-violet-900/30">
                <x-heroicon-o-cursor-arrow-rays class="w-6 h-6 text-violet-600 dark:text-violet-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['click_rate'] ?? 0 }}%</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Tasa de clics</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ number_format($stats['clicked'] ?? 0) }} clics</p>
            </div>
        </div>

    </div>

    {{-- ── Stats secundarias ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 flex items-center gap-3 shadow-sm">
            <x-heroicon-o-arrow-uturn-left class="w-5 h-5 text-rose-500 shrink-0"/>
            <div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['bounced'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Rebotes</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 flex items-center gap-3 shadow-sm">
            <x-heroicon-o-hand-raised class="w-5 h-5 text-orange-500 shrink-0"/>
            <div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['complained'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Marcados como spam</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 flex items-center gap-3 shadow-sm">
            <x-heroicon-o-user-minus class="w-5 h-5 text-gray-400 shrink-0"/>
            <div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['unsubscribed'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Bajas</p>
            </div>
        </div>

    </div>

    {{-- ── Eventos recientes ─────────────────────────────────────────────── --}}
    @if(! empty($events))
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Eventos recientes</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Últimos {{ count($events) }} eventos del dominio</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($events as $event)
            @php
                $tipo      = $event['event'] ?? 'unknown';
                $recipient = $event['recipient'] ?? '';
                if (empty($recipient) && isset($event['envelope']['targets'])) {
                    $recipient = $event['envelope']['targets'];
                }
                if (empty($recipient)) {
                    $recipient = '—';
                }
                $subject   = $event['message']['headers']['subject'] ?? '(sin asunto)';
                $ts        = ! empty($event['timestamp'])
                    ? \Carbon\Carbon::createFromTimestamp((int) $event['timestamp'])->format('d/m H:i')
                    : null;
            @endphp
            <div class="px-6 py-3 flex items-center gap-4">

                @if($tipo === 'delivered')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 shrink-0 w-24 justify-center">Entregado</span>
                @elseif($tipo === 'opened')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300 shrink-0 w-24 justify-center">Abierto</span>
                @elseif($tipo === 'clicked')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 shrink-0 w-24 justify-center">Clic</span>
                @elseif($tipo === 'bounced' || $tipo === 'failed')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 shrink-0 w-24 justify-center">Rebote</span>
                @elseif($tipo === 'complained')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 shrink-0 w-24 justify-center">Spam</span>
                @elseif($tipo === 'unsubscribed')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 shrink-0 w-24 justify-center">Baja</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 shrink-0 w-24 justify-center">{{ ucfirst($tipo) }}</span>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $recipient }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $subject }}</p>
                </div>

                @if($ts)
                <p class="text-xs text-gray-400 dark:text-gray-500 shrink-0 whitespace-nowrap">{{ $ts }}</p>
                @endif

            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-8 text-center shadow-sm">
        <x-heroicon-o-inbox class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2"/>
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay eventos recientes en este dominio.</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Usa <strong>Enviar prueba</strong> para generar el primer evento.</p>
    </div>
    @endif

    @endif {{-- fin $configurado --}}

    {{-- ── Información del plan ─────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Plan activo: {{ $planLabel }}</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            @if($plan === 'basic')
                <p>✅ Dashboard de Mailing</p>
                <p class="text-gray-400 dark:text-gray-600">⬜ Módulos ERP (Contabilidad, Inventario, Ventas…) — disponibles en <strong>Plan Pro</strong></p>
            @elseif($plan === 'pro')
                <p>✅ Dashboard de Mailing</p>
                <p>✅ Módulos ERP completos (Contabilidad, Inventario, Ventas, Manufactura, Tesorería)</p>
                <p>✅ Informes Financieros con exportación Supercias</p>
                <p class="text-gray-400 dark:text-gray-600">⬜ Funcionalidades avanzadas — disponibles en <strong>Plan Enterprise</strong></p>
            @else
                <p>✅ Acceso completo a todos los módulos y funcionalidades</p>
            @endif
        </div>
    </div>

</div>
</x-filament-panels::page>
