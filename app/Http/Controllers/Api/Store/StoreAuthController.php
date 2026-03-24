<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StoreAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $empresa  = app('store.empresa');
        $customer = StoreCustomer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->where('activo', true)
            ->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $token = $customer->createToken('store')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'customer' => $this->customerData($customer),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'             => 'required|string|max:150',
            'apellido'           => 'nullable|string|max:150',
            'email'              => 'required|email|max:255',
            'password'           => 'required|min:8|confirmed',
            'telefono'           => 'nullable|string|max:20',
        ]);

        $empresa = app('store.empresa');

        $exists = StoreCustomer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El correo ya está registrado'], 422);
        }

        $customer = StoreCustomer::create([
            'empresa_id' => $empresa->id,
            'nombre'     => $request->nombre,
            'apellido'   => $request->apellido,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'telefono'   => $request->telefono,
        ]);

        $token = $customer->createToken('store')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'customer' => $this->customerData($customer),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->customerData($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        // TODO: implementar envío de correo de recuperación
        return response()->json(['message' => 'Si el correo existe, recibirás instrucciones.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        // TODO: implementar reset con token
        return response()->json(['message' => 'Contraseña actualizada']);
    }

    private function customerData(StoreCustomer $customer): array
    {
        return [
            'id'       => $customer->id,
            'nombre'   => $customer->nombre,
            'apellido' => $customer->apellido,
            'email'    => $customer->email,
            'telefono' => $customer->telefono,
        ];
    }
}
