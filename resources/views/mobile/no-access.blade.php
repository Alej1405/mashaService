@extends('mobile.layout')
@section('title', 'Acceso no disponible')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center text-center px-2">

    {{-- Ícono --}}
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl mb-6"
         style="background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.25);">
        <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </div>

    <h2 class="text-xl font-bold text-white mb-3">
        Usa una computadora o tablet
    </h2>

    <p class="text-sm leading-relaxed mb-2" style="color: rgba(232,230,240,0.55);">
        El sistema ERP está diseñado para pantallas más grandes.
        Para una experiencia óptima, accede desde tu computadora o tablet.
    </p>

    <p class="text-xs mt-1" style="color: rgba(232,230,240,0.3);">
        <strong class="text-indigo-400">erp.mashaec.net</strong>
    </p>

    {{-- Separador --}}
    <div class="w-12 h-px my-6" style="background: rgba(255,255,255,0.1);"></div>

    <p class="text-xs" style="color: rgba(232,230,240,0.3);">
        ¿Tienes una cuenta Enterprise?<br>
        Contacta a tu administrador para activar el acceso móvil.
    </p>

    {{-- Cerrar sesión --}}
    <form method="POST" action="{{ route('mobile.logout') }}" class="mt-8">
        @csrf
        <button type="submit" class="btn-danger px-6 py-2.5 text-xs font-medium">
            Cerrar sesión
        </button>
    </form>

</div>
@endsection
