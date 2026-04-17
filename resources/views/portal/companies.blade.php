@extends('portal.layout')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Mis empresas</h1>
        <p class="text-sm text-gray-500 mt-0.5">Empresas a las que representas o trabajas</p>
    </div>
    <a href="{{ route('portal.companies.create', $empresa->slug) }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Agregar empresa
    </a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
    {{ session('success') }}
</div>
@endif

@if($companies->isEmpty())
<div class="text-center py-16 bg-white rounded-2xl border border-gray-200">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <p class="text-gray-500 text-sm">No tienes empresas registradas.</p>
    <a href="{{ route('portal.companies.create', $empresa->slug) }}"
       class="mt-3 inline-block text-indigo-600 hover:underline text-sm font-medium">
        Agregar una empresa →
    </a>
</div>
@else
<div class="space-y-3">
    @foreach($companies as $company)
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="font-semibold text-gray-800 text-sm">{{ $company->nombre }}</h2>
                @if($company->cargo)
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 font-medium">
                    {{ $company->cargo }}
                </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-0.5 font-mono">RUC: {{ $company->ruc }}</p>
            @if($company->correo)
            <p class="text-xs text-gray-500 mt-0.5">{{ $company->correo }}</p>
            @endif
            @if($company->direccion)
            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $company->direccion }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('portal.companies.edit', [$empresa->slug, $company->id]) }}"
               class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition font-medium">
                Editar
            </a>
            <form action="{{ route('portal.companies.destroy', [$empresa->slug, $company->id]) }}"
                  method="POST"
                  onsubmit="return confirm('¿Eliminar esta empresa?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition font-medium">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
