<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\StoreCustomer;
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

        if (request()->session()->has('store_customer_id')) {
            return redirect()->route('portal.dashboard', $slug);
        }

        return view('portal.login', compact('empresa'));
    }

    public function login(Request $request, string $slug)
    {
        $empresa = $this->empresa($slug);

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $customer = StoreCustomer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('email', $request->email)
            ->where('activo', true)
            ->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->withInput();
        }

        $request->session()->put('store_customer_id', $customer->id);
        $request->session()->put('store_empresa_id', $empresa->id);

        return redirect()->route('portal.dashboard', $slug);
    }

    public function logout(Request $request, string $slug)
    {
        $request->session()->forget(['store_customer_id', 'store_empresa_id']);
        return redirect()->route('portal.login', $slug);
    }
}
