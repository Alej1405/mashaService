<x-filament-widgets::widget>
    <div class="d-card">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#1e293b;">
                <x-heroicon-o-building-library class="w-3.5 h-3.5" style="color:#4f46e5;" />
                Tesorería &amp; Operaciones
            </h3>
            <span class="text-[9px] font-semibold" style="color:#94a3b8;">Liquidez y pendientes en tiempo real</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">

            {{-- Efectivo en caja --}}
            <div class="col-span-2 md:col-span-1 rounded-lg p-3 flex flex-col justify-between" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <div>
                    <p class="d-metric-lbl" style="color:#15803d;">Efectivo en Caja</p>
                    <p class="d-metric-val mt-1" style="color:#14532d;">${{ number_format($totalEfectivo, 2) }}</p>
                </div>
                @if($cajas->count() > 0)
                <div class="mt-2 space-y-0.5">
                    @foreach($cajas as $caja)
                    <div class="flex justify-between items-center">
                        <span class="text-[9px]" style="color:#15803d;">{{ \Illuminate\Support\Str::limit($caja->nombre, 14) }}</span>
                        <span class="text-[9px] font-bold" style="color:#14532d;">${{ number_format($caja->saldo_actual, 0) }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-[9px] mt-1" style="color:#94a3b8;">Sin cajas registradas</p>
                @endif
                <a href="/{{ $panelPath }}/{{ $tenant }}/cash-registers"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#15803d;">
                    Ver cajas <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

            {{-- Cuentas bancarias --}}
            <div class="col-span-2 md:col-span-1 rounded-lg p-3 flex flex-col justify-between" style="background:#eef2ff;border:1px solid #c7d2fe;">
                <div>
                    <p class="d-metric-lbl" style="color:#4338ca;">Cuentas Bancarias</p>
                    <p class="d-metric-val mt-1" style="color:#312e81;">{{ $totalBancos }}</p>
                </div>
                @if($bancos->count() > 0)
                <div class="mt-2 space-y-0.5">
                    @foreach($bancos->take(3) as $banco)
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] truncate" style="color:#4338ca;max-width:100px;">{{ $banco->bank->nombre ?? '—' }}</span>
                        <span class="text-[9px] font-bold uppercase" style="color:#312e81;">{{ $banco->tipo_cuenta }}</span>
                    </div>
                    @endforeach
                    @if($bancos->count() > 3)
                        <p class="text-[9px]" style="color:#94a3b8;">+{{ $bancos->count() - 3 }} más</p>
                    @endif
                </div>
                @else
                <p class="text-[9px] mt-1" style="color:#94a3b8;">Sin cuentas registradas</p>
                @endif
                <a href="/{{ $panelPath }}/{{ $tenant }}/bank-accounts"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#4338ca;">
                    Ver cuentas <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

            {{-- Ventas por confirmar --}}
            <div class="rounded-lg p-3 flex flex-col justify-between" style="background:#fef2f2;border:1px solid #fecaca;">
                <div>
                    <p class="d-metric-lbl" style="color:#dc2626;">Ventas por Confirmar</p>
                    <p class="d-metric-val mt-1" style="color:#991b1b;">{{ $ventasBorrador->cantidad ?? 0 }}</p>
                    <p class="text-[9px] font-bold mt-1" style="color:#dc2626;">${{ number_format($ventasBorrador->total ?? 0, 0) }}</p>
                </div>
                <a href="/{{ $panelPath }}/{{ $tenant }}/sales?tableFilters[estado][value]=borrador"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#dc2626;">
                    Revisar <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

            {{-- Compras por confirmar --}}
            <div class="rounded-lg p-3 flex flex-col justify-between" style="background:#fffbeb;border:1px solid #fde68a;">
                <div>
                    <p class="d-metric-lbl" style="color:#d97706;">Compras por Confirmar</p>
                    <p class="d-metric-val mt-1" style="color:#92400e;">{{ $comprasBorrador->cantidad ?? 0 }}</p>
                    <p class="text-[9px] font-bold mt-1" style="color:#d97706;">${{ number_format($comprasBorrador->total ?? 0, 0) }}</p>
                </div>
                <a href="/{{ $panelPath }}/{{ $tenant }}/purchases"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#d97706;">
                    Revisar <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

            {{-- Clientes nuevos --}}
            <div class="rounded-lg p-3 flex flex-col justify-between" style="background:#faf5ff;border:1px solid #ddd6fe;">
                <div>
                    <p class="d-metric-lbl" style="color:#7c3aed;">Clientes Nuevos</p>
                    <p class="d-metric-val mt-1" style="color:#4c1d95;">{{ $clientesNuevosMes }}</p>
                    <p class="text-[9px] font-medium mt-1" style="color:#94a3b8;">este mes</p>
                </div>
                <a href="/{{ $panelPath }}/{{ $tenant }}/customers"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#7c3aed;">
                    Ver clientes <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

            {{-- Producción pendiente --}}
            <div class="rounded-lg p-3 flex flex-col justify-between" style="background:#f0f9ff;border:1px solid #bae6fd;">
                <div>
                    <p class="d-metric-lbl" style="color:#0369a1;">Producción Pendiente</p>
                    <p class="d-metric-val mt-1" style="color:#0c4a6e;">{{ $produccionActiva }}</p>
                    <p class="text-[9px] font-medium mt-1" style="color:#94a3b8;">órdenes borrador</p>
                </div>
                <a href="/{{ $panelPath }}/{{ $tenant }}/production-orders"
                   class="mt-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#0369a1;">
                    Ver órdenes <x-heroicon-s-chevron-right class="w-2 h-2" />
                </a>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
