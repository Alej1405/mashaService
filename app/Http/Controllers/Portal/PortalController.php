<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\StoreProduct;
use App\Models\ServiceContract;
use App\Models\ServiceDesign;
use App\Models\Customer;
use App\Models\StoreCustomerCompany;
use App\Models\StoreOrder;
use App\Shared\Actions\OlvidarCacheCmsPunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PortalController extends Controller
{
    private function empresa(string $slug): Empresa
    {
        return Empresa::where('slug', $slug)->where('activo', true)->firstOrFail();
    }

    private function customer(Request $request): Customer
    {
        return Customer::withoutGlobalScopes()
            ->findOrFail($request->session()->get('portal_customer_id'));
    }

    /**
     * Tras cualquier cambio del portal que afecte la landing pública (web, galería,
     * menú), se invalida la caché de la API CMS para que se refleje al instante y no
     * espere los 10 min del TTL. Ver App\Shared\Actions\OlvidarCacheCmsPunto.
     */
    private function olvidarCacheCms(Customer $customer): void
    {
        (new OlvidarCacheCmsPunto)->handle($customer);
    }

    public function dashboard(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $recentOrders = StoreOrder::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();

        $activeContracts = ServiceContract::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('estado', 'activo')
            ->latest()
            ->limit(5)
            ->get();

        $totalOrders    = StoreOrder::withoutGlobalScopes()->where('customer_id', $customer->id)->count();
        $totalContracts = ServiceContract::withoutGlobalScopes()->where('customer_id', $customer->id)->where('estado', 'activo')->count();

        $catalogoProductos = StoreProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->orderBy('nombre')
            ->get();

        $tieneServicios = ServiceDesign::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->exists();

        return view('portal.dashboard', compact(
            'empresa', 'customer',
            'recentOrders', 'activeContracts',
            'totalOrders', 'totalContracts',
            'catalogoProductos', 'tieneServicios',
        ));
    }

    public function orders(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $orders = StoreOrder::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return view('portal.orders', compact('empresa', 'customer', 'orders'));
    }

    /** Formulario para armar un pedido nuevo con el catálogo publicado de la empresa. */
    public function orderCreate(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $productos = StoreProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->with('storeCategory')
            ->orderBy('nombre')
            ->get();

        return view('portal.order-create', compact('empresa', 'customer', 'productos'));
    }

    /** Crea el pedido (queda pendiente) reutilizando el mismo servicio que la API. */
    public function orderStore(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $data = $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:1',
            'notas'            => 'nullable|string|max:500',
        ]);

        // Solo líneas con cantidad real, en el formato que espera el servicio.
        $items = collect($data['items'])
            ->filter(fn ($i) => (float) $i['cantidad'] > 0)
            ->map(fn ($i) => ['store_product_id' => (int) $i['id'], 'cantidad' => (float) $i['cantidad']])
            ->values()
            ->all();

        if (empty($items)) {
            return back()->withErrors(['items' => 'Agrega al menos un producto al pedido.'])->withInput();
        }

        // El servicio exige dirección de envío; usamos la del cliente.
        $shipping = [
            'linea1' => $customer->direccion ?: 'Retiro en tienda',
            'ciudad' => 'N/D',
        ];

        try {
            $order = app(\App\Services\StoreOrderService::class)->createOrder(
                empresa:         $empresa,
                customer:        $customer,
                items:           $items,
                shippingAddress: $shipping,
                couponCode:      null,
                notes:           $data['notas'] ?? null,
                origen:          'cliente',
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('portal.orders.show', [$empresa->slug, $order->id])
            ->with('success', "Pedido #{$order->id} creado. Ya lo recibimos, está pendiente de confirmación.");
    }

    public function orderShow(Request $request, string $slug, int $id)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $order = StoreOrder::withoutGlobalScopes()
            ->with(['orderItems.product', 'coupon'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        return view('portal.order-show', compact('empresa', 'customer', 'order'));
    }

    // ── Mi web (landing) ────────────────────────────────────────────────────

    /** Fila web del cliente (get-or-new), fuente de verdad del CONTENIDO de la landing. */
    private function webRow(Customer $customer): \App\Models\CustomerWeb
    {
        return $customer->web()->withoutGlobalScopes()->first()
            ?? new \App\Models\CustomerWeb(['customer_id' => $customer->id, 'empresa_id' => $customer->empresa_id]);
    }

    public function webEdit(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        if (! $customer->publicado) {
            return redirect()->route('portal.dashboard', $empresa->slug)
                ->with('success', 'Tu página web aún no está habilitada. Contáctanos para activarla.');
        }

        $web = $this->webRow($customer);

        return view('portal.web-edit', compact('empresa', 'customer', 'web'));
    }

    public function webUpdate(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);
        abort_unless($customer->publicado, 403);

        $data = $request->validate([
            'descripcion_web'  => 'nullable|string|max:2000',
            'horario'          => 'nullable|string|max:180',
            'latitud'          => 'nullable|numeric|between:-90,90',
            'longitud'         => 'nullable|numeric|between:-180,180',
            'google_maps_url'  => 'nullable|url|max:500',
            // 2048 KB = el upload_max_filesize del servidor. Más alto, PHP corta
            // la petición antes de validar y el error no dice nada.
            'logo'             => 'nullable|image|max:2048',
        ]);

        $mapsUrl = trim((string) ($data['google_maps_url'] ?? ''));
        $coords  = $this->coordsDesdeGoogleMaps($mapsUrl);

        $web = $this->webRow($customer);
        $web->descripcion_web  = $data['descripcion_web'] ?? null;
        $web->horario          = $data['horario'] ?? null;
        $web->google_maps_url  = $mapsUrl ?: null;
        $web->latitud          = $coords[0] ?? ($data['latitud'] ?? null);
        $web->longitud         = $coords[1] ?? ($data['longitud'] ?? null);

        if ($request->hasFile('logo')) {
            $web->logo = $request->file('logo')->store('clientes/logos', 'public');
        }

        $web->save();

        $this->olvidarCacheCms($customer);

        return back()->with('success', 'Tu información se actualizó.');
    }

    public function services(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $contracts = ServiceContract::withoutGlobalScopes()
            ->with('serviceDesign')
            ->where('customer_id', $customer->id)
            // Mismo caso que en producción: FIELD() no existe en Postgres.
            ->orderByRaw("CASE estado WHEN 'activo' THEN 1 WHEN 'pausado' THEN 2 WHEN 'finalizado' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(10);

        return view('portal.services', compact('empresa', 'customer', 'contracts'));
    }


    public function profile(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        return view('portal.profile', compact('empresa', 'customer'));
    }

    public function updateProfile(Request $request, string $slug)
    {
        $customer = $this->customer($request);

        $request->validate([
            'nombre'   => 'required|string|max:150',
            'apellido' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
        ]);

        $customer->update($request->only('nombre', 'apellido', 'telefono'));

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request, string $slug)
    {
        $customer = $this->customer($request);

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, (string) $customer->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        // La contraseña vive en customer_access (contexto de acceso al portal).
        $customer->access()->updateOrCreate(
            ['customer_id' => $customer->id],
            ['empresa_id' => $customer->empresa_id, 'password' => Hash::make($request->password)],
        );

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function companies(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $companies = StoreCustomerCompany::where('customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->latest()
            ->get();

        return view('portal.companies', compact('empresa', 'customer', 'companies'));
    }

    public function companiesCreate(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        return view('portal.companies-form', compact('empresa', 'customer'));
    }

    public function companiesStore(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $data = $request->validate([
            'ruc'       => 'required|string|size:13',
            'nombre'    => 'required|string|max:200',
            'direccion' => 'nullable|string|max:300',
            'correo'    => 'nullable|email|max:200',
            'cargo'     => 'nullable|string|max:150',
        ]);

        $data['customer_id'] = $customer->id;
        $data['empresa_id']        = $empresa->id;

        StoreCustomerCompany::create($data);

        return redirect()
            ->route('portal.companies', $slug)
            ->with('success', 'Empresa registrada correctamente.');
    }

    public function companiesEdit(Request $request, string $slug, int $company)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $companyRecord = StoreCustomerCompany::where('customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->findOrFail($company);

        return view('portal.companies-form', compact('empresa', 'customer', 'companyRecord'));
    }

    public function companiesUpdate(Request $request, string $slug, int $company)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $companyRecord = StoreCustomerCompany::where('customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->findOrFail($company);

        $data = $request->validate([
            'ruc'       => 'required|string|size:13',
            'nombre'    => 'required|string|max:200',
            'direccion' => 'nullable|string|max:300',
            'correo'    => 'nullable|email|max:200',
            'cargo'     => 'nullable|string|max:150',
        ]);

        $companyRecord->update($data);

        return redirect()
            ->route('portal.companies', $slug)
            ->with('success', 'Empresa actualizada correctamente.');
    }

    public function companiesDestroy(Request $request, string $slug, int $company)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        StoreCustomerCompany::where('customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->findOrFail($company)
            ->delete();

        return redirect()
            ->route('portal.companies', $slug)
            ->with('success', 'Empresa eliminada.');
    }

    public function customers(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        if (! $customer->is_super_admin) {
            abort(403);
        }

        $customers = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereDoesntHave('access', fn ($q) => $q->where('is_super_admin', true))
            ->latest()
            ->paginate(20);

        return view('portal.customers', compact('empresa', 'customer', 'customers'));
    }
}
