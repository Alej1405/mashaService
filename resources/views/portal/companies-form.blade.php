@extends('portal.layout')

@section('content')

@php $editing = isset($companyRecord); @endphp

<div class="mb-5">
    <a href="{{ route('portal.companies', $empresa->slug) }}"
       class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 transition mb-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Mis empresas
    </a>
    <h1 class="text-xl font-bold text-gray-800">
        {{ $editing ? 'Editar empresa' : 'Nueva empresa' }}
    </h1>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
    {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6">
    <form action="{{ $editing
            ? route('portal.companies.update', [$empresa->slug, $companyRecord->id])
            : route('portal.companies.store',  $empresa->slug) }}"
          method="POST">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- RUC --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    RUC <span class="text-red-500">*</span>
                </label>
                <input type="text" name="ruc" maxlength="13"
                       value="{{ old('ruc', $companyRecord->ruc ?? '') }}"
                       placeholder="13 dígitos"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-400
                              @error('ruc') border-red-400 @enderror">
                @error('ruc')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre de la empresa <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nombre" maxlength="200"
                       value="{{ old('nombre', $companyRecord->nombre ?? '') }}"
                       placeholder="Razón social"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-400
                              @error('nombre') border-red-400 @enderror">
                @error('nombre')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Correo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Correo de la empresa
                </label>
                <input type="email" name="correo" maxlength="200"
                       value="{{ old('correo', $companyRecord->correo ?? '') }}"
                       placeholder="contacto@empresa.com"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-400
                              @error('correo') border-red-400 @enderror">
                @error('correo')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cargo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tu cargo en la empresa
                </label>
                <input type="text" name="cargo" maxlength="150"
                       value="{{ old('cargo', $companyRecord->cargo ?? '') }}"
                       placeholder="Ej. Representante legal, Contador..."
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-400
                              @error('cargo') border-red-400 @enderror">
                @error('cargo')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Dirección --}}
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Dirección
                </label>
                <input type="text" name="direccion" maxlength="300"
                       value="{{ old('direccion', $companyRecord->direccion ?? '') }}"
                       placeholder="Dirección fiscal de la empresa"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-400
                              @error('direccion') border-red-400 @enderror">
                @error('direccion')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">
                {{ $editing ? 'Guardar cambios' : 'Registrar empresa' }}
            </button>
            <a href="{{ route('portal.companies', $empresa->slug) }}"
               class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition">
                Cancelar
            </a>
        </div>

    </form>
</div>

@endsection
