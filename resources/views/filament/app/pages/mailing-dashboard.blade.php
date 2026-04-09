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

    {{-- ── Aviso fuente de datos ────────────────────────────────────────── --}}
    @if($stats['from_cache'] ?? false)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 flex items-start gap-3">
        <x-heroicon-o-clock class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"/>
        <p class="text-sm text-amber-700 dark:text-amber-300">
            Mostrando última sincronización guardada
            @if($stats['last_synced'] ?? null)
                del <strong>{{ $stats['last_synced'] }}</strong>
            @endif
            — el servicio de correo ya no retiene logs de esa fecha (retención limitada en plan gratuito).
            Los correos enviados (Enviados) siempre se calculan desde tus campañas locales.
        </p>
    </div>
    @elseif(($stats['accepted'] ?? 0) === 0 && ($stats['delivered'] ?? 0) === 0 && ($stats['last_synced'] ?? null) === null)
    <div class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700 p-4 flex items-start gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"/>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            Aún no hay datos de envío. Crea y envía tu primera campaña para ver las estadísticas aquí.
        </p>
    </div>
    @endif

    {{-- ── KPIs de volumen (fila 1) ─────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Enviados / Aceptados --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-indigo-100 dark:bg-indigo-900/30">
                <x-heroicon-o-paper-airplane class="w-6 h-6 text-indigo-600 dark:text-indigo-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['accepted'] ?? 0) }}</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Enviados</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">aceptados por el servicio</p>
            </div>
        </div>

        {{-- Entregados --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-emerald-100 dark:bg-emerald-900/30">
                <x-heroicon-o-inbox-arrow-down class="w-6 h-6 text-emerald-600 dark:text-emerald-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['delivered'] ?? 0) }}</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Entregados</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">llegaron al destinatario</p>
            </div>
        </div>

        {{-- Fallidos --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-rose-100 dark:bg-rose-900/30">
                <x-heroicon-o-x-circle class="w-6 h-6 text-rose-600 dark:text-rose-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['failed'] ?? 0) }}</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Fallidos</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ number_format($stats['bounced'] ?? 0) }} rebotes</p>
            </div>
        </div>

    </div>

    {{-- ── KPIs de engagement (fila 2) ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Tasa de entrega --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-teal-100 dark:bg-teal-900/30">
                <x-heroicon-o-check-badge class="w-6 h-6 text-teal-600 dark:text-teal-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['delivery_rate'] ?? 0 }}%</p>
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Tasa de entrega</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">entregados / enviados</p>
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

        {{-- Tasa de clics --}}
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

    {{-- ── Stats de problemas (fila 3) ─────────────────────────────────── --}}
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

                @if($tipo === 'accepted')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 shrink-0 w-24 justify-center">Enviado</span>
                @elseif($tipo === 'delivered')
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

    {{-- ── Cuota de envíos ─────────────────────────────────────────────── --}}
    @php
        $quotaPct     = $quota['percentage'] ?? 0;
        $quotaColor   = $quotaPct >= 90 ? 'rose' : ($quotaPct >= 70 ? 'amber' : 'emerald');
        $quotaBarBg   = $quotaPct >= 90 ? 'bg-rose-500' : ($quotaPct >= 70 ? 'bg-amber-400' : 'bg-emerald-500');
    @endphp
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Cuota de envíos del período</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">Se renueva el {{ $quota['reset_label'] ?? $quota['reset_date'] ?? '—' }}</span>
        </div>

        {{-- Barra de progreso --}}
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 mb-3">
            <div class="{{ $quotaBarBg }} h-3 rounded-full transition-all duration-300"
                 style="width: {{ $quotaPct }}%"></div>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                <strong class="text-gray-900 dark:text-white">{{ number_format($quota['sent'] ?? 0) }}</strong>
                enviados de
                <strong class="text-gray-900 dark:text-white">{{ number_format($quota['limit'] ?? 0) }}</strong>
            </span>
            @if(($quota['remaining'] ?? 0) > 0)
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                    {{ number_format($quota['remaining']) }} disponibles
                </span>
            @else
                <span class="text-rose-600 dark:text-rose-400 font-semibold">Cuota agotada</span>
            @endif
        </div>

        @if($quotaPct >= 80)
        <p class="text-xs text-{{ $quotaColor }}-600 dark:text-{{ $quotaColor }}-400 mt-2">
            @if($quotaPct >= 100)
                Has alcanzado el límite del período. Los envíos se renuevan el {{ $quota['reset_label'] ?? $quota['reset_date'] ?? '—' }}.
            @else
                Has usado el {{ $quotaPct }}% de tu cuota. Renueva el {{ $quota['reset_label'] ?? $quota['reset_date'] ?? '—' }}.
            @endif
        </p>
        @endif
    </div>

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
