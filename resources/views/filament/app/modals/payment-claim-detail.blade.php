<div class="space-y-4 text-sm">

    {{-- Datos del cliente --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Cliente</p>
            <p class="font-semibold text-gray-800">{{ $claim->storeCustomer?->nombre_completo ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Correo</p>
            <p class="font-semibold text-gray-800">{{ $claim->storeCustomer?->email ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Monto declarado</p>
            <p class="font-bold text-lg text-emerald-700">${{ number_format($claim->monto_declarado, 2) }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Registrado</p>
            <p class="font-semibold text-gray-800">{{ $claim->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    {{-- Paquetes incluidos --}}
    @php $packages = $claim->packages(); @endphp
    @if($packages->isNotEmpty())
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Paquetes incluidos</p>
        <table class="w-full border border-gray-100 rounded-lg overflow-hidden text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2 text-gray-500">Tracking</th>
                    <th class="text-left px-3 py-2 text-gray-500">Descripción</th>
                    <th class="text-right px-3 py-2 text-gray-500">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($packages as $pkg)
                <tr>
                    <td class="px-3 py-2 font-mono">{{ $pkg->numero_tracking ?? '#'.$pkg->id }}</td>
                    <td class="px-3 py-2 text-gray-600">{{ $pkg->descripcion ?? '—' }}</td>
                    <td class="px-3 py-2 text-right font-semibold">
                        {{ $pkg->monto_cobro ? '$'.number_format($pkg->monto_cobro, 2) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notas del cliente --}}
    @if($claim->notas_cliente)
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Notas del cliente</p>
        <p class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-gray-700">{{ $claim->notas_cliente }}</p>
    </div>
    @endif

    {{-- Comprobante --}}
    @if($claim->comprobante_path)
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Comprobante de transferencia</p>
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($claim->comprobante_path) }}"
             alt="Comprobante"
             class="max-w-full rounded-lg border border-gray-200 max-h-80 object-contain">
    </div>
    @endif

    {{-- Notas del verificador --}}
    @if($claim->notas_verificador)
    <div>
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Notas del verificador</p>
        <p class="bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 text-blue-800">{{ $claim->notas_verificador }}</p>
    </div>
    @endif

    {{-- Asiento contable --}}
    @if($claim->journalEntry)
    <div class="bg-emerald-50 border border-emerald-100 rounded-lg px-4 py-3">
        <p class="text-xs text-emerald-700 font-semibold">
            Asiento contable generado: <strong>{{ $claim->journalEntry->numero }}</strong>
        </p>
    </div>
    @endif

</div>
