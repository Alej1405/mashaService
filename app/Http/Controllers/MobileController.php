<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\ProductionOrder;
use App\Models\ProductPresentation;
use App\Models\UbicacionAlmacen;
use App\Models\ZonaAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    // ── Almacenes ─────────────────────────────────────────────────────────

    public function listAlmacenes(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa   = $this->empresa();
        $almacenes = Almacen::where('empresa_id', $empresa->id)
            ->orderBy('nombre')
            ->get();
        return view('mobile.almacenes', compact('empresa', 'almacenes'));
    }

    public function showAlmacenForm(Request $request, ?Almacen $almacen = null)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }

        // Si se pasó un almacen verificar que pertenece a la empresa
        if ($almacen && $almacen->empresa_id !== $this->empresa()->id) {
            abort(403);
        }

        $empresa = $this->empresa();
        return view('mobile.almacen-form', compact('empresa', 'almacen'));
    }

    public function guardarAlmacen(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $validated = $request->validate([
            'almacen_id'  => ['nullable', 'integer'],
            'codigo'      => ['required', 'string', 'max:20'],
            'nombre'      => ['required', 'string', 'max:150'],
            'tipo'        => ['required', 'in:bodega_propia,deposito_externo,area_produccion,punto_venta,transito'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'direccion'   => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo'      => ['boolean'],
        ]);

        try {
            $almacenId = $validated['almacen_id'] ?? null;

            if ($almacenId) {
                // Editar existente
                $almacen = Almacen::where('empresa_id', $empresa->id)->findOrFail($almacenId);

                // Verificar unicidad del código (excluyendo el registro actual)
                $codigoExiste = Almacen::where('empresa_id', $empresa->id)
                    ->where('codigo', $validated['codigo'])
                    ->where('id', '!=', $almacenId)
                    ->exists();
                if ($codigoExiste) {
                    return response()->json(['error' => 'Ya existe un almacén con ese código.'], 422);
                }

                $almacen->update([
                    'codigo'      => $validated['codigo'],
                    'nombre'      => $validated['nombre'],
                    'tipo'        => $validated['tipo'],
                    'responsable' => $validated['responsable'] ?? null,
                    'direccion'   => $validated['direccion'] ?? null,
                    'descripcion' => $validated['descripcion'] ?? null,
                    'activo'      => $validated['activo'] ?? true,
                ]);
                return response()->json(['success' => true, 'modo' => 'actualizado', 'nombre' => $almacen->nombre]);
            }

            // Crear nuevo
            $codigoExiste = Almacen::where('empresa_id', $empresa->id)
                ->where('codigo', $validated['codigo'])
                ->exists();
            if ($codigoExiste) {
                return response()->json(['error' => 'Ya existe un almacén con ese código.'], 422);
            }

            $almacen = Almacen::create([
                'empresa_id'  => $empresa->id,
                'codigo'      => $validated['codigo'],
                'nombre'      => $validated['nombre'],
                'tipo'        => $validated['tipo'],
                'responsable' => $validated['responsable'] ?? null,
                'direccion'   => $validated['direccion'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
                'activo'      => $validated['activo'] ?? true,
            ]);
            return response()->json(['success' => true, 'modo' => 'creado', 'nombre' => $almacen->nombre]);

        } catch (\Exception $e) {
            Log::error('Error guardando almacén móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el almacén.'], 500);
        }
    }

    public function eliminarAlmacen(Request $request, Almacen $almacen)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }

        if ($almacen->empresa_id !== $this->empresa()->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }

        try {
            $almacen->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando almacén móvil: ' . $e->getMessage());
            return response()->json(['error' => 'No se puede eliminar el almacén.'], 500);
        }
    }

    // ── Zonas de Almacén ──────────────────────────────────────────────────

    public function listZonas(Request $request, Almacen $almacen)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        if ($almacen->empresa_id !== $this->empresa()->id) {
            abort(403);
        }
        $zonas = ZonaAlmacen::where('almacen_id', $almacen->id)->orderBy('nombre')->get();
        return view('mobile.zonas', compact('almacen', 'zonas'));
    }

    public function showZonaForm(Request $request, Almacen $almacen, ?ZonaAlmacen $zona = null)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        if ($almacen->empresa_id !== $this->empresa()->id) {
            abort(403);
        }
        if ($zona && $zona->almacen_id !== $almacen->id) {
            abort(403);
        }
        return view('mobile.zona-form', compact('almacen', 'zona'));
    }

    public function guardarZona(Request $request, Almacen $almacen)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        if ($almacen->empresa_id !== $this->empresa()->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }

        $validated = $request->validate([
            'zona_id'     => ['nullable', 'integer'],
            'codigo'      => ['required', 'string', 'max:20'],
            'nombre'      => ['required', 'string', 'max:150'],
            'tipo'        => ['required', 'in:pasillo,estanteria,anaquel,area_refrigerada,camara_fria,area_cuarentena,area_despacho,area_recepcion,piso,otro'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo'      => ['boolean'],
        ]);

        try {
            $zonaId = $validated['zona_id'] ?? null;

            if ($zonaId) {
                $zona = ZonaAlmacen::where('almacen_id', $almacen->id)->findOrFail($zonaId);

                $existe = ZonaAlmacen::where('almacen_id', $almacen->id)
                    ->where('codigo', $validated['codigo'])
                    ->where('id', '!=', $zonaId)->exists();
                if ($existe) {
                    return response()->json(['error' => 'Ya existe una zona con ese código en este almacén.'], 422);
                }

                $zona->update([
                    'codigo'      => $validated['codigo'],
                    'nombre'      => $validated['nombre'],
                    'tipo'        => $validated['tipo'],
                    'descripcion' => $validated['descripcion'] ?? null,
                    'activo'      => $validated['activo'] ?? true,
                ]);
                return response()->json(['success' => true, 'modo' => 'actualizado', 'nombre' => $zona->nombre]);
            }

            $existe = ZonaAlmacen::where('almacen_id', $almacen->id)->where('codigo', $validated['codigo'])->exists();
            if ($existe) {
                return response()->json(['error' => 'Ya existe una zona con ese código en este almacén.'], 422);
            }

            $zona = ZonaAlmacen::create([
                'empresa_id'  => $almacen->empresa_id,
                'almacen_id'  => $almacen->id,
                'codigo'      => $validated['codigo'],
                'nombre'      => $validated['nombre'],
                'tipo'        => $validated['tipo'],
                'descripcion' => $validated['descripcion'] ?? null,
                'activo'      => $validated['activo'] ?? true,
            ]);
            return response()->json(['success' => true, 'modo' => 'creado', 'nombre' => $zona->nombre]);

        } catch (\Exception $e) {
            Log::error('Error guardando zona móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la zona.'], 500);
        }
    }

    public function eliminarZona(Request $request, Almacen $almacen, ZonaAlmacen $zona)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        if ($almacen->empresa_id !== $this->empresa()->id || $zona->almacen_id !== $almacen->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }
        try {
            $zona->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando zona móvil: ' . $e->getMessage());
            return response()->json(['error' => 'No se puede eliminar la zona.'], 500);
        }
    }

    /** JSON: zonas activas de un almacén (para cascading selects) */
    public function getZonasJson(Request $request, Almacen $almacen)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json([], 403);
        }
        if ($almacen->empresa_id !== $this->empresa()->id) {
            return response()->json([], 403);
        }
        $zonas = ZonaAlmacen::where('almacen_id', $almacen->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
        return response()->json($zonas);
    }

    /** JSON: ubicaciones de una zona (para cascading selects) */
    public function getUbicacionesJson(Request $request, ZonaAlmacen $zona)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json([], 403);
        }
        $empresa = $this->empresa();
        if ($zona->empresa_id !== $empresa->id) {
            return response()->json([], 403);
        }
        $ubicaciones = UbicacionAlmacen::where('zona_id', $zona->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_ubicacion']);
        return response()->json($ubicaciones);
    }

    // ── Inventario ────────────────────────────────────────────────────────

    public function showInventario(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa   = $this->empresa();
        $unidades  = MeasurementUnit::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        $almacenes = Almacen::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        return view('mobile.inventario', compact('empresa', 'unidades', 'almacenes'));
    }

    public function guardarInventario(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $validated = $request->validate([
            'nombre'               => ['required', 'string', 'max:255'],
            'type'                 => ['required', 'in:insumo,materia_prima,producto_terminado,activo_fijo,servicio'],
            'measurement_unit_id'  => ['nullable', 'integer', 'exists:measurement_units,id'],
            'descripcion'          => ['nullable', 'string', 'max:500'],
            'purchase_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_price'           => ['nullable', 'numeric', 'min:0'],
            'stock_actual'         => ['nullable', 'numeric', 'min:0'],
            'stock_minimo'         => ['nullable', 'numeric', 'min:0'],
            'ubicacion_almacen_id' => ['nullable', 'integer'],
            'foto'                 => ['nullable', 'image', 'max:5120'],
        ]);

        // Verificar que ubicacion_almacen_id pertenece a la empresa
        $ubicacionId = $validated['ubicacion_almacen_id'] ?? null;
        if ($ubicacionId) {
            $ubicExiste = UbicacionAlmacen::where('empresa_id', $empresa->id)->where('id', $ubicacionId)->exists();
            if (!$ubicExiste) {
                return response()->json(['error' => 'Ubicación no válida.'], 422);
            }
        }

        try {
            $fotoPath = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                $fotoPath = $request->file('foto')->store("inventario-fotos/{$empresa->id}", 'public');
            }

            $item = InventoryItem::create([
                'empresa_id'           => $empresa->id,
                'nombre'               => $validated['nombre'],
                'type'                 => $validated['type'],
                'measurement_unit_id'  => $validated['measurement_unit_id'] ?? null,
                'descripcion'          => $validated['descripcion'] ?? null,
                'purchase_price'       => $validated['purchase_price'] ?? null,
                'sale_price'           => $validated['sale_price'] ?? null,
                'stock_actual'         => $validated['stock_actual'] ?? 0,
                'stock_minimo'         => $validated['stock_minimo'] ?? 0,
                'ubicacion_almacen_id' => $ubicacionId,
                'foto_path'            => $fotoPath,
                'activo'               => true,
            ]);

            return response()->json([
                'success' => true,
                'codigo'  => $item->codigo,
                'nombre'  => $item->nombre,
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando ítem inventario móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el ítem.'], 500);
        }
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

    // ── Venta ─────────────────────────────────────────────────────────────────

    public function showVenta(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa   = $this->empresa();
        $customers = Customer::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        $items     = InventoryItem::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        return view('mobile.venta', compact('empresa', 'customers', 'items'));
    }

    public function guardarVenta(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();
        $validated = $request->validate([
            'fecha'           => ['required', 'date'],
            'customer_id'     => ['nullable', 'integer', 'exists:customers,id'],
            'tipo_venta'      => ['required', 'in:contado,credito'],
            'forma_pago'      => ['required', 'in:efectivo,transferencia,tarjeta_credito,credito'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'integer'],
            'items.*.descripcion'       => ['required', 'string'],
            'items.*.cantidad'          => ['required', 'numeric', 'min:0.0001'],
            'items.*.precio_unitario'   => ['required', 'numeric', 'min:0'],
            'items.*.aplica_iva'        => ['boolean'],
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'empresa_id'     => $empresa->id,
                'customer_id'    => $validated['customer_id'] ?? null,
                'fecha'          => $validated['fecha'],
                'tipo_venta'     => $validated['tipo_venta'],
                'forma_pago'     => $validated['forma_pago'],
                'tipo_operacion' => 'productos',
                'estado'         => 'borrador',
                'subtotal'       => 0,
                'iva'            => 0,
                'total'          => 0,
            ]);

            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id'           => $sale->id,
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'tipo_item'         => 'producto',
                    'descripcion'       => $item['descripcion'],
                    'cantidad'          => $item['cantidad'],
                    'precio_unitario'   => $item['precio_unitario'],
                    'aplica_iva'        => $item['aplica_iva'] ?? false,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'referencia' => $sale->fresh()->referencia]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando venta móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la venta.'], 500);
        }
    }

    // ── Orden de Producción ───────────────────────────────────────────────────

    public function showProduccion(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa       = $this->empresa();
        $presentaciones = ProductPresentation::whereHas('productDesign', fn($q) => $q->where('empresa_id', $empresa->id))
            ->where('activa', true)
            ->with('productDesign')
            ->get();
        return view('mobile.produccion', compact('empresa', 'presentaciones'));
    }

    public function guardarProduccion(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();
        $validated = $request->validate([
            'product_presentation_id' => ['required', 'integer'],
            'fecha'                   => ['required', 'date'],
            'cantidad_producida'      => ['required', 'numeric', 'min:0.0001'],
            'notas'                   => ['nullable', 'string', 'max:500'],
        ]);

        // Verificar que la presentación pertenece a la empresa
        $presentacion = ProductPresentation::whereHas('productDesign', fn($q) => $q->where('empresa_id', $empresa->id))
            ->find($validated['product_presentation_id']);
        if (!$presentacion) {
            return response()->json(['error' => 'Presentación no válida.'], 422);
        }

        try {
            $orden = ProductionOrder::create([
                'empresa_id'              => $empresa->id,
                'product_presentation_id' => $validated['product_presentation_id'],
                'fecha'                   => $validated['fecha'],
                'cantidad_producida'      => $validated['cantidad_producida'],
                'notas'                   => $validated['notas'] ?? null,
                'estado'                  => 'borrador',
                'costo_total'             => 0,
            ]);
            return response()->json(['success' => true, 'referencia' => $orden->referencia]);
        } catch (\Exception $e) {
            Log::error('Error guardando orden producción móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la orden.'], 500);
        }
    }
}
