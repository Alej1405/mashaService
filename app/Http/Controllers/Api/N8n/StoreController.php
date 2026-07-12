<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\Scopes\EmpresaScope;
use App\Models\StoreCoupon;
use App\Models\StoreProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de tienda desde n8n/Telegram. Como CmsController, ancla SIEMPRE la
 * empresa de la sesión con withoutGlobalScope (la API n8n no tiene tenant/auth).
 *
 * Nota: crear productos desde cero requiere inventory_item_id (NOT NULL), por eso
 * aquí productos = listar/actualizar/eliminar sobre los existentes. La creación de
 * cupones (promos) sí es completa.
 */
class StoreController extends Controller
{
    /** Resumen para el menú del módulo. */
    public function resumen(Request $request): JsonResponse
    {
        $id = $this->empresaId($request);

        return response()->json([
            'ok' => true,
            'conteos' => [
                'productos' => $this->scope(StoreProduct::query(), $id)->count(),
                'cupones' => $this->scope(StoreCoupon::query(), $id)->count(),
            ],
        ]);
    }

    // ---- Cupones / Promociones ---------------------------------------------

    public function couponsIndex(Request $request): JsonResponse
    {
        $items = $this->scope(StoreCoupon::query(), $this->empresaId($request))
            ->latest('id')->limit(15)
            ->get(['id', 'codigo', 'tipo', 'valor', 'usos_actuales', 'maximo_usos', 'activo']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function couponsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'tipo' => ['required', 'in:porcentaje,monto_fijo'],
            'valor' => ['required', 'numeric', 'min:0'],
            'maximo_usos' => ['nullable', 'integer', 'min:1'],
            'fecha_fin' => ['nullable', 'date'],
        ]);

        $empresaId = $this->empresaId($request);
        $codigo = mb_strtoupper(trim($data['codigo']));

        if ($this->scope(StoreCoupon::query(), $empresaId)->where('codigo', $codigo)->exists()) {
            return response()->json([
                'ok' => false,
                'error' => 'codigo_duplicado',
                'mensaje' => 'Ya existe un cupón con ese código en esta empresa.',
            ], 422);
        }

        $coupon = new StoreCoupon();
        $coupon->empresa_id = $empresaId;
        $coupon->codigo = $codigo;
        $coupon->tipo = $data['tipo'];
        $coupon->valor = $data['valor'];
        $coupon->maximo_usos = $data['maximo_usos'] ?? null;
        $coupon->usos_actuales = 0;
        $coupon->activo = true;
        $coupon->fecha_fin = $data['fecha_fin'] ?? null;
        $coupon->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cupón creado.',
            'item' => ['id' => $coupon->id, 'codigo' => $coupon->codigo],
        ], 201);
    }

    public function couponsDestroy(Request $request, int $id): JsonResponse
    {
        return $this->eliminar(StoreCoupon::class, $this->empresaId($request), $id, 'Cupón');
    }

    // ---- Productos (gestión de existentes) ---------------------------------

    public function productsIndex(Request $request): JsonResponse
    {
        $items = $this->scope(StoreProduct::query(), $this->empresaId($request))
            ->with('stockItems.inventoryItem')
            ->latest('id')->limit(15)
            ->get(['id', 'nombre', 'precio_venta', 'publicado'])
            ->each->append('stock'); // stock ahora es virtual (leído del inventario)

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function productsUpdate(Request $request, int $id): JsonResponse
    {
        // 'stock' ya no es editable: se deriva del inventario (store_product_stock).
        $data = $request->validate([
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'publicado' => ['nullable', 'boolean'],
        ]);

        $producto = $this->scope(StoreProduct::query(), $this->empresaId($request))->find($id);
        if (! $producto) {
            return response()->json([
                'ok' => false,
                'error' => 'no_encontrado',
                'mensaje' => 'Producto no encontrado en esta empresa.',
            ], 404);
        }

        foreach (['precio_venta', 'publicado'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] !== null) {
                $producto->{$campo} = $data[$campo];
            }
        }
        $producto->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Producto actualizado.',
            'item' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio_venta' => $producto->precio_venta,
                'stock' => $producto->stock,
                'publicado' => $producto->publicado,
            ],
        ]);
    }

    public function productsDestroy(Request $request, int $id): JsonResponse
    {
        return $this->eliminar(StoreProduct::class, $this->empresaId($request), $id, 'Producto');
    }

    // ---- Helpers ------------------------------------------------------------

    private function empresaId(Request $request): int
    {
        return (int) $request->attributes->get('n8n_empresa')->id;
    }

    private function scope(Builder $query, int $empresaId): Builder
    {
        return $query->withoutGlobalScope(EmpresaScope::class)->where('empresa_id', $empresaId);
    }

    private function eliminar(string $model, int $empresaId, int $id, string $etiqueta): JsonResponse
    {
        $item = $this->scope($model::query(), $empresaId)->find($id);

        if (! $item) {
            return response()->json([
                'ok' => false,
                'error' => 'no_encontrado',
                'mensaje' => $etiqueta . ' no encontrado en esta empresa.',
            ], 404);
        }

        $item->delete();

        return response()->json(['ok' => true, 'mensaje' => $etiqueta . ' eliminado.']);
    }
}
