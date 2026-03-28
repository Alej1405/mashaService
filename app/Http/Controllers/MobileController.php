<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileController extends Controller
{
    // ── Helpers de autorización ───────────────────────────────────────────

    /** Empresa del usuario autenticado, o null si no tiene. */
    private function empresa()
    {
        return Auth::user()->empresa;
    }

    /** Verifica plan enterprise y empresa válida. */
    private function tieneAccesoEnterprise(): bool
    {
        $empresa = $this->empresa();
        return $empresa && ($empresa->plan ?? 'basic') === 'enterprise';
    }

    /** Solo admin_empresa (el super_admin no tiene empresa, usa el ERP completo). */
    private function esAdmin(): bool
    {
        return Auth::user()->hasRole('admin_empresa');
    }

    /** Respuesta de acceso denegado para GET (vista) o POST (JSON). */
    private function denegarAcceso(Request $request, string $mensaje): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => $mensaje], 403);
        }
        return redirect()->route('mobile.index');
    }

    // ── Auth ──────────────────────────────────────────────────────────────

    public function index()
    {
        $empresa = $this->empresa();

        if (!$empresa || ($empresa->plan ?? 'basic') !== 'enterprise') {
            return view('mobile.no-access');
        }

        return view('mobile.dashboard', compact('empresa'));
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('mobile.index');
        }

        return view('mobile.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, remember: true)) {
            $request->session()->regenerate();
            return redirect()->route('mobile.index');
        }

        return back()->withErrors([
            'email' => 'Credenciales incorrectas.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('mobile.login');
    }

    // ── Compra con foto OCR ───────────────────────────────────────────────

    public function showCompraOcr(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }

        $empresa   = $this->empresa();
        $suppliers = Supplier::where('empresa_id', $empresa->id)->orderBy('nombre')->get();
        $items     = InventoryItem::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();

        return view('mobile.compra-ocr', compact('empresa', 'suppliers', 'items'));
    }

    public function procesarOcr(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }

        $request->validate([
            'foto' => ['required', 'image', 'max:10240'],
        ]);

        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            return response()->json(['error' => 'OCR no configurado. Agrega OPENAI_API_KEY en el servidor.'], 422);
        }

        // Crear directorio temporal si no existe
        $tmpDir = storage_path('app/tmp/ocr');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $path     = $request->file('foto')->store('tmp/ocr', 'local');
        $fullPath = storage_path('app/' . $path);

        try {
            $python  = config('services.python.executable', 'python3');
            $script  = base_path('scripts/ocr_factura.py');
            $command = escapeshellarg($python) . ' ' . escapeshellarg($script)
                     . ' ' . escapeshellarg($fullPath)
                     . ' ' . escapeshellarg($apiKey);

            $output = shell_exec($command);
            @unlink($fullPath);

            if (!$output) {
                return response()->json(['error' => 'El script OCR no devolvió respuesta.'], 422);
            }

            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('OCR output no válido: ' . $output);
                return response()->json(['error' => 'Respuesta OCR inválida.'], 422);
            }

            if (isset($data['error'])) {
                return response()->json(['error' => $data['error']], 422);
            }

            // Buscar proveedor existente por RUC
            $supplier = null;
            if (!empty($data['ruc_proveedor'])) {
                $supplier = Supplier::where('empresa_id', $this->empresa()->id)
                    ->where('ruc', $data['ruc_proveedor'])
                    ->first();
            }

            $data['supplier_id']     = $supplier?->id;
            $data['supplier_nombre'] = $supplier?->nombre ?? $data['razon_social'] ?? null;

            return response()->json($data);

        } catch (\Exception $e) {
            @unlink($fullPath);
            Log::error('OCR error: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al procesar la imagen.'], 500);
        }
    }

    public function guardarCompra(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }

        $empresa = $this->empresa();

        $validated = $request->validate([
            'numero_factura'            => ['required', 'string', 'max:50'],
            'fecha'                     => ['required', 'date'],
            'supplier_id'               => ['nullable', 'integer', 'exists:proveedores,id'],
            'subtotal'                  => ['required', 'numeric', 'min:0'],
            'iva'                       => ['required', 'numeric', 'min:0'],
            'total'                     => ['required', 'numeric', 'min:0'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.descripcion'       => ['required', 'string'],
            'items.*.cantidad'          => ['required', 'numeric', 'min:0.0001'],
            'items.*.precio_unitario'   => ['required', 'numeric', 'min:0'],
            'items.*.inventory_item_id' => ['nullable', 'integer'],
        ]);

        // Verificar que los inventory_item_id pertenecen a la empresa
        $itemIds = array_filter(array_column($validated['items'], 'inventory_item_id'));
        if (!empty($itemIds)) {
            $validos = InventoryItem::where('empresa_id', $empresa->id)
                ->whereIn('id', $itemIds)
                ->pluck('id')
                ->toArray();
            foreach ($itemIds as $iid) {
                if (!in_array($iid, $validos)) {
                    return response()->json(['error' => 'Ítem de inventario no válido.'], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'empresa_id'     => $empresa->id,
                'supplier_id'    => $validated['supplier_id'] ?? null,
                'numero_factura' => $validated['numero_factura'],
                'date'           => $validated['fecha'],
                'subtotal'       => $validated['subtotal'],
                'iva'            => $validated['iva'],
                'total'          => $validated['total'],
                'tipo_pago'      => 'contado',
                'forma_pago'     => 'efectivo',
                'status'         => 'borrador',
            ]);

            foreach ($validated['items'] as $item) {
                $subtotalItem = round($item['cantidad'] * $item['precio_unitario'], 4);
                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'quantity'          => $item['cantidad'],
                    'unit_price'        => $item['precio_unitario'],
                    'aplica_iva'        => true,
                    'subtotal'          => $subtotalItem,
                    'iva_monto'         => 0,
                    'total_item'        => $subtotalItem,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra registrada correctamente.',
                'number'  => $purchase->number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando compra móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la compra.'], 500);
        }
    }

    // ── Validación de compras ─────────────────────────────────────────────

    public function listValidarCompras(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }

        if (!$this->esAdmin()) {
            return view('mobile.forbidden', [
                'mensaje' => 'Solo los administradores de empresa pueden validar compras.',
            ]);
        }

        $empresa = $this->empresa();
        $compras = Purchase::where('empresa_id', $empresa->id)
            ->where('status', 'borrador')
            ->with(['supplier', 'items.inventoryItem'])
            ->latest()
            ->get();

        return view('mobile.validar-compras', compact('empresa', 'compras'));
    }

    public function confirmarCompra(Request $request, Purchase $purchase)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }

        if (!$this->esAdmin()) {
            return response()->json(['error' => 'Solo los administradores pueden confirmar compras.'], 403);
        }

        // Verificar que la compra pertenece a la empresa del usuario
        if ($purchase->empresa_id !== $this->empresa()->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }

        if ($purchase->status !== 'borrador') {
            return response()->json(['error' => 'Solo se pueden confirmar compras en estado borrador.'], 422);
        }

        if (!$purchase->supplier_id) {
            return response()->json(['error' => 'La compra debe tener un proveedor asignado antes de confirmar.'], 422);
        }

        try {
            $purchase->update(['status' => 'confirmado']);
            return response()->json([
                'success' => true,
                'message' => 'Compra ' . $purchase->number . ' confirmada.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirmando compra móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al confirmar: ' . $e->getMessage()], 500);
        }
    }
}
