<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar nota de venta — {{ $empresa->name }}</title>
    @if($empresa->logo_path)
        <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-2xl mx-auto px-4 py-10">

    {{-- Encabezado empresa --}}
    <div class="text-center mb-8">
        @if($empresa->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                 alt="{{ $empresa->name }}" class="h-10 w-auto mx-auto mb-3">
        @else
            <p class="font-bold text-gray-800 text-xl">{{ $empresa->name }}</p>
        @endif
        <p class="text-sm text-gray-500">Portal de clientes</p>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Nota de venta --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">

        {{-- Cabecera SRI --}}
        <div class="bg-gray-900 px-5 py-4 flex items-start justify-between">
            <div>
                <p class="font-bold text-white text-base">{{ $empresa->name }}</p>
                @if($empresa->numero_identificacion)
                <p class="text-gray-400 text-xs mt-0.5">RUC: {{ $empresa->numero_identificacion }}</p>
                @endif
                @if($empresa->direccion)
                <p class="text-gray-400 text-xs mt-0.5">{{ $empresa->direccion }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-orange-400 font-bold text-sm uppercase tracking-wide">Nota de Venta</p>
                <p class="text-white font-mono font-bold text-sm mt-1">{{ $billing->numero_nota_venta }}</p>
                <p class="text-gray-400 text-xs mt-0.5">{{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        {{-- Datos cliente --}}
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex gap-6 flex-wrap">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Cliente</p>
                <p class="font-semibold text-gray-800 text-sm mt-0.5">{{ $customer->nombre_completo }}</p>
            </div>
            @if($customer->cedula_ruc)
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Identificación</p>
                <p class="font-semibold text-gray-800 font-mono text-sm mt-0.5">{{ $customer->cedula_ruc }}</p>
            </div>
            @endif
        </div>

        {{-- Ítems --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-semibold">Cód.</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-semibold">Descripción</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase font-semibold">Cant.</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-semibold">P. Unit.</th>
                        <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase font-semibold">IVA</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($billing->items as $item)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2.5 text-gray-500 text-xs font-mono">{{ $item['codigo'] }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ $item['descripcion'] }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-600">
                            {{ $item['cantidad'] }}
                            @if(!empty($item['unidad']))
                                <span class="block text-gray-400 text-xs">{{ $item['unidad'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right text-gray-600">${{ number_format($item['precio'], 4) }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-500 text-xs">{{ $item['iva_pct'] }}%</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-800">${{ number_format($item['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totales --}}
        <div class="border-t-2 border-gray-200 bg-gray-50 px-5 py-4">
            <div class="max-w-xs ml-auto space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500">
                    <span>SUBTOTAL 0%</span>
                    <span>${{ number_format($billing->subtotal_0, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>SUBTOTAL 15%</span>
                    <span>${{ number_format($billing->subtotal_15, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>IVA 15%</span>
                    <span>${{ number_format($billing->iva, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-300 pt-2 mt-2">
                    <span>VALOR TOTAL</span>
                    <span class="text-orange-600">${{ number_format($billing->total, 2) }}</span>
                </div>
            </div>
        </div>

    </div>

    {{-- Formulario de aceptación --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-6">

        <h2 class="font-bold text-gray-800 text-base mb-1">¿A nombre de quién se emite la factura?</h2>
        <p class="text-sm text-gray-500 mb-5">Selecciona la opción correspondiente para emitir la factura.</p>

        <form action="{{ route('portal.billing.confirm', [$empresa->slug, $billing->token]) }}" method="POST">
            @csrf

            <div class="space-y-3" id="billing-options">

                {{-- Opción: A mi nombre --}}
                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                              has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 border-gray-200 hover:border-gray-300">
                    <input type="radio" name="billing_type" value="customer"
                           class="mt-0.5 accent-indigo-600 shrink-0" required>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">A mi nombre</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $customer->nombre_completo }}
                            @if($customer->cedula_ruc) — {{ $customer->cedula_ruc }}@endif
                        </p>
                    </div>
                </label>

                {{-- Opciones: empresas del cliente --}}
                @foreach($companies as $company)
                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                              has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 border-gray-200 hover:border-gray-300">
                    <input type="radio" name="billing_type" value="company"
                           onclick="document.getElementById('billing_company_id').value='{{ $company->id }}'"
                           class="mt-0.5 accent-indigo-600 shrink-0">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-gray-800 text-sm">{{ $company->nombre }}</p>
                            @if($company->cargo)
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">
                                {{ $company->cargo }}
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 font-mono">RUC: {{ $company->ruc }}</p>
                        @if($company->direccion)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $company->direccion }}</p>
                        @endif
                    </div>
                </label>
                @endforeach

                {{-- Si no tiene empresas, mostrar enlace para agregar --}}
                @if($companies->isEmpty())
                <p class="text-xs text-gray-400 mt-1">
                    ¿Necesitas factura a nombre de una empresa?
                    <a href="{{ route('portal.login', $empresa->slug) }}" class="text-indigo-600 hover:underline">
                        Ingresa al portal para agregar tus empresas →
                    </a>
                </p>
                @endif

            </div>

            <input type="hidden" name="billing_company_id" id="billing_company_id" value="">

            <div class="mt-6">
                <button type="submit"
                        class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition text-sm">
                    Confirmar y enviar solicitud de facturación →
                </button>
            </div>

        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        {{ $empresa->name }} · Portal de clientes
    </p>

</div>

</body>
</html>
