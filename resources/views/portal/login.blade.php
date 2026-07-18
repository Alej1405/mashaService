<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — {{ $empresa->name }}</title>
    @if($empresa->logo_path)
        <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
        <link rel="apple-touch-icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; -webkit-font-smoothing: antialiased; }
        .login-card { animation: lc .4s cubic-bezier(0.23,1,0.32,1) both; }
        @keyframes lc { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { .login-card { animation: none; } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4"
      style="background:
        radial-gradient(60rem 40rem at 50% -10rem, #eef2ff 0%, rgba(238,242,255,0) 60%),
        #f6f7f9;">

<div class="w-full max-w-sm login-card">

    {{-- Marca --}}
    <div class="text-center mb-7">
        @if($empresa->logo_path)
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm grid place-items-center overflow-hidden">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}" alt="{{ $empresa->name }}" class="max-w-[80%] max-h-[80%] object-contain">
            </div>
        @else
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-indigo-600 text-white grid place-items-center text-2xl font-bold shadow-sm shadow-indigo-600/25">
                {{ mb_strtoupper(mb_substr($empresa->name, 0, 1)) }}
            </div>
        @endif
        <h1 class="text-lg font-bold text-slate-900">{{ $empresa->name }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Portal de clientes</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-[0_8px_30px_rgba(15,23,42,0.06)] p-6 sm:p-7">

        <div class="mb-5">
            <h2 class="text-base font-semibold text-slate-900">Bienvenido</h2>
            <p class="text-sm text-slate-500 mt-0.5">Ingresa con tu número de identificación.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ $loginAction }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="cedula" class="block text-sm font-medium text-slate-700 mb-1.5">Cédula o RUC</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 9h3m-3 3h3m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v9a2 2 0 002 2h1m2-6a2 2 0 11-4 0 2 2 0 014 0zm-4 4a3 3 0 016 0v.5H5z"/></svg>
                    </span>
                    <input type="text" id="cedula" name="cedula" value="{{ old('cedula') }}" required autofocus inputmode="numeric"
                           placeholder="1712345678"
                           class="w-full rounded-xl border border-slate-300 pl-11 pr-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <p class="mt-1.5 text-xs text-slate-400">El número con el que te registró {{ $empresa->name }}.</p>
            </div>
            <button type="submit"
                    class="group w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 text-sm shadow-sm shadow-indigo-600/25 transition active:scale-[0.98] flex items-center justify-center gap-2">
                Ingresar
                <svg class="w-4 h-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
        ¿Problemas para ingresar? Contacta a <strong class="text-slate-500">{{ $empresa->name }}</strong>.
    </p>
</div>

</body>
</html>
