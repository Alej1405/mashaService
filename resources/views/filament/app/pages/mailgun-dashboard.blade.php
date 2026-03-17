<x-filament-panels::page>
<div class="space-y-6">

    {{-- Banner de estado de configuración --}}
    @if(! $configurado)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-5 flex items-start gap-4">
        <div class="shrink-0 mt-0.5">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500"/>
        </div>
        <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300">Mailgun no está configurado</p>
            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                Para activar el envío de correos, agrega tu API Key y dominio en
                <strong>Configuración de la Empresa</strong> (menú superior, junto al nombre de la empresa).
            </p>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-700 p-5 flex items-start gap-4">
        <div class="shrink-0 mt-0.5">
            <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-500"/>
        </div>
        <div>
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">Mailgun configurado correctamente</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mt-1">
                Dominio: <strong>{{ $empresa->mailgun_domain }}</strong> —
                Remitente: <strong>{{ $empresa->mailgun_from_name ?: $empresa->name }}</strong>
                &lt;{{ $empresa->mailgun_from_email }}&gt;
            </p>
        </div>
    </div>
    @endif

    {{-- Tarjetas de KPIs (placeholders hasta integrar la API de Mailgun) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['label' => 'Correos enviados',   'value' => '—', 'icon' => 'heroicon-o-paper-airplane', 'color' => 'indigo'],
                ['label' => 'Tasa de entrega',    'value' => '—', 'icon' => 'heroicon-o-check-badge',    'color' => 'emerald'],
                ['label' => 'Aperturas',           'value' => '—', 'icon' => 'heroicon-o-eye',            'color' => 'sky'],
                ['label' => 'Rebotes',             'value' => '—', 'icon' => 'heroicon-o-arrow-uturn-left','color' => 'rose'],
            ];
        @endphp

        @foreach($cards as $card)
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-center gap-4 shadow-sm">
            <div class="rounded-full p-3 bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30">
                <x-dynamic-component :component="$card['icon']" class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400"/>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Sección de información del plan --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Tu Plan: {{ $planLabel }}</h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            @if($plan === 'basic')
                <p>✅ Dashboard de Mailing con Mailgun</p>
                <p class="text-gray-400 dark:text-gray-600">⬜ Módulos ERP (Contabilidad, Inventario, Ventas…) — disponibles en <strong>Plan Pro</strong></p>
            @elseif($plan === 'pro')
                <p>✅ Dashboard de Mailing con Mailgun</p>
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
