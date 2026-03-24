<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StoreCustomerController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user();
        return response()->json([
            'id'       => $customer->id,
            'nombre'   => $customer->nombre,
            'apellido' => $customer->apellido,
            'email'    => $customer->email,
            'telefono' => $customer->telefono,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'   => 'required|string|max:150',
            'apellido' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
        ]);

        $request->user()->update($request->only('nombre', 'apellido', 'telefono'));

        return response()->json(['message' => 'Perfil actualizado']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Contraseña actualizada']);
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses()->orderByDesc('es_principal')->get());
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $request->validate([
            'nombre_destinatario' => 'required|string|max:150',
            'linea1'              => 'required|string|max:255',
            'linea2'              => 'nullable|string|max:255',
            'ciudad'              => 'required|string|max:100',
            'provincia'           => 'nullable|string|max:100',
            'pais'                => 'nullable|string|max:100',
            'codigo_postal'       => 'nullable|string|max:20',
            'telefono'            => 'nullable|string|max:20',
            'es_principal'        => 'boolean',
        ]);

        if ($request->boolean('es_principal')) {
            $request->user()->addresses()->update(['es_principal' => false]);
        }

        $address = $request->user()->addresses()->create($request->validated());

        return response()->json($address, 201);
    }

    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $request->validate([
            'nombre_destinatario' => 'required|string|max:150',
            'linea1'              => 'required|string|max:255',
            'linea2'              => 'nullable|string|max:255',
            'ciudad'              => 'required|string|max:100',
            'provincia'           => 'nullable|string|max:100',
            'pais'                => 'nullable|string|max:100',
            'codigo_postal'       => 'nullable|string|max:20',
            'telefono'            => 'nullable|string|max:20',
            'es_principal'        => 'boolean',
        ]);

        if ($request->boolean('es_principal')) {
            $request->user()->addresses()->where('id', '!=', $id)->update(['es_principal' => false]);
        }

        $address->update($request->validated());

        return response()->json($address);
    }

    public function destroyAddress(Request $request, int $id): JsonResponse
    {
        $request->user()->addresses()->findOrFail($id)->delete();
        return response()->json(['message' => 'Dirección eliminada']);
    }
}
