@extends('mobile.layout')
@section('title', 'Iniciar Sesión')

@section('content')
<div class="flex-1 flex flex-col justify-center">

    {{-- Logo / Marca --}}
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
             style="background: rgba(79,70,229,0.2); border: 1px solid rgba(79,70,229,0.4);">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Mashaec ERP</h1>
        <p class="text-sm mt-1" style="color: rgba(232,230,240,0.5);">Acceso rápido empresarial</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl text-sm text-red-300"
             style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25);">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Formulario --}}
    <form method="POST" action="{{ route('mobile.login') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-medium mb-2" style="color: rgba(232,230,240,0.6);">
                Correo electrónico
            </label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="input w-full px-4 py-3 text-sm"
                   placeholder="usuario@empresa.com"
                   autocomplete="email" autofocus required>
        </div>

        <div>
            <label class="block text-xs font-medium mb-2" style="color: rgba(232,230,240,0.6);">
                Contraseña
            </label>
            <input type="password" name="password"
                   class="input w-full px-4 py-3 text-sm"
                   placeholder="••••••••"
                   autocomplete="current-password" required>
        </div>

        <button type="submit"
                class="btn-primary w-full py-3.5 text-sm mt-2">
            Ingresar
        </button>
    </form>

</div>

<p class="text-center text-xs mt-6" style="color: rgba(232,230,240,0.25);">
    Mashaec ERP · Solo uso empresarial
</p>
@endsection
