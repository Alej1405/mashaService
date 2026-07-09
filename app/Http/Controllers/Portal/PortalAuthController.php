<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Empresa;
use Illuminate\Http\Request;

class PortalAuthController extends Controller
{
    private function empresa(string $slug): Empresa
    {
        return Empresa::where('slug', $slug)->where('activo', true)->firstOrFail();
    }

    public function showLogin(string $slug)
    {
        $empresa = $this->empresa($slug);

        if (request()->session()->has('portal_customer_id')) {
            return redirect()->route('portal.dashboard', $slug);
        }

        $loginAction = route('portal.login.post', $slug);

        return view('portal.login', compact('empresa', 'loginAction'));
    }

    public function login(Request $request, string $slug)
    {
        $empresa = $this->empresa($slug);

        $request->validate([
            'cedula' => 'required',
        ]);

        // El cliente entra con su número de cédula/RUC (con el que se lo registró),
        // único por empresa. No hay contraseñas propias: todo cliente activo de la
        // empresa tiene acceso al portal.
        $cedula = preg_replace('/\s+/', '', (string) $request->cedula);

        $customer = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->where('numero_identificacion', $cedula)
            ->first();

        if (! $customer) {
            return back()->withErrors(['cedula' => 'Cédula o RUC no encontrado para esta empresa.'])->withInput();
        }

        $request->session()->put('portal_customer_id', $customer->id);
        $request->session()->put('portal_empresa_id', $empresa->id);

        return redirect()->route('portal.dashboard', $slug);
    }

    public function logout(Request $request, string $slug)
    {
        $request->session()->forget(['portal_customer_id', 'portal_empresa_id']);
        return redirect()->route('portal.login', $slug);
    }
}
