<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Bank;
use App\Models\CmsAbout;
use App\Models\LogisticsBodega;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\CmsTeamMember;
use App\Models\CmsTestimonial;
use App\Models\Debt;
use App\Models\InventoryItem;
use App\Models\ItemPresentation;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use App\Models\ProductFormulaLine;
use App\Models\ProductPresentation;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreCategory;
use App\Models\StoreCustomer;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use App\Models\Supplier;
use App\Models\Customer;
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

    // ── Posiciones (UbicacionAlmacen) ─────────────────────────────────────

    public function listUbicaciones(Request $request, Almacen $almacen, ZonaAlmacen $zona)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        if ($almacen->empresa_id !== $this->empresa()->id || $zona->almacen_id !== $almacen->id) {
            abort(403);
        }
        $posiciones = UbicacionAlmacen::where('zona_id', $zona->id)->orderBy('nombre')->get();
        return view('mobile.posiciones', compact('almacen', 'zona', 'posiciones'));
    }

    public function showUbicacionForm(Request $request, Almacen $almacen, ZonaAlmacen $zona, ?UbicacionAlmacen $ubicacion = null)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        if ($almacen->empresa_id !== $this->empresa()->id || $zona->almacen_id !== $almacen->id) {
            abort(403);
        }
        if ($ubicacion && $ubicacion->zona_id !== $zona->id) {
            abort(403);
        }
        return view('mobile.posicion-form', compact('almacen', 'zona', 'ubicacion'));
    }

    public function guardarUbicacion(Request $request, Almacen $almacen, ZonaAlmacen $zona)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        if ($almacen->empresa_id !== $this->empresa()->id || $zona->almacen_id !== $almacen->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }

        $validated = $request->validate([
            'ubicacion_id'     => ['nullable', 'integer'],
            'codigo_ubicacion' => ['required', 'string', 'max:20'],
            'nombre'           => ['required', 'string', 'max:150'],
            'capacidad_maxima' => ['nullable', 'numeric', 'min:0'],
            'unidad_capacidad' => ['nullable', 'string', 'max:50'],
            'activo'           => ['boolean'],
        ]);

        try {
            $ubicacionId = $validated['ubicacion_id'] ?? null;

            if ($ubicacionId) {
                $ubicacion = UbicacionAlmacen::where('zona_id', $zona->id)->findOrFail($ubicacionId);

                $existe = UbicacionAlmacen::where('zona_id', $zona->id)
                    ->where('codigo_ubicacion', $validated['codigo_ubicacion'])
                    ->where('id', '!=', $ubicacionId)->exists();
                if ($existe) {
                    return response()->json(['error' => 'Ya existe una posición con ese código en esta zona.'], 422);
                }

                $ubicacion->update([
                    'codigo_ubicacion' => $validated['codigo_ubicacion'],
                    'nombre'           => $validated['nombre'],
                    'capacidad_maxima' => $validated['capacidad_maxima'] ?? null,
                    'unidad_capacidad' => $validated['unidad_capacidad'] ?? null,
                    'activo'           => $validated['activo'] ?? true,
                ]);
                return response()->json(['success' => true, 'modo' => 'actualizado', 'nombre' => $ubicacion->nombre]);
            }

            $existe = UbicacionAlmacen::where('zona_id', $zona->id)
                ->where('codigo_ubicacion', $validated['codigo_ubicacion'])->exists();
            if ($existe) {
                return response()->json(['error' => 'Ya existe una posición con ese código en esta zona.'], 422);
            }

            $ubicacion = UbicacionAlmacen::create([
                'empresa_id'       => $almacen->empresa_id,
                'almacen_id'       => $almacen->id,
                'zona_id'          => $zona->id,
                'codigo_ubicacion' => $validated['codigo_ubicacion'],
                'nombre'           => $validated['nombre'],
                'capacidad_maxima' => $validated['capacidad_maxima'] ?? null,
                'unidad_capacidad' => $validated['unidad_capacidad'] ?? null,
                'activo'           => $validated['activo'] ?? true,
            ]);
            return response()->json(['success' => true, 'modo' => 'creado', 'nombre' => $ubicacion->nombre]);

        } catch (\Exception $e) {
            Log::error('Error guardando posición móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la posición.'], 500);
        }
    }

    public function eliminarUbicacion(Request $request, Almacen $almacen, ZonaAlmacen $zona, UbicacionAlmacen $ubicacion)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        if ($almacen->empresa_id !== $this->empresa()->id || $zona->almacen_id !== $almacen->id || $ubicacion->zona_id !== $zona->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }
        try {
            $ubicacion->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando posición móvil: ' . $e->getMessage());
            return response()->json(['error' => 'No se puede eliminar la posición.'], 500);
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
        $empresa       = $this->empresa();
        $unidades      = MeasurementUnit::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        $almacenes     = Almacen::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        $presentaciones = ItemPresentation::where('empresa_id', $empresa->id)->where('activo', true)
            ->with('measurementUnit')
            ->orderBy('nombre')
            ->get();
        return view('mobile.inventario', compact('empresa', 'unidades', 'almacenes', 'presentaciones'));
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
            'presentation_id'      => ['nullable', 'integer', 'exists:item_presentations,id'],
            'new_pres_nombre'      => ['nullable', 'string', 'max:150'],
            'new_pres_unit'        => ['nullable', 'integer', 'exists:measurement_units,id'],
            'new_pres_capacidad'   => ['nullable', 'numeric', 'min:0.0001'],
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

            // Presentación: usar existente o crear nueva
            $presentacionId = $validated['presentation_id'] ?? null;
            if (!$presentacionId && !empty($validated['new_pres_nombre'])) {
                $nuevaPres = ItemPresentation::create([
                    'empresa_id'          => $empresa->id,
                    'nombre'              => $validated['new_pres_nombre'],
                    'measurement_unit_id' => $validated['new_pres_unit'] ?? null,
                    'capacidad'           => $validated['new_pres_capacidad'] ?? null,
                    'activo'              => true,
                ]);
                $presentacionId = $nuevaPres->id;
            }

            $item = InventoryItem::create([
                'empresa_id'           => $empresa->id,
                'nombre'               => $validated['nombre'],
                'type'                 => $validated['type'],
                'measurement_unit_id'  => $validated['measurement_unit_id'] ?? null,
                'descripcion'          => $validated['descripcion'] ?? null,
                'purchase_price'       => $validated['purchase_price'] ?? 0,
                'sale_price'           => $validated['sale_price'] ?? null,
                'stock_actual'         => $validated['stock_actual'] ?? 0,
                'stock_minimo'         => $validated['stock_minimo'] ?? 0,
                'ubicacion_almacen_id' => $ubicacionId,
                'presentation_id'      => $presentacionId,
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
                $subtotalItem = round((float) $item['cantidad'] * (float) $item['precio_unitario'], 4);
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

    // ── Deudas ────────────────────────────────────────────────────────────────

    public function showDeuda(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa = $this->empresa();
        $bancos  = Bank::activos()->orderBy('nombre')->get();
        return view('mobile.deuda', compact('empresa', 'bancos'));
    }

    public function guardarDeuda(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $validated = $request->validate([
            'tipo'                   => ['required', 'in:prestamo_bancario,tarjeta_credito,prestamo_personal,prestamo_empresarial,otro'],
            'acreedor'               => ['required', 'string', 'max:255'],
            'descripcion'            => ['required', 'string', 'max:500'],
            'monto_original'         => ['required', 'numeric', 'min:0.01'],
            'cuota_mensual'          => ['nullable', 'numeric', 'min:0'],
            'tasa_interes'           => ['required', 'numeric', 'min:0'],
            'seguro_desgravamen_anual' => ['nullable', 'numeric', 'min:0'],
            'sistema_amortizacion'   => ['required', 'in:frances,aleman,americano'],
            'fecha_inicio'           => ['required', 'date'],
            'plazo_meses'            => ['required', 'integer', 'min:1'],
            'bank_id'                => ['nullable', 'integer', 'exists:banks,id'],
            'notas'                  => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $plazo         = (int) $validated['plazo_meses'];
            $cuotaMensual  = isset($validated['cuota_mensual']) && (float) $validated['cuota_mensual'] > 0
                             ? (float) $validated['cuota_mensual']
                             : null;
            Debt::create([
                'empresa_id'               => $empresa->id,
                'tipo'                     => $validated['tipo'],
                'acreedor'                 => $validated['acreedor'],
                'descripcion'              => $validated['descripcion'],
                'monto_original'           => $validated['monto_original'],
                'saldo_pendiente'          => $validated['monto_original'],
                'cuota_mensual'            => $cuotaMensual,
                'tasa_interes'             => $validated['tasa_interes'],
                'seguro_desgravamen_anual' => $validated['seguro_desgravamen_anual'] ?? 0,
                'sistema_amortizacion'     => $validated['sistema_amortizacion'],
                'fecha_inicio'             => $validated['fecha_inicio'],
                'plazo_meses'              => $plazo,
                'numero_cuotas'            => $plazo,
                'fecha_vencimiento'        => \Carbon\Carbon::parse($validated['fecha_inicio'])->addMonths($plazo)->toDateString(),
                'clasificacion'            => $plazo <= 12 ? 'corriente' : 'no_corriente',
                'bank_id'                  => $validated['bank_id'] ?? null,
                'notas'                    => $validated['notas'] ?? null,
                'estado'                   => 'borrador',
            ]);

            return response()->json(['success' => true, 'message' => 'Deuda registrada en borrador. Un administrador debe activarla.']);
        } catch (\Exception $e) {
            Log::error('Error guardando deuda móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la deuda.'], 500);
        }
    }

    public function listValidarDeudas(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        if (!$this->esAdmin()) {
            return view('mobile.forbidden', ['mensaje' => 'Solo los administradores de empresa pueden validar deudas.']);
        }
        $empresa = $this->empresa();
        $deudas  = Debt::where('empresa_id', $empresa->id)
            ->where('estado', 'borrador')
            ->with('bank')
            ->latest()
            ->get();
        return view('mobile.validar-deudas', compact('empresa', 'deudas'));
    }

    public function activarDeuda(Request $request, Debt $debt)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        if (!$this->esAdmin()) {
            return response()->json(['error' => 'Solo los administradores pueden activar deudas.'], 403);
        }
        if ($debt->empresa_id !== $this->empresa()->id) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }
        if ($debt->estado !== 'borrador') {
            return response()->json(['error' => 'Solo se pueden activar deudas en estado borrador.'], 422);
        }

        try {
            $debt->update(['estado' => 'activa']);
            return response()->json(['success' => true, 'message' => 'Deuda ' . $debt->numero . ' activada. Asiento contable y tabla de amortización generados.']);
        } catch (\Exception $e) {
            Log::error('Error activando deuda móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al activar la deuda: ' . $e->getMessage()], 500);
        }
    }

    // ── Diseño de Producto ────────────────────────────────────────────────────

    public function showDisenoProducto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        }
        $empresa  = $this->empresa();
        $unidades = MeasurementUnit::where('empresa_id', $empresa->id)->where('activo', true)->orderBy('nombre')->get();
        $insumos  = InventoryItem::where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->whereIn('type', ['insumo', 'materia_prima'])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
        return view('mobile.diseno-producto', compact('empresa', 'unidades', 'insumos'));
    }

    public function guardarDisenoProducto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $validated = $request->validate([
            'nombre'                       => ['required', 'string', 'max:150'],
            'propuesta_valor'              => ['nullable', 'string'],
            'notas_estrategicas'           => ['nullable', 'string', 'max:1000'],
            'tiene_multiples_presentaciones' => ['boolean'],
            'presentaciones'               => ['required', 'array', 'min:1'],
            'presentaciones.*.nombre'      => ['nullable', 'string', 'max:150'],
            'presentaciones.*.cantidad_minima_produccion' => ['required', 'numeric', 'min:0.0001'],
            'presentaciones.*.measurement_unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'],
            'presentaciones.*.formula'     => ['nullable', 'array'],
            'presentaciones.*.formula.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'presentaciones.*.formula.*.cantidad'          => ['required_with:presentaciones.*.formula.*.inventory_item_id', 'numeric', 'min:0.0001'],
            'presentaciones.*.formula.*.measurement_unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'],
            'presentaciones.*.formula.*.notas'             => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            $design = ProductDesign::create([
                'empresa_id'                    => $empresa->id,
                'nombre'                        => $validated['nombre'],
                'propuesta_valor'               => $validated['propuesta_valor'] ?? null,
                'notas_estrategicas'            => $validated['notas_estrategicas'] ?? null,
                'tiene_multiples_presentaciones' => $validated['tiene_multiples_presentaciones'] ?? false,
                'activo'                        => true,
            ]);

            foreach ($validated['presentaciones'] as $presData) {
                $pres = ProductPresentation::create([
                    'product_design_id'          => $design->id,
                    'nombre'                     => $presData['nombre'] ?? null,
                    'cantidad_minima_produccion' => $presData['cantidad_minima_produccion'],
                    'measurement_unit_id'        => $presData['measurement_unit_id'] ?? null,
                    'activa'                     => true,
                ]);

                foreach ($presData['formula'] ?? [] as $linea) {
                    if (empty($linea['inventory_item_id'])) continue;
                    ProductFormulaLine::create([
                        'presentation_id'    => $pres->id,
                        'inventory_item_id'  => $linea['inventory_item_id'],
                        'cantidad'           => $linea['cantidad'],
                        'measurement_unit_id' => $linea['measurement_unit_id'] ?? null,
                        'notas'              => $linea['notas'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'nombre' => $design->nombre]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando diseño de producto móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el diseño.'], 500);
        }
    }

    // ── Listas de consulta ────────────────────────────────────────────────────

    public function listInventario(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $items = InventoryItem::where('empresa_id', $empresa->id)->with('measurementUnit')->orderByDesc('updated_at')->paginate(25);
        return view('mobile.inventario-lista', compact('empresa', 'items'));
    }

    public function listVentas(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $ventas = Sale::where('empresa_id', $empresa->id)->with('customer')->orderByDesc('fecha')->paginate(25);
        return view('mobile.ventas-lista', compact('empresa', 'ventas'));
    }

    public function listCompras(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $compras = Purchase::where('empresa_id', $empresa->id)->with('supplier')->orderByDesc('date')->paginate(25);
        return view('mobile.compras-lista', compact('empresa', 'compras'));
    }

    public function listDeudas(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $deudas = Debt::where('empresa_id', $empresa->id)->with('bank')->orderByDesc('created_at')->paginate(25);
        return view('mobile.deudas-lista', compact('empresa', 'deudas'));
    }

    public function listProduccion(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $ordenes = ProductionOrder::where('empresa_id', $empresa->id)->with('productPresentation.productDesign')->orderByDesc('fecha')->paginate(25);
        return view('mobile.produccion-lista', compact('empresa', 'ordenes'));
    }

    public function listDisenosProducto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $disenos = ProductDesign::where('empresa_id', $empresa->id)->with('presentations')->orderByDesc('created_at')->paginate(25);
        return view('mobile.disenos-producto-lista', compact('empresa', 'disenos'));
    }

    // ── Ecommerce ─────────────────────────────────────────────────────────────

    public function showEcommerce(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        return view('mobile.ecommerce', compact('empresa'));
    }

    // ── CMS Singletons ────────────────────────────────────────────────────────

    public function showCmsHero(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $hero = CmsHero::where('empresa_id', $empresa->id)->first();
        return view('mobile.cms-hero', compact('empresa', 'hero'));
    }

    public function guardarCmsHero(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'titulo'      => ['required', 'string', 'max:200'],
            'subtitulo'   => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'cta_texto'   => ['nullable', 'string', 'max:100'],
            'cta_url'     => ['nullable', 'string', 'max:300'],
            'activo'      => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        CmsHero::updateOrCreate(['empresa_id' => $empresa->id], array_merge($data, ['empresa_id' => $empresa->id]));
        return response()->json(['success' => true, 'message' => 'Hero actualizado.']);
    }

    public function showCmsAbout(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $about = CmsAbout::where('empresa_id', $empresa->id)->first();
        return view('mobile.cms-about', compact('empresa', 'about'));
    }

    public function guardarCmsAbout(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'titulo'      => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'activo'      => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        CmsAbout::updateOrCreate(['empresa_id' => $empresa->id], array_merge($data, ['empresa_id' => $empresa->id]));
        return response()->json(['success' => true, 'message' => 'Sección Nosotros actualizada.']);
    }

    public function showCmsContacto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $contacto = CmsContact::where('empresa_id', $empresa->id)->first();
        return view('mobile.cms-contacto', compact('empresa', 'contacto'));
    }

    public function guardarCmsContacto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'direccion'  => ['nullable', 'string', 'max:300'],
            'telefono'   => ['nullable', 'string', 'max:50'],
            'email'      => ['nullable', 'email', 'max:150'],
            'whatsapp'   => ['nullable', 'string', 'max:50'],
            'facebook'   => ['nullable', 'string', 'max:300'],
            'instagram'  => ['nullable', 'string', 'max:300'],
            'linkedin'   => ['nullable', 'string', 'max:300'],
            'youtube'    => ['nullable', 'string', 'max:300'],
            'tiktok'     => ['nullable', 'string', 'max:300'],
            'activo'     => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        CmsContact::updateOrCreate(['empresa_id' => $empresa->id], array_merge($data, ['empresa_id' => $empresa->id]));
        return response()->json(['success' => true, 'message' => 'Contacto actualizado.']);
    }

    // ── CMS Servicios ─────────────────────────────────────────────────────────

    public function listCmsServicios(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $servicios = CmsService::where('empresa_id', $empresa->id)->orderBy('sort_order')->get();
        return view('mobile.cms-servicios', compact('empresa', 'servicios'));
    }

    public function showCmsServicioForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $servicio = $id ? CmsService::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-servicio-form', compact('empresa', 'servicio'));
    }

    public function guardarCmsServicio(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'          => ['nullable', 'integer'],
            'titulo'      => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer'],
            'activo'      => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $s = CmsService::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $s->update($data);
        } else {
            unset($data['id']);
            CmsService::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Servicio guardado.']);
    }

    public function eliminarCmsServicio(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsService::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── CMS Equipo ────────────────────────────────────────────────────────────

    public function listCmsEquipo(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $miembros = CmsTeamMember::where('empresa_id', $empresa->id)->orderBy('sort_order')->get();
        return view('mobile.cms-equipo', compact('empresa', 'miembros'));
    }

    public function showCmsEquipoForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $miembro = $id ? CmsTeamMember::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-equipo-form', compact('empresa', 'miembro'));
    }

    public function guardarCmsEquipo(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'         => ['nullable', 'integer'],
            'nombre'     => ['required', 'string', 'max:150'],
            'cargo'      => ['nullable', 'string', 'max:150'],
            'bio'        => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'activo'     => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $m = CmsTeamMember::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $m->update($data);
        } else {
            unset($data['id']);
            CmsTeamMember::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Miembro guardado.']);
    }

    public function eliminarCmsEquipo(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsTeamMember::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── CMS Testimonios ───────────────────────────────────────────────────────

    public function listCmsTestimonios(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $testimonios = CmsTestimonial::where('empresa_id', $empresa->id)->orderBy('sort_order')->get();
        return view('mobile.cms-testimonios', compact('empresa', 'testimonios'));
    }

    public function showCmsTestimonioForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $testimonio = $id ? CmsTestimonial::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-testimonio-form', compact('empresa', 'testimonio'));
    }

    public function guardarCmsTestimonio(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'            => ['nullable', 'integer'],
            'autor_nombre'  => ['required', 'string', 'max:150'],
            'autor_cargo'   => ['nullable', 'string', 'max:150'],
            'autor_empresa' => ['nullable', 'string', 'max:150'],
            'contenido'     => ['required', 'string'],
            'estrellas'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort_order'    => ['nullable', 'integer'],
            'activo'        => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $t = CmsTestimonial::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $t->update($data);
        } else {
            unset($data['id']);
            CmsTestimonial::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Testimonio guardado.']);
    }

    public function eliminarCmsTestimonio(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsTestimonial::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── CMS FAQs ──────────────────────────────────────────────────────────────

    public function listCmsFaqs(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $faqs = CmsFaq::where('empresa_id', $empresa->id)->orderBy('sort_order')->get();
        return view('mobile.cms-faqs', compact('empresa', 'faqs'));
    }

    public function showCmsFaqForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $faq = $id ? CmsFaq::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-faq-form', compact('empresa', 'faq'));
    }

    public function guardarCmsFaq(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'         => ['nullable', 'integer'],
            'pregunta'   => ['required', 'string', 'max:300'],
            'respuesta'  => ['required', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'activo'     => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $f = CmsFaq::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $f->update($data);
        } else {
            unset($data['id']);
            CmsFaq::create($data);
        }
        return response()->json(['success' => true, 'message' => 'FAQ guardada.']);
    }

    public function eliminarCmsFaq(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsFaq::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── CMS Posts ─────────────────────────────────────────────────────────────

    public function listCmsPosts(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $posts = CmsPost::where('empresa_id', $empresa->id)->orderByDesc('created_at')->paginate(20);
        return view('mobile.cms-posts', compact('empresa', 'posts'));
    }

    public function showCmsPostForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $post = $id ? CmsPost::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-post-form', compact('empresa', 'post'));
    }

    public function guardarCmsPost(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'        => ['nullable', 'integer'],
            'titulo'    => ['required', 'string', 'max:250'],
            'contenido' => ['nullable', 'string'],
            'activo'    => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $p = CmsPost::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $p->update($data);
        } else {
            unset($data['id']);
            CmsPost::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Post guardado.']);
    }

    public function eliminarCmsPost(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsPost::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── CMS Logos de Clientes ─────────────────────────────────────────────────

    public function listCmsLogos(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $logos = CmsClientLogo::where('empresa_id', $empresa->id)->orderBy('sort_order')->get();
        return view('mobile.cms-logos', compact('empresa', 'logos'));
    }

    public function showCmsLogoForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $logo = $id ? CmsClientLogo::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.cms-logo-form', compact('empresa', 'logo'));
    }

    public function guardarCmsLogo(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'         => ['nullable', 'integer'],
            'nombre'     => ['required', 'string', 'max:150'],
            'url'        => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer'],
            'activo'     => ['nullable'],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $l = CmsClientLogo::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $l->update($data);
        } else {
            unset($data['id']);
            CmsClientLogo::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Logo guardado.']);
    }

    public function eliminarCmsLogo(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        CmsClientLogo::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Tienda — Productos ────────────────────────────────────────────────────

    public function listTiendaProductos(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $productos = StoreProduct::where('empresa_id', $empresa->id)->with('storeCategory')->orderBy('orden')->paginate(25);
        return view('mobile.tienda-productos', compact('empresa', 'productos'));
    }

    public function showTiendaProductoForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $producto = $id ? StoreProduct::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        $categorias = StoreCategory::where('empresa_id', $empresa->id)->orderBy('nombre')->get();
        $presentaciones = ProductPresentation::whereHas('productDesign', fn($q) => $q->where('empresa_id', $empresa->id))->with('productDesign')->get();
        return view('mobile.tienda-producto-form', compact('empresa', 'producto', 'categorias', 'presentaciones'));
    }

    public function guardarTiendaProducto(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'                    => ['nullable', 'integer'],
            'nombre'                => ['required', 'string', 'max:200'],
            'descripcion'           => ['nullable', 'string'],
            'precio_venta'          => ['required', 'numeric', 'min:0'],
            'precio_distribuidor'   => ['nullable', 'numeric', 'min:0'],
            'store_category_id'     => ['nullable', 'integer'],
            'product_presentation_id' => ['nullable', 'integer'],
            'publicado'             => ['nullable'],
            'destacado'             => ['nullable'],
            'orden'                 => ['nullable', 'integer'],
        ]);
        $data['publicado'] = $request->boolean('publicado');
        $data['destacado'] = $request->boolean('destacado');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $p = StoreProduct::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $p->update($data);
        } else {
            unset($data['id']);
            StoreProduct::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Producto guardado.']);
    }

    public function eliminarTiendaProducto(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        StoreProduct::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Tienda — Pedidos ──────────────────────────────────────────────────────

    public function listTiendaPedidos(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $pedidos = StoreOrder::where('empresa_id', $empresa->id)->with('customer')->orderByDesc('created_at')->paginate(25);
        return view('mobile.tienda-pedidos', compact('empresa', 'pedidos'));
    }

    public function actualizarEstadoPedido(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        if (!$this->esAdmin()) return response()->json(['error' => 'Solo administradores.'], 403);
        $empresa = $this->empresa();
        $pedido = StoreOrder::where('empresa_id', $empresa->id)->findOrFail($id);
        $data = $request->validate(['estado' => ['required', 'in:pendiente,enviado,entregado,cancelado']]);
        $pedido->update(['estado' => $data['estado']]);
        return response()->json(['success' => true, 'message' => 'Estado actualizado.']);
    }

    // ── Tienda — Categorías ───────────────────────────────────────────────────

    public function listTiendaCategorias(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $categorias = StoreCategory::where('empresa_id', $empresa->id)->orderBy('orden')->get();
        return view('mobile.tienda-categorias', compact('empresa', 'categorias'));
    }

    public function showTiendaCategoriaForm(Request $request, $id = null)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $categoria = $id ? StoreCategory::where('empresa_id', $empresa->id)->findOrFail($id) : null;
        return view('mobile.tienda-categoria-form', compact('empresa', 'categoria'));
    }

    public function guardarTiendaCategoria(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        $empresa = $this->empresa();
        $data = $request->validate([
            'id'          => ['nullable', 'integer'],
            'nombre'      => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'publicado'   => ['nullable'],
            'orden'       => ['nullable', 'integer'],
        ]);
        $data['publicado'] = $request->boolean('publicado');
        $data['empresa_id'] = $empresa->id;
        if (!empty($data['id'])) {
            $c = StoreCategory::where('empresa_id', $empresa->id)->findOrFail($data['id']);
            unset($data['id']);
            $c->update($data);
        } else {
            unset($data['id']);
            StoreCategory::create($data);
        }
        return response()->json(['success' => true, 'message' => 'Categoría guardada.']);
    }

    public function eliminarTiendaCategoria(Request $request, $id)
    {
        if (!$this->tieneAccesoEnterprise()) return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        StoreCategory::where('empresa_id', $this->empresa()->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Tienda — Clientes ─────────────────────────────────────────────────────

    public function listTiendaClientes(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $clientes = StoreCustomer::where('empresa_id', $empresa->id)->orderByDesc('created_at')->paginate(25);
        return view('mobile.tienda-clientes', compact('empresa', 'clientes'));
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

    // ── Logística ─────────────────────────────────────────────────────────────

    public function showLogistica(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa       = $this->empresa();
        $totalCargas   = LogisticsPackage::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();
        $totalEmbarques = LogisticsShipment::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();
        return view('mobile.logistica', compact('empresa', 'totalCargas', 'totalEmbarques'));
    }

    public function showCargaNueva(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa   = $this->empresa();
        $bodegas   = LogisticsBodega::withoutGlobalScopes()->where('empresa_id', $empresa->id)->orderBy('nombre')->get();
        $clientes  = StoreCustomer::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->where('is_super_admin', false)
            ->orderBy('nombre')
            ->get();
        return view('mobile.logistica-carga', compact('empresa', 'bodegas', 'clientes'));
    }

    public function guardarCarga(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $request->validate([
            'descripcion'       => ['required', 'string', 'max:500'],
            'numero_tracking'   => ['nullable', 'string', 'max:100'],
            'referencia'        => ['nullable', 'string', 'max:100'],
            'bodega_id'         => ['required', 'integer'],
            'store_customer_id' => ['nullable'],
            'peso_kg'           => ['nullable', 'numeric', 'min:0'],
            'valor_declarado'   => ['nullable', 'numeric', 'min:0'],
        ]);

        // Validar que la bodega pertenece a la empresa
        $bodega = LogisticsBodega::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->find($request->bodega_id);
        if (!$bodega) {
            return response()->json(['error' => 'Bodega no válida.'], 422);
        }

        // Resolver store_customer_id
        $storeCustomerId = null;
        $rawCustomer = $request->store_customer_id;
        if ($rawCustomer) {
            $scId = (int) str_replace('sc_', '', (string) $rawCustomer);
            if (StoreCustomer::withoutGlobalScopes()->where('empresa_id', $empresa->id)->where('id', $scId)->exists()) {
                $storeCustomerId = $scId;
            }
        }

        try {
            $package = LogisticsPackage::create([
                'empresa_id'       => $empresa->id,
                'bodega_id'        => $request->bodega_id,
                'store_customer_id'=> $storeCustomerId,
                'descripcion'      => $request->descripcion,
                'numero_tracking'  => $request->numero_tracking ?: null,
                'referencia'       => $request->referencia ?: null,
                'peso_kg'          => $request->peso_kg ?: null,
                'valor_declarado'  => $request->valor_declarado ?: null,
                'moneda'           => 'USD',
                'estado'           => 'registrado',
                'fecha_recepcion_bodega' => now()->toDateString(),
            ]);

            return response()->json([
                'success'  => true,
                'tracking' => $package->numero_tracking ?? 'PKG-' . $package->id,
                'id'       => $package->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando carga logística móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al registrar la carga.'], 500);
        }
    }

    public function listCargas(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $cargas  = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->with('storeCustomer', 'bodega')
            ->orderByDesc('created_at')
            ->paginate(30);
        return view('mobile.logistica-cargas', compact('empresa', 'cargas'));
    }

    public function actualizarEstadoCarga(Request $request, int $packageId)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $package = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->find($packageId);

        if (!$package) {
            return response()->json(['error' => 'Carga no encontrada.'], 404);
        }

        $request->validate([
            'estado'            => ['required', 'string', 'in:' . implode(',', array_keys(LogisticsPackage::ESTADOS))],
            'estado_secundario' => ['nullable', 'string'],
        ]);

        $estadoAnterior = $package->estado;
        $package->update([
            'estado'            => $request->estado,
            'estado_secundario' => $request->estado_secundario ?: null,
        ]);

        // Notificar al cliente si tiene correo y el estado cambió
        if ($estadoAnterior !== $request->estado && $package->store_customer_id) {
            try {
                $customer = StoreCustomer::withoutGlobalScopes()->find($package->store_customer_id);
                $emp      = \App\Models\Empresa::find($package->empresa_id);
                if ($customer && $emp && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                    $solicitarPago = $request->estado === 'finalizado_aduana';
                    $mail = new \App\Mail\LogisticsPackageStatusMail($package, $customer, $emp, $solicitarPago);
                    \Resend\Laravel\Facades\Resend::emails()->send([
                        'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                        'to'      => [$customer->email],
                        'subject' => $mail->envelope()->subject,
                        'html'    => $mail->buildHtml(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo notificar al cliente: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'estado' => $request->estado]);
    }

    public function showEmbarqueNuevo(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa = $this->empresa();
        $bodegas = LogisticsBodega::withoutGlobalScopes()->where('empresa_id', $empresa->id)->orderBy('nombre')->get();
        // Paquetes sin embarque asignado (registrados o en_aduana)
        $paquetesSinEmbarque = LogisticsPackage::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereDoesntHave('shipments')
            ->whereIn('estado', ['registrado', 'embarque_solicitado'])
            ->with('storeCustomer')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
        return view('mobile.logistica-embarque', compact('empresa', 'bodegas', 'paquetesSinEmbarque'));
    }

    public function guardarEmbarque(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) {
            return response()->json(['error' => 'Requiere plan Enterprise.'], 403);
        }
        $empresa = $this->empresa();

        $request->validate([
            'tipo'                 => ['required', 'in:individual,consolidado,fraccionado'],
            'bodega_id'            => ['required', 'integer'],
            'fecha_embarque'       => ['nullable', 'date'],
            'fecha_llegada_ecuador'=> ['nullable', 'date'],
            'numero_guia_aerea'    => ['nullable', 'string', 'max:100'],
            'observaciones'        => ['nullable', 'string', 'max:500'],
            'package_ids'          => ['nullable', 'array'],
            'package_ids.*'        => ['integer'],
        ]);

        $bodega = LogisticsBodega::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->find($request->bodega_id);
        if (!$bodega) {
            return response()->json(['error' => 'Bodega no válida.'], 422);
        }

        try {
            $shipment = LogisticsShipment::create([
                'empresa_id'            => $empresa->id,
                'bodega_id'             => $request->bodega_id,
                'numero_embarque'       => LogisticsShipment::generarNumero($empresa->id),
                'tipo'                  => $request->tipo,
                'estado'                => 'embarque_solicitado',
                'fecha_embarque'        => $request->fecha_embarque ?: null,
                'fecha_llegada_ecuador' => $request->fecha_llegada_ecuador ?: null,
                'numero_guia_aerea'     => $request->numero_guia_aerea ?: null,
                'observaciones'         => $request->observaciones ?: null,
            ]);

            // Asignar paquetes seleccionados
            if (!empty($request->package_ids)) {
                $validIds = LogisticsPackage::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->whereIn('id', $request->package_ids)
                    ->pluck('id');
                if ($validIds->isNotEmpty()) {
                    $shipment->packages()->attach($validIds);
                    // Actualizar estado de los paquetes a embarque_solicitado
                    LogisticsPackage::withoutGlobalScopes()
                        ->whereIn('id', $validIds)
                        ->update(['estado' => 'embarque_solicitado']);
                }
            }

            return response()->json([
                'success'  => true,
                'numero'   => $shipment->numero_embarque,
                'paquetes' => $shipment->packages()->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando embarque móvil: ' . $e->getMessage());
            return response()->json(['error' => 'Error al registrar el embarque.'], 500);
        }
    }

    public function listEmbarques(Request $request)
    {
        if (!$this->tieneAccesoEnterprise()) return $this->denegarAcceso($request, 'Requiere plan Enterprise.');
        $empresa   = $this->empresa();
        $embarques = LogisticsShipment::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->withCount('packages')
            ->orderByDesc('created_at')
            ->paginate(25);
        return view('mobile.logistica-embarques', compact('empresa', 'embarques'));
    }
}
