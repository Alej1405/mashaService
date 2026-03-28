@extends('mobile.layout')
@section('title', 'Sin permiso')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center text-center px-2">

    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl mb-6"
         style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);">
        <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6V7m0 0a5 5 0 00-5 5v1H5a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2h-2v-1a5 5 0 00-5-5z"/>
        </svg>
    </div>

    <h2 class="text-xl font-bold text-white mb-3">Acceso restringido</h2>

    <p class="text-sm leading-relaxed" style="color: rgba(232,230,240,0.55);">
        {{ $mensaje ?? 'No tienes permiso para acceder a esta sección.' }}
    </p>

    <div class="w-12 h-px my-6" style="background: rgba(255,255,255,0.1);"></div>

    <a href="{{ route('mobile.index') }}"
       class="btn-primary px-8 py-3 text-sm inline-block">
        Volver al inicio
    </a>

</div>
@endsection
