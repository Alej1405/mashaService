<div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white border-b pb-2">Resumen de la Compra</h3>
    <table class="w-full text-xs text-left">
        <thead>
            <tr class="text-gray-500 uppercase tracking-wider font-semibold">
                <th class="py-1">Producto</th>
                <th class="py-1 text-right">Cant.</th>
                <th class="py-1 text-right">P. Unit.</th>
                <th class="py-1 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($get('items') ?? [] as $item)
                @php
                    $product = \App\Models\InventoryItem::find($item['inventory_item_id'] ?? null);
                    $subTotal = (float)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0);
                    $iva = ($item['aplica_iva'] ?? false) ? $subTotal * 0.15 : 0;
                @endphp
                <tr>
                    <td class="py-2">{{ $product?->nombre ?? 'S/N' }}</td>
                    <td class="py-2 text-right">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                    <td class="py-2 text-right">{{ number_format($item['unit_price'] ?? 0, 4) }}</td>
                    <td class="py-2 text-right">{{ number_format($subTotal + $iva, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
