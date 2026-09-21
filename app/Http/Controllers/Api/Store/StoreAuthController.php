<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Mail\CustomerResetPasswordMail;
use App\Mail\CustomerVerifyEmailMail;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StoreAuthController extends Controller
{
    /** Minutos que dura el enlace de recuperación. */
    private const RESET_TTL = 60;

    /** Espera mínima entre reenvíos de verificación, para no permitir spam de correos. */
    private const REENVIO_TTL = 2;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $empresa  = app('store.empresa');
        $customer = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->where('activo', true)
            ->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // Solo entran cuentas con el correo verificado.
        if (!$customer->access?->estaVerificado()) {
            return response()->json([
                'message'      => 'Debes verificar tu correo antes de iniciar sesión. Revisa tu bandeja de entrada.',
                'no_verificado' => true,
            ], 403);
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

        $exists = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El correo ya está registrado'], 422);
        }

        $customer = Customer::create([
            'empresa_id' => $empresa->id,
            'nombre'     => $request->nombre,
            'apellido'   => $request->apellido,
            'email'      => $request->email,
            'telefono'   => $request->telefono,
        ]);

        // La credencial vive en customer_access (contexto de acceso al portal).
        $access = $customer->access()->create([
            'empresa_id'           => $empresa->id,
            'password'             => Hash::make($request->password),
            'verification_token'   => Str::random(64),
            'verification_sent_at' => now(),
        ]);

        $this->enviarVerificacion($customer, $empresa, $access->verification_token);

        // Sin token de sesión: la cuenta no sirve hasta que verifique el correo.
        return response()->json([
            'message'  => 'Cuenta creada. Te enviamos un correo para verificarla.',
            'customer' => $this->customerData($customer),
        ], 201);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $empresa = app('store.empresa');
        $access  = \App\Models\CustomerAccess::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('verification_token', $request->token)
            ->first();

        if (!$access) {
            return response()->json(['message' => 'El enlace de verificación no es válido o ya fue usado.'], 422);
        }

        if ($access->estaVerificado()) {
            return response()->json(['message' => 'Esta cuenta ya estaba verificada.']);
        }

        $access->update([
            'email_verified_at'  => now(),
            'verification_token' => null,
        ]);

        return response()->json(['message' => 'Correo verificado. Ya puedes iniciar sesión.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $empresa  = app('store.empresa');
        $customer = $this->buscarPorEmail($empresa, $request->email);

        // Respuesta genérica: no revelamos si el correo existe.
        $generica = response()->json(['message' => 'Si la cuenta existe y está pendiente, te reenviamos el correo.']);

        $access = $customer?->access;
        if (!$access || $access->estaVerificado()) {
            return $generica;
        }

        if ($access->verification_sent_at?->diffInMinutes(now()) < self::REENVIO_TTL) {
            return response()->json([
                'message' => 'Ya te enviamos un correo hace poco. Espera unos minutos antes de pedir otro.',
            ], 429);
        }

        $access->update([
            'verification_token'   => Str::random(64),
            'verification_sent_at' => now(),
        ]);

        $this->enviarVerificacion($customer, $empresa, $access->verification_token);

        return $generica;
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
        $request->validate(['email' => 'required|email']);

        $empresa  = app('store.empresa');
        $customer = $this->buscarPorEmail($empresa, $request->email);

        // Siempre la misma respuesta: si distinguiéramos, cualquiera podría averiguar
        // qué correos están registrados en la tienda.
        $generica = response()->json(['message' => 'Si el correo existe, recibirás instrucciones.']);

        $access = $customer?->access;
        if (!$access) {
            return $generica;
        }

        $token = Str::random(64);
        $access->update([
            'reset_token'      => $token,
            'reset_expires_at' => now()->addMinutes(self::RESET_TTL),
        ]);

        try {
            Mail::to($customer->email)->send(
                new CustomerResetPasswordMail($customer, $empresa, $this->urlFront($empresa, 'recuperar-clave', $token))
            );
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de recuperación', [
                'customer_id' => $customer->id,
                'empresa_id'  => $empresa->id,
                'error'       => $e->getMessage(),
            ]);
        }

        return $generica;
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $empresa = app('store.empresa');
        $access  = \App\Models\CustomerAccess::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('reset_token', $request->token)
            ->first();

        if (!$access || !$access->resetTokenVigente($request->token)) {
            return response()->json(['message' => 'El enlace no es válido o ya caducó. Pide uno nuevo.'], 422);
        }

        $access->update([
            'password'         => Hash::make($request->password),
            'reset_token'      => null,
            'reset_expires_at' => null,
            // Quien recibe el correo demuestra que la dirección es suya.
            'email_verified_at' => $access->email_verified_at ?? now(),
        ]);

        // Cerrar las sesiones abiertas: si alguien más tenía la clave anterior, queda fuera.
        $access->customer?->tokens()->delete();

        return response()->json(['message' => 'Contraseña actualizada. Ya puedes iniciar sesión.']);
    }

    private function buscarPorEmail($empresa, string $email): ?Customer
    {
        return Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $email)
            ->where('activo', true)
            ->first();
    }

    private function enviarVerificacion(Customer $customer, $empresa, string $token): void
    {
        try {
            Mail::to($customer->email)->send(
                new CustomerVerifyEmailMail($customer, $empresa, $this->urlFront($empresa, 'verificar-correo', $token))
            );
        } catch (\Throwable $e) {
            // El registro no debe fallar porque el correo no salga; queda el reenvío.
            Log::error('No se pudo enviar el correo de verificación', [
                'customer_id' => $customer->id,
                'empresa_id'  => $empresa->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * El enlace apunta al frontend de cada cliente, no a la API: es el front quien
     * muestra la pantalla y luego llama a verify-email / reset-password con el token.
     */
    private function urlFront($empresa, string $ruta, string $token): string
    {
        $base = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');

        return $base . '/' . $empresa->slug . '/' . $ruta . '?token=' . $token;
    }

    private function customerData(Customer $customer): array
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
