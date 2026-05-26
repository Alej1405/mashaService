<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $customer = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->whereNotNull('password')
            ->where('activo', true)
            ->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->withInput();
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
