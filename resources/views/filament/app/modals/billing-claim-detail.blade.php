<div class="space-y-4 text-sm">

    {{-- Datos de la nota de venta --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">N.° Nota de venta</p>
            <p class="font-mono font-semibold text-gray-800">{{ $billing->numero_nota_venta }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                {{ $billing->estado === 'cobrado' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ \App\Models\LogisticsBillingRequest::ESTADOS[$billing->estado]['label'] ?? $billing->estado }}
            </span>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Cliente</p>
            <p class="font-semibold text-gray-800">{{ $billing->storeCustomer?->nombre_completo ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Correo</p>
            <p class="text-gray-700">{{ $billing->storeCustomer?->email ?? '—' }}</p>
        </div>
    </div>

    {{-- Datos de facturación --}}
    <div class="bg-gray-50 border border-gray-100 rounded-lg px-4 py-3 grid grid-cols-2 gap-2">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Facturar a</p>
            <p class="font-semibold text-gray-800">{{ $billing->billing_nombre ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">RUC / Cédula</p>
            <p class="font-mono text-gray-800">{{ $billing->billing_ruc ?? '—' }}</p>
        </div>
    </div>

    {{-- Ítems --}}
    @if($billing->items)
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Detalle de la nota de venta</p>
        <table class="w-full border border-gray-100 rounded-lg overflow-hidden text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2 text-gray-500">Descripción</th>
                    <th class="text-center px-3 py-2 text-gray-500">Cant.</th>
                    <th class="text-right px-3 py-2 text-gray-500">P. Unit.</th>
                    <th class="text-center px-3 py-2 text-gray-500">IVA</th>
                    <th class="text-right px-3 py-2 text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($billing->items as $item)
                <tr>
                    <td class="px-3 py-2 text-gray-700">{{ $item['descripcion'] }}</td>
                    <td class="px-3 py-2 text-center text-gray-600">
                        {{ $item['cantidad'] }}
                        @if(!empty($item['unidad']))
                            <span class="block text-gray-400 text-[10px]">{{ $item['unidad'] }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right text-gray-600">${{ number_format($item['precio'], 4) }}</td>
                    <td class="px-3 py-2 text-center text-gray-500">{{ $item['iva_pct'] }}%</td>
                    <td class="px-3 py-2 text-right font-semibold">${{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                @if($billing->subtotal_0 > 0)
                <tr>
                    <td colspan="4" class="px-3 py-1 text-gray-500">Subtotal 0%</td>
                    <td class="px-3 py-1 text-right">${{ number_format($billing->subtotal_0, 2) }}</td>
                </tr>
                @endif
                @if($billing->subtotal_15 > 0)
                <tr>
                    <td colspan="4" class="px-3 py-1 text-gray-500">Subtotal 15%</td>
                    <td class="px-3 py-1 text-right">${{ number_format($billing->subtotal_15, 2) }}</td>
                </tr>
                @endif
                @if((float)($billing->descuento_monto ?? 0) > 0)
                <tr class="text-green-600 font-medium">
                    <td colspan="4" class="px-3 py-1">
                        Descuento
                        @if($billing->descuento_tipo === 'cliente_fijo') (cliente fijo)
                        @elseif($billing->descuento_tipo === 'promocion') (promoción)
                        @else ({{ $billing->descuento_descripcion ?? 'otro' }})
                        @endif
                    </td>
                    <td class="px-3 py-1 text-right">− ${{ number_format($billing->descuento_monto, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="4" class="px-3 py-1 text-gray-500">IVA 15%</td>
                    <td class="px-3 py-1 text-right">${{ number_format($billing->iva, 2) }}</td>
                </tr>
                <tr class="font-bold text-base">
                    <td colspan="4" class="px-3 py-2 text-gray-700">TOTAL</td>
                    <td class="px-3 py-2 text-right text-emerald-700">${{ number_format($billing->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Comprobante del cliente --}}
    @if($claim)
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Comprobante de pago del cliente</p>
        <div class="grid grid-cols-2 gap-2 mb-2 text-xs">
            <div>
                <span class="text-gray-500">Monto declarado:</span>
                <span class="font-semibold ml-1">${{ number_format($claim->monto_declarado, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-500">Enviado:</span>
                <span class="ml-1">{{ $claim->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        @if($claim->notas_cliente)
        <p class="bg-gray-50 border border-gray-100 rounded px-3 py-2 text-gray-700 mb-2">{{ $claim->notas_cliente }}</p>
        @endif
        @if($claim->comprobante_path)
            @php $ext = pathinfo($claim->comprobante_path, PATHINFO_EXTENSION); @endphp
            @if(in_array(strtolower($ext), ['pdf']))
            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($claim->comprobante_path) }}"
               target="_blank"
               class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ver comprobante PDF
            </a>
            @else
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($claim->comprobante_path) }}"
                 alt="Comprobante"
                 class="max-w-full rounded-lg border border-gray-200 max-h-80 object-contain">
            @endif
        @endif
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-100 rounded-lg px-4 py-3">
        <p class="text-xs text-yellow-700">El cliente aún no ha enviado comprobante de pago.</p>
    </div>
    @endif

    {{-- Verificación --}}
    @if($billing->estado === 'cobrado')
    <div class="bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3">
        <p class="text-xs text-emerald-700 font-semibold mb-1">Cobro verificado</p>
        @if($billing->verificado_at)
        <p class="text-xs text-emerald-600">Verificado el {{ $billing->verificado_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>
    @endif

</div>
