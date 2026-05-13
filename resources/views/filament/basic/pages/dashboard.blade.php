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
            @if($servicio_activo && $configurado)
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
            @elseif($servicio_activo && ! $configurado)
            <div class="flex items-center gap-2 bg-amber-500/15 border border-amber-500/30 rounded-xl px-4 py-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-400 shrink-0"/>
                <div>
                    <p class="text-xs font-bold text-amber-300">Servicio inactivo</p>
                    <p class="text-xs text-amber-500">Contacta al administrador</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-2 bg-slate-500/15 border border-slate-500/30 rounded-xl px-4 py-3">
                <x-heroicon-o-lock-closed class="w-5 h-5 text-slate-400 shrink-0"/>
                <div>
                    <p class="text-xs font-bold text-slate-300">Mailing suspendido</p>
                    <p class="text-xs text-slate-500">Plan no incluye este servicio</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     INFO DEL PLAN
══════════════════════════════════════════════════════════════ --}}
<div class="d-card">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="p-1.5 rounded-lg" style="background:#eef2ff;">
                <x-heroicon-o-credit-card class="w-4 h-4" style="color:#4f46e5;" />
            </div>
            <span class="text-xs font-bold uppercase tracking-widest" style="color:#64748b;">Tu Plan</span>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
              style="background:{{ $badgeBg }};color:{{ $badgeColor }};border:1px solid {{ $badgeColor }}22;">
            {{ $planLabel }}
        </span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
        @foreach($features as $feature)
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="flex-shrink-0 p-1 rounded-md" style="background:#ecfdf5;">
                    <x-heroicon-o-check-circle class="w-3.5 h-3.5" style="color:#16a34a;" />
                </div>
                <span class="text-[11px] font-medium leading-tight" style="color:#475569;">{{ $feature }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MINIATURA WEB
══════════════════════════════════════════════════════════════ --}}
<div class="d-card">
    <div class="flex items-center gap-2 mb-4">
        <div class="p-1.5 rounded-lg" style="background:#f0fdf4;">
            <x-heroicon-o-globe-alt class="w-4 h-4" style="color:#16a34a;" />
        </div>
        <span class="text-xs font-bold uppercase tracking-widest" style="color:#64748b;">Accesos Rápidos</span>
    </div>

    @if($websiteUrl)
        <a href="{{ $websiteUrl }}" target="_blank" rel="noopener noreferrer"
           class="group block rounded-xl overflow-hidden transition-all duration-200"
           style="border:1px solid #e2e8f0;max-width:280px;"
           onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.10)';this.style.borderColor='#c7d2fe';"
           onmouseout="this.style.boxShadow='none';this.style.borderColor='#e2e8f0';">
            <div class="relative overflow-hidden" style="height:150px;background:#f1f5f9;">
                <img
                    src="{{ $thumbnailUrl }}"
                    alt="Vista previa de {{ $empresa->name }}"
                    class="w-full h-full object-cover object-top transition-transform duration-300 group-hover:scale-105"
                    onerror="this.parentElement.innerHTML='<div style=\'display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:6px;\'><svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' style=\'width:28px;height:28px;color:#94a3b8;\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418\'/></svg><span style=\'font-size:11px;color:#94a3b8;\'>Vista previa no disponible</span></div>';"
                >
                <div class="absolute top-2 right-2 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
                     style="background:rgba(255,255,255,0.9);backdrop-filter:blur(4px);">
                    <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" style="color:#4f46e5;" />
                </div>
            </div>
            <div class="px-3 py-2.5" style="background:#ffffff;">
                <p class="text-xs font-bold truncate" style="color:#1e293b;">{{ $empresa->name }}</p>
                <p class="text-[10px] truncate mt-0.5" style="color:#94a3b8;">{{ $websiteUrl }}</p>
            </div>
        </a>
    @else
        <div class="flex flex-col items-center justify-center py-6 text-center rounded-xl"
             style="background:#f8fafc;border:1px dashed #cbd5e1;">
            <div class="p-3 rounded-full mb-3" style="background:#e0e7ff;">
                <x-heroicon-o-globe-alt class="w-5 h-5" style="color:#4f46e5;" />
            </div>
            <p class="text-sm font-semibold mb-1" style="color:#1e293b;">Sin sitio web registrado</p>
            <p class="text-xs max-w-xs" style="color:#64748b;">
                El administrador puede agregar la URL del sitio web desde
                <strong>Admin → Empresas → Editar</strong>.
            </p>
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     AMPLIAR PLAN (solo si el servicio no está activo)
══════════════════════════════════════════════════════════════ --}}
@if(! $servicio_activo)
<div class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 p-10 flex flex-col items-center justify-center text-center gap-6">

    <div class="rounded-full bg-indigo-100 dark:bg-indigo-900/50 p-4">
        <x-heroicon-o-envelope class="w-12 h-12 text-indigo-500 dark:text-indigo-400"/>
    </div>

    <div>
        <p class="text-xl font-bold text-gray-900 dark:text-white">Módulo de Mailing</p>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 max-w-md mx-auto">
            Envía campañas de correo masivo, gestiona contactos y visualiza estadísticas de entrega en tiempo real.
        </p>
    </div>

    <div class="grid grid-cols-2 gap-3 text-left max-w-sm w-full">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-paper-airplane class="w-4 h-4 text-indigo-500 shrink-0"/> Campañas masivas
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-users class="w-4 h-4 text-indigo-500 shrink-0"/> Gestión de contactos
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-chart-bar class="w-4 h-4 text-indigo-500 shrink-0"/> Estadísticas en tiempo real
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-document-text class="w-4 h-4 text-indigo-500 shrink-0"/> Plantillas de correo
        </div>
    </div>

    <button
        wire:click="solicitarAmpliarPlan"
        wire:loading.attr="disabled"
        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-75 text-white font-semibold rounded-lg transition-colors duration-150 cursor-pointer"
    >
        <x-heroicon-o-rocket-launch class="w-5 h-5"/>
        <span wire:loading.remove wire:target="solicitarAmpliarPlan">Ampliar plan</span>
        <span wire:loading wire:target="solicitarAmpliarPlan">Enviando solicitud...</span>
    </button>

    <p class="text-xs text-gray-400 dark:text-gray-500">
        El equipo de soporte se pondrá en contacto para activar el servicio
    </p>

</div>
@endif


</div>
</x-filament-panels::page>
