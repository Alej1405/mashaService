<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — {{ $empresa->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
        @if($empresa->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}" alt="{{ $empresa->name }}" class="h-12 mx-auto mb-3">
        @endif
        <h1 class="text-xl font-bold text-gray-800">{{ $empresa->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Portal de clientes</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

        <h2 class="text-base font-semibold text-gray-800 mb-5">Iniciar sesión</h2>

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('portal.login.post', $empresa->slug) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 text-sm transition">
                Ingresar
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        ¿Problemas para ingresar? Contacta a {{ $empresa->name }}.
    </p>
</div>

</body>
</html>
