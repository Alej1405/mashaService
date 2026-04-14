@extends('portal.layout')
@section('content')

<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Clientes registrados</h1>
        <p class="text-sm text-gray-500 mt-0.5">Todas las cuentas de clientes de {{ $empresa->name }}.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($customers->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-500">
                No hay clientes registrados aún.
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Correo</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Activo</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($customers as $c)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-medium text-gray-800">
                            {{ trim($c->nombre . ' ' . ($c->apellido ?? '')) }}
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $c->email }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $c->telefono ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            @if($c->activo)
                                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                            @else
                                <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $c->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($customers->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $customers->links() }}
            </div>
            @endif
        @endif
    </div>

</div>

@endsection
