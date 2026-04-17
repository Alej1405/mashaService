<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud confirmada — {{ $empresa->name }}</title>
    @if($empresa->logo_path)
        <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="max-w-md w-full bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center">

    @if($empresa->logo_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
             alt="{{ $empresa->name }}" class="h-10 w-auto mx-auto mb-6">
    @endif

    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-xl font-bold text-gray-800 mb-2">¡Solicitud confirmada!</h1>

    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
        Hemos recibido tu aceptación de la nota de venta
        <strong class="text-gray-700">{{ $billing->numero_nota_venta }}</strong>
        por un total de <strong class="text-orange-600">${{ number_format($billing->total, 2) }}</strong>.
        <br><br>
        La factura se emitirá a nombre de
        <strong class="text-gray-700">{{ $billing->billing_nombre }}</strong>
        @if($billing->billing_ruc)
        (RUC/CI: {{ $billing->billing_ruc }})
        @endif
        .
    </p>

    <a href="{{ route('portal.login', $empresa->slug) }}"
       class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
        Ir al portal →
    </a>

    <p class="text-xs text-gray-400 mt-6">{{ $empresa->name }} · Portal de clientes</p>

</div>

</body>
</html>
