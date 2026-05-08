<x-filament-widgets::widget>
    <div class="space-y-2">

        {{-- Resumen de compromisos --}}
        <div class="d-card">
            <div class="d-card-header">
                <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#1e293b;">
                    <x-heroicon-o-banknotes class="w-3.5 h-3.5" style="color:#15803d;" />
                    Compromisos Financieros — {{ $mesNombre }}
                </h3>
                <span class="text-[9px] font-semibold" style="color:#94a3b8;">Costos fijos + servicio de deuda</span>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">

                <div class="rounded-lg p-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <p class="d-metric-lbl">Costos Fijos Op.</p>
                    <p class="d-metric-val mt-0.5">${{ number_format($costosFijosMensual, 0) }}</p>
                    <p class="text-[9px] mt-1" style="color:#94a3b8;">equivalente mensual</p>
                </div>

                <div class="rounded-lg p-3" style="background:#eef2ff;border:1px solid #c7d2fe;">
                    <p class="d-metric-lbl" style="color:#4338ca;">Cuotas este mes</p>
                    <p class="d-metric-val mt-0.5" style="color:#312e81;">${{ number_format($cuotasMesActual, 0) }}</p>
                    <p class="text-[9px] mt-1" style="color:#94a3b8;">servicio de deuda</p>
                </div>

                <div class="rounded-lg p-3" style="background:#faf5ff;border:1px solid #ddd6fe;">
                    <p class="d-metric-lbl" style="color:#7c3aed;">Total Mensual</p>
                    <p class="d-metric-val mt-0.5" style="color:#4c1d95;">${{ number_format($totalMensual, 0) }}</p>
                    <p class="text-[9px] mt-1" style="color:#94a3b8;">operativo + deudas</p>
                </div>

                <div class="rounded-lg p-3"
                     style="background:{{ $saldoTotalDeudas > 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $saldoTotalDeudas > 0 ? '#fecaca' : '#bbf7d0' }};">
                    <p class="d-metric-lbl" style="color:{{ $saldoTotalDeudas > 0 ? '#dc2626' : '#15803d' }};">Saldo Total Deudas</p>
                    <p class="d-metric-val mt-0.5" style="color:{{ $saldoTotalDeudas > 0 ? '#991b1b' : '#14532d' }};">${{ number_format($saldoTotalDeudas, 0) }}</p>
                    <p class="text-[9px] mt-1" style="color:#94a3b8;">deudas activas y parciales</p>
                </div>

            </div>
        </div>

        {{-- Barra de composición --}}
        @if($totalMensual > 0)
        <div class="d-card">
            <p class="text-[9px] font-bold uppercase tracking-widest mb-2" style="color:#94a3b8;">Composición del compromiso mensual</p>
            @php
                $pctOp   = $totalMensual > 0 ? ($costosFijosMensual / $totalMensual) * 100 : 0;
                $pctDebt = $totalMensual > 0 ? ($cuotasMesActual / $totalMensual) * 100 : 0;
            @endphp
            <div class="flex rounded-full overflow-hidden h-2.5" style="background:#f1f5f9;">
                @if($pctOp > 0)
                    <div class="h-full transition-all" style="width:{{ $pctOp }}%;background:#94a3b8;"></div>
                @endif
                @if($pctDebt > 0)
                    <div class="h-full transition-all" style="width:{{ $pctDebt }}%;background:#4f46e5;"></div>
                @endif
            </div>
            <div class="flex gap-4 mt-2">
                <span class="flex items-center gap-1.5 text-[9px] font-semibold" style="color:#64748b;">
                    <span class="inline-block w-2 h-2 rounded-full" style="background:#94a3b8;"></span>
                    Operativo {{ number_format($pctOp, 1) }}%
                </span>
                <span class="flex items-center gap-1.5 text-[9px] font-semibold" style="color:#64748b;">
                    <span class="inline-block w-2 h-2 rounded-full" style="background:#4f46e5;"></span>
                    Deudas {{ number_format($pctDebt, 1) }}%
                </span>
            </div>
        </div>
        @endif

        {{-- Alerta cuotas morosas --}}
        @if($cuotasMorosas->count() > 0)
        <div class="d-card" style="border-color:#fecaca;background:#fef2f2;">
            <div class="flex items-center gap-2 mb-3">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4" style="color:#dc2626;" />
                <p class="text-[10px] font-bold" style="color:#991b1b;">
                    {{ $cuotasMorosas->count() }} cuota(s) vencida(s) — Total: ${{ number_format($totalMoroso, 0) }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-[9px]">
                    <thead>
                        <tr style="color:#dc2626;border-bottom:1px solid #fecaca;">
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Deuda</th>
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Cuota</th>
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Venció</th>
                            <th class="pb-2 pr-3 text-right font-bold uppercase tracking-wider">Capital</th>
                            <th class="pb-2 pr-3 text-right font-bold uppercase tracking-wider">Interés</th>
                            <th class="pb-2 text-right font-bold uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cuotasMorosas as $linea)
                        <tr style="border-bottom:1px solid #fee2e2;">
                            <td class="py-1.5 pr-3 font-semibold" style="color:#1e293b;">{{ $linea->debt->acreedor ?? $linea->debt->numero }}</td>
                            <td class="py-1.5 pr-3" style="color:#64748b;">#{{ $linea->numero_cuota }}</td>
                            <td class="py-1.5 pr-3 font-bold" style="color:#dc2626;">
                                {{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->format('d/m/Y') }}
                                <span class="text-[8px] font-normal" style="color:#94a3b8;">({{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->diffForHumans() }})</span>
                            </td>
                            <td class="py-1.5 pr-3 text-right" style="color:#64748b;">${{ number_format($linea->monto_capital, 0) }}</td>
                            <td class="py-1.5 pr-3 text-right" style="color:#64748b;">${{ number_format($linea->monto_interes, 0) }}</td>
                            <td class="py-1.5 text-right font-bold" style="color:#991b1b;">${{ number_format($linea->total_cuota, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Próximas cuotas --}}
        <div class="d-card">
            <div class="d-card-header">
                <p class="text-[10px] font-bold uppercase tracking-widest" style="color:#475569;">
                    Próximas cuotas (60 días)
                </p>
                @if($proximasCuotas->count() > 0)
                    <span class="text-[9px] font-bold" style="color:#4f46e5;">
                        ${{ number_format($proximasCuotas->sum('total_cuota'), 0) }} en {{ $proximasCuotas->count() }} venc.
                    </span>
                @endif
            </div>

            @if($proximasCuotas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-[9px]">
                    <thead>
                        <tr style="color:#94a3b8;border-bottom:1px solid #e2e8f0;">
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Acreedor</th>
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Cuota</th>
                            <th class="pb-2 pr-3 text-left font-bold uppercase tracking-wider">Vence</th>
                            <th class="pb-2 pr-3 text-right font-bold uppercase tracking-wider">Capital</th>
                            <th class="pb-2 pr-3 text-right font-bold uppercase tracking-wider">Interés</th>
                            <th class="pb-2 pr-3 text-right font-bold uppercase tracking-wider">Total</th>
                            <th class="pb-2 text-left font-bold uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proximasCuotas as $linea)
                        @php
                            $dias    = now()->diffInDays(\Carbon\Carbon::parse($linea->fecha_vencimiento), false);
                            $urgente = $dias <= 7;
                            $proximo = $dias <= 15;
                        @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;background:{{ $urgente ? '#fffbeb' : 'transparent' }};">
                            <td class="py-1.5 pr-3 font-semibold" style="color:#1e293b;">
                                {{ $linea->debt->acreedor ?? '—' }}
                                <span style="color:#94a3b8;"> {{ $linea->debt->numero }}</span>
                            </td>
                            <td class="py-1.5 pr-3" style="color:#64748b;">#{{ $linea->numero_cuota }}</td>
                            <td class="py-1.5 pr-3 {{ $urgente ? 'font-bold' : '' }}" style="color:{{ $urgente ? '#d97706' : '#475569' }};">
                                {{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->format('d/m/Y') }}
                                @if($urgente)<span class="text-[8px]" style="color:#94a3b8;"> ({{ $dias }}d)</span>@endif
                            </td>
                            <td class="py-1.5 pr-3 text-right" style="color:#64748b;">${{ number_format($linea->monto_capital, 0) }}</td>
                            <td class="py-1.5 pr-3 text-right" style="color:#64748b;">${{ number_format($linea->monto_interes, 0) }}</td>
                            <td class="py-1.5 pr-3 text-right font-bold" style="color:{{ $urgente ? '#b45309' : '#1e293b' }};">${{ number_format($linea->total_cuota, 0) }}</td>
                            <td class="py-1.5">
                                @if($urgente)
                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase" style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;">Urgente</span>
                                @elseif($proximo)
                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase" style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;">Próximo</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase" style="background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:1px solid #e2e8f0;">
                            <td colspan="5" class="pt-2 pr-3 text-right font-semibold" style="color:#64748b;">Total próximos 60 días:</td>
                            <td class="pt-2 pr-3 text-right font-black" style="color:#1e293b;">${{ number_format($proximasCuotas->sum('total_cuota'), 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
                <p class="text-[10px] mt-1" style="color:#94a3b8;">No hay cuotas pendientes en los próximos 60 días.</p>
            @endif
        </div>

    </div>
</x-filament-widgets::widget>
