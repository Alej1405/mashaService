<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Empresa;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsPackage;
use App\Models\LogisticsPaymentClaim;
use App\Models\ServiceContract;
use App\Models\StoreCustomer;
use App\Models\StoreCustomerCompany;
use App\Models\StoreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PortalController extends Controller
{
    private function empresa(string $slug): Empresa
    {
        return Empresa::where('slug', $slug)->where('activo', true)->firstOrFail();
    }

    private function customer(Request $request): StoreCustomer
    {
        return StoreCustomer::withoutGlobalScopes()
            ->findOrFail($request->session()->get('store_customer_id'));
    }

    public function dashboard(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $recentOrders = StoreOrder::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();

        $activeContracts = ServiceContract::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->where('estado', 'activo')
            ->latest()
            ->limit(5)
            ->get();

        $totalOrders    = StoreOrder::withoutGlobalScopes()->where('store_customer_id', $customer->id)->count();
        $totalContracts = ServiceContract::withoutGlobalScopes()->where('store_customer_id', $customer->id)->where('estado', 'activo')->count();

        $pendingPackages = LogisticsPackage::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->where(function ($q) {
                // Finalizado en aduana (en espera de pago)
                $q->where('estado', 'finalizado_aduana')
                  // O marcado explícitamente como pago pendiente
                  ->orWhere('estado_secundario', 'pago_pendiente');
            })
            ->latest()
            ->get();

        $totalPendingPago = $pendingPackages->sum('monto_cobro');

        $totalPackages = LogisticsPackage::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->count();

        $recentPackages = LogisticsPackage::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->latest()
            ->limit(5)
            ->get();

        $cuentasBancarias = BankAccount::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->with('bank')
            ->get();

        return view('portal.dashboard', compact(
            'empresa', 'customer',
            'recentOrders', 'activeContracts',
            'totalOrders', 'totalContracts',
            'pendingPackages', 'totalPendingPago',
            'totalPackages', 'recentPackages',
            'cuentasBancarias',
        ));
    }

    public function orders(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $orders = StoreOrder::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return view('portal.orders', compact('empresa', 'customer', 'orders'));
    }

    public function orderShow(Request $request, string $slug, int $id)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $order = StoreOrder::withoutGlobalScopes()
            ->with(['orderItems.product', 'coupon'])
            ->where('store_customer_id', $customer->id)
            ->findOrFail($id);

        return view('portal.order-show', compact('empresa', 'customer', 'order'));
    }

    public function services(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $contracts = ServiceContract::withoutGlobalScopes()
            ->with('serviceDesign')
            ->where('store_customer_id', $customer->id)
            ->orderByRaw("FIELD(estado, 'activo', 'pausado', 'finalizado')")
            ->latest()
            ->paginate(10);

        return view('portal.services', compact('empresa', 'customer', 'contracts'));
    }

    public function packages(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $packages = LogisticsPackage::withoutGlobalScopes()
            ->with([
                'shipments'       => fn ($q) => $q->orderByDesc('created_at')->limit(1),
                'documents',
                'items',
                'servicePackage',
                'billingRequests' => fn ($q) => $q->latest()->limit(1),
            ])
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->latest()
            ->paginate(15);

        // Cargar payment claims del cliente para estos paquetes
        $packageIds   = $packages->pluck('id')->toArray();
        $paymentClaims = LogisticsPaymentClaim::withoutGlobalScopes()
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->get()
            ->filter(fn ($claim) => count(array_intersect($claim->package_ids ?? [], $packageIds)) > 0);

        // Indexar por package_id para acceso rápido en la vista
        $claimsByPackage = [];
        foreach ($paymentClaims as $claim) {
            foreach ($claim->package_ids ?? [] as $pid) {
                $claimsByPackage[$pid][] = $claim;
            }
        }

        return view('portal.packages', compact('empresa', 'customer', 'packages', 'claimsByPackage'));
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

        if (! Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $customer->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function submitPayment(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $request->validate([
            'package_ids'    => 'required|array|min:1',
            'package_ids.*'  => 'integer',
            'monto_manual'   => 'required|numeric|min:0.01',
            'comprobante'    => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf|max:10240',
            'notas_cliente'  => 'nullable|string|max:500',
        ]);

        // Verificar que los paquetes pertenecen al cliente
        $packageIds = LogisticsPackage::withoutGlobalScopes()
            ->whereIn('id', $request->package_ids)
            ->where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->pluck('id');

        if ($packageIds->isEmpty()) {
            return back()->withErrors(['package_ids' => 'Selecciona al menos un paquete válido.']);
        }

        $monto = (float) $request->monto_manual;

        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')
                ->store('comprobantes/' . $empresa->id, 'public');
        }

        LogisticsPaymentClaim::create([
            'empresa_id'       => $empresa->id,
            'store_customer_id' => $customer->id,
            'package_ids'      => $packageIds->toArray(),
            'monto_declarado'  => $monto,
            'comprobante_path' => $comprobantePath,
            'notas_cliente'    => $request->notas_cliente,
            'estado'           => 'pendiente',
        ]);

        return back()->with('payment_sent', '¡Pago registrado! Verificaremos tu transferencia a la brevedad.');
    }

    // ── Empresas ──────────────────────────────────────────────────────────────

    public function companies(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $companies = StoreCustomerCompany::where('store_customer_id', $customer->id)
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

        $data['store_customer_id'] = $customer->id;
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

        $companyRecord = StoreCustomerCompany::where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->findOrFail($company);

        return view('portal.companies-form', compact('empresa', 'customer', 'companyRecord'));
    }

    public function companiesUpdate(Request $request, string $slug, int $company)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        $companyRecord = StoreCustomerCompany::where('store_customer_id', $customer->id)
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

        StoreCustomerCompany::where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->findOrFail($company)
            ->delete();

        return redirect()
            ->route('portal.companies', $slug)
            ->with('success', 'Empresa eliminada.');
    }

    // ── Aceptación de nota de venta (link del correo o portal) ───────────────

    public function billingAccept(Request $request, string $slug, string $token)
    {
        $empresa = $this->empresa($slug);

        $billing = LogisticsBillingRequest::where('token', $token)
            ->where('empresa_id', $empresa->id)
            ->with(['package', 'storeCustomer'])
            ->firstOrFail();

        if ($billing->estado === 'aceptado') {
            return view('portal.billing-accepted', compact('empresa', 'billing'));
        }

        if ($billing->estado !== 'pendiente') {
            abort(410, 'Esta solicitud ya no está disponible.');
        }

        $customer = $billing->storeCustomer;

        // Listar empresas del cliente
        $companies = StoreCustomerCompany::where('store_customer_id', $customer->id)
            ->where('empresa_id', $empresa->id)
            ->get();

        return view('portal.billing-accept', compact('empresa', 'billing', 'customer', 'companies'));
    }

    public function billingConfirm(Request $request, string $slug, string $token)
    {
        $empresa = $this->empresa($slug);

        $billing = LogisticsBillingRequest::where('token', $token)
            ->where('empresa_id', $empresa->id)
            ->with('storeCustomer')
            ->firstOrFail();

        if ($billing->estado !== 'pendiente') {
            return redirect()->back()->withErrors(['error' => 'Esta solicitud ya no está disponible.']);
        }

        $request->validate([
            'billing_type'       => 'required|in:customer,company',
            'billing_company_id' => 'required_if:billing_type,company|nullable|integer',
        ]);

        $billingType = $request->billing_type;
        $company     = null;

        if ($billingType === 'company') {
            $company = StoreCustomerCompany::where('id', $request->billing_company_id)
                ->where('store_customer_id', $billing->store_customer_id)
                ->where('empresa_id', $empresa->id)
                ->firstOrFail();
        }

        $billing->aceptar('email', $billingType, $company, $billing->storeCustomer);

        return redirect()
            ->route('portal.billing.accept', [$slug, $token])
            ->with('accepted', true);
    }

    public function customers(Request $request, string $slug)
    {
        $empresa  = $this->empresa($slug);
        $customer = $this->customer($request);

        if (! $customer->is_super_admin) {
            abort(403);
        }

        $customers = StoreCustomer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('is_super_admin', false)
            ->latest()
            ->paginate(20);

        return view('portal.customers', compact('empresa', 'customer', 'customers'));
    }
}
