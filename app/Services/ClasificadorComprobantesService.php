<?php

namespace App\Services;

use App\Models\ComprobanteSri;
use App\Models\Gasto;
use App\Models\GastoLinea;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\TipoGasto;
use Illuminate\Support\Facades\DB;

/**
 * Convierte un comprobante del portal en contabilidad de verdad.
 *
 * El listado del SRI dice quién facturó y por cuánto, no qué se compró. Hasta
 * que alguien lo diga, esa factura suma al 104 y no existe en el balance. Al
 * clasificarla se crea el documento que corresponde y con él su asiento:
 *
 *   inventario   → una compra, que mueve el kardex y recalcula el promedio
 *   activo fijo  → un ítem de inventario con su vida útil, que se depreciará
 *   gasto        → un gasto con su IVA como crédito tributario
 *   no deducible → igual, pero marcado para la conciliación de la renta
 *
 * De ahí que existan dos puertas para que algo entre al inventario: el módulo
 * de bodega, o una factura que llegó del portal. La segunda es la que salva
 * los meses que nadie registró en su momento.
 *
 * Marco: skills `contabilidad-ec` e `inventarios-contable-ec`.
 */
class ClasificadorComprobantesService
{
    public const DESTINOS = [
        'inventario'   => 'Compra de inventario',
        'activo_fijo'  => 'Activo fijo',
        'gasto'        => 'Gasto',
        'no_deducible' => 'Gasto no deducible',
    ];

    /**
     * Clasifica un comprobante y crea su documento contable.
     *
     * @param  array{destino: string, tipo_gasto_id?: int, inventory_item_id?: int, cantidad?: float}  $datos
     * @return array{ok: bool, documento?: string, id?: int, error?: string}
     */
    public function clasificar(ComprobanteSri $c, array $datos, ?int $usuarioId = null): array
    {
        if ($c->conciliado_con) {
            return ['ok' => false, 'error' => 'Ese comprobante ya está registrado en el ERP.'];
        }

        $destino = $this->destino($c, $datos);

        if (! array_key_exists((string) $destino, self::DESTINOS)) {
            return ['ok' => false, 'error' => 'Hay que decir qué fue esa factura.'];
        }

        // El período cerrado no admite documentos nuevos, aquí tampoco.
        try {
            app(ContabilidadService::class)->exigirEjercicioAbierto(
                $c->empresa_id, $c->fecha_emision->toDateString(),
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return DB::transaction(function () use ($c, $datos, $destino, $usuarioId) {
            $resultado = match ($destino) {
                'inventario', 'activo_fijo' => $this->comoCompra($c, $datos, $destino),
                default                     => $this->comoGasto($c, $datos, $destino === 'no_deducible'),
            };

            if (! ($resultado['ok'] ?? false)) {
                return $resultado;
            }

            $c->update([
                'destino'           => $destino,
                'tipo_gasto_id'     => $datos['tipo_gasto_id'] ?? null,
                'inventory_item_id' => $datos['inventory_item_id'] ?? null,
                'cantidad'          => $datos['cantidad'] ?? null,
                'clasificado_en'    => now(),
                'clasificado_por'   => $usuarioId,
                'conciliado_con'    => $resultado['documento'],
                'conciliado_id'     => $resultado['id'],
            ]);

            return $resultado;
        });
    }

    /**
     * Qué fue esa factura.
     *
     * Si entra a un ítem del inventario, **el ítem ya lo dice**: el formulario
     * de bodega obliga a decir si es materia prima, insumo, producto terminado
     * o activo fijo. Volver a preguntarlo aquí sería pedir dos veces el mismo
     * dato, y con dos respuestas que pueden contradecirse. Solo se pregunta por
     * lo que no está registrado en ninguna parte: los gastos.
     */
    private function destino(ComprobanteSri $c, array $datos): ?string
    {
        if ($itemId = $datos['inventory_item_id'] ?? null) {
            $item = InventoryItem::withoutGlobalScopes()
                ->where('empresa_id', $c->empresa_id)->find($itemId);

            if ($item) {
                return $item->type === 'activo_fijo' ? 'activo_fijo' : 'inventario';
            }
        }

        return $datos['destino'] ?? null;
    }

    /**
     * La factura entra como compra: eso mueve el kardex, recalcula el costo
     * promedio y genera el asiento, todo por el camino normal del ERP.
     */
    private function comoCompra(ComprobanteSri $c, array $datos, string $destino): array
    {
        $itemId = $datos['inventory_item_id'] ?? null;

        if (! $itemId) {
            return ['ok' => false, 'error' => 'Hay que decir a qué ítem del inventario entra.'];
        }

        $item = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', $c->empresa_id)->find($itemId);

        if (! $item) {
            return ['ok' => false, 'error' => 'Ese ítem no es de esta empresa.'];
        }

        $base = $c->base;
        // Sin cantidad no hay costo unitario, y sin costo unitario no hay kardex.
        $cantidad = (float) ($datos['cantidad'] ?? 0) ?: 1.0;

        $compra = Purchase::withoutGlobalScopes()->create([
            'empresa_id'     => $c->empresa_id,
            'supplier_id'    => $this->proveedor($c)?->id,
            'numero_factura' => $c->numero,
            'date'           => $c->fecha_emision,
            'subtotal'       => $base,
            'iva'            => $c->iva,
            'total'          => $c->total,
            'forma_pago'     => 'transferencia',
            // El check de la tabla solo admite contado, credito_local y
            // credito_exterior: una factura del portal es compra local a crédito.
            'tipo_pago'      => 'credito_local',
            'status'         => 'borrador',
            'notas'          => 'Del portal del SRI · ' . $c->razon_social,
        ]);

        DB::table('purchase_items')->insert([
            'purchase_id'       => $compra->id,
            'inventory_item_id' => $item->id,
            'quantity'          => $cantidad,
            'unit_price'        => round($base / max($cantidad, 0.0001), 4),
            'aplica_iva'        => $c->iva > 0,
            'subtotal'          => $base,
            'iva_monto'         => $c->iva,
            'total_item'        => $c->total,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Confirmar es lo que dispara el asiento y el movimiento de kardex.
        $compra->update(['status' => 'confirmado']);
        $compra->refresh();

        if (! $compra->journal_entry_id) {
            return ['ok' => false, 'error' => 'La compra se creó pero no generó asiento: '
                . ($compra->error_contable_msg ?: 'revisa la cuenta contable del ítem.')];
        }

        return ['ok' => true, 'documento' => 'purchase', 'id' => $compra->id,
                'detalle' => "compra {$compra->numero_factura} · {$item->nombre}"];
    }

    /** La factura entra como gasto, con su IVA como crédito tributario. */
    private function comoGasto(ComprobanteSri $c, array $datos, bool $noDeducible): array
    {
        $tipo = TipoGasto::find($datos['tipo_gasto_id'] ?? null);

        if (! $tipo) {
            return ['ok' => false, 'error' => 'Hay que decir qué tipo de gasto fue.'];
        }

        // Los tipos son globales; la cuenta sale del plan de esta empresa.
        $cuenta = $tipo->cuentaEn($c->empresa_id);

        if (! $cuenta) {
            return ['ok' => false, 'error' => "El tipo «{$tipo->nombre}» no encuentra su cuenta "
                . "({$tipo->codigo_cuenta}) en el plan de esta empresa."];
        }

        $gasto = Gasto::create([
            'empresa_id'       => $c->empresa_id,
            'supplier_id'      => $this->proveedor($c)?->id,
            'numero_documento' => $c->numero,
            'tipo_documento'   => 'factura',
            'fecha'            => $c->fecha_emision,
            'descripcion'      => $tipo->nombre . ' · ' . $c->razon_social,
            'forma_pago'       => 'transferencia',
            'estado'           => 'borrador',
        ]);

        GastoLinea::create([
            'gasto_id'        => $gasto->id,
            'tipo_gasto_id'   => $tipo->id,
            'account_plan_id' => $cuenta,
            'descripcion'     => 'Del portal del SRI',
            'base'            => $c->base,
            'porcentaje_iva'  => $c->base > 0 ? round($c->iva / $c->base * 100, 2) : 0,
            'iva'             => $c->iva,
            'deducible'       => ! $noDeducible,
            'motivo_no_deducible' => $noDeducible ? 'Marcado al clasificar el comprobante' : null,
        ]);

        $confirmado = app(GastoService::class)->confirmar($gasto->fresh('lineas'));

        return ['ok' => true, 'documento' => 'gasto', 'id' => $confirmado->id,
                'detalle' => "gasto {$c->numero} · {$tipo->nombre}"];
    }

    /** El proveedor del portal, creado si no estaba. */
    private function proveedor(ComprobanteSri $c): ?Supplier
    {
        if (! $c->identificacion) {
            return null;
        }

        $existente = Supplier::withoutGlobalScopes()
            ->where('empresa_id', $c->empresa_id)
            ->where('numero_identificacion', $c->identificacion)
            ->first();

        if ($existente) {
            return $existente;
        }

        // El portal solo da nombre y RUC; el resto son obligatorios en la tabla
        // y quedan marcados para que alguien los complete cuando haga falta.
        return Supplier::withoutGlobalScopes()->create([
            'empresa_id'            => $c->empresa_id,
            'nombre'                => $c->razon_social ?: $c->identificacion,
            'tipo_persona'          => strlen($c->identificacion) === 13 ? 'juridica' : 'natural',
            'tipo_identificacion'   => strlen($c->identificacion) === 13 ? 'ruc' : 'cedula',
            'numero_identificacion' => $c->identificacion,
            'tipo_proveedor'        => 'bienes',
            'contacto_principal'    => 'Por completar',
            'telefono_principal'    => 'Por completar',
            'correo_principal'      => 'porcompletar@' . \Illuminate\Support\Str::slug($c->razon_social ?: 'proveedor') . '.ec',
            'activo'                => true,
        ]);
    }

    /**
     * Lo que se propone para un comprobante, mirando cómo se clasificó antes
     * a ese mismo proveedor.
     *
     * Con veintinueve facturas del mismo proveedor, decirlo una vez debería
     * bastar para las otras veintiocho.
     *
     * @return array{destino: string|null, tipo_gasto_id: int|null, inventory_item_id: int|null, visto: int}
     */
    public function proponer(ComprobanteSri $c): array
    {
        $anterior = ComprobanteSri::where('empresa_id', $c->empresa_id)
            ->where('identificacion', $c->identificacion)
            ->whereNotNull('destino')
            ->latest('clasificado_en')
            ->first();

        $vistos = ComprobanteSri::where('empresa_id', $c->empresa_id)
            ->where('identificacion', $c->identificacion)
            ->whereNotNull('destino')->count();

        return [
            'destino'           => $anterior?->destino,
            'tipo_gasto_id'     => $anterior?->tipo_gasto_id,
            'inventory_item_id' => $anterior?->inventory_item_id,
            'visto'             => $vistos,
        ];
    }

    /**
     * Clasifica de golpe todos los comprobantes pendientes de un proveedor.
     *
     * @return array{hechos: int, fallos: array<int, string>}
     */
    public function clasificarProveedor(int $empresaId, string $identificacion, array $datos, ?int $usuarioId = null): array
    {
        $pendientes = ComprobanteSri::where('empresa_id', $empresaId)
            ->where('identificacion', $identificacion)
            ->sinClasificar()->where('origen', 'recibido')->get();

        $hechos = 0;
        $fallos = [];

        foreach ($pendientes as $c) {
            // La cantidad se reparte por comprobante: cada factura trae la suya.
            $suyo = $datos;

            if (($datos['inventory_item_id'] ?? null) && empty($datos['cantidad'])) {
                $suyo['cantidad'] = 1;
            }

            $r = $this->clasificar($c, $suyo, $usuarioId);

            if ($r['ok'] ?? false) {
                $hechos++;
            } else {
                $fallos[] = "{$c->numero}: {$r['error']}";
            }
        }

        return ['hechos' => $hechos, 'fallos' => $fallos];
    }

    /**
     * Cuántas facturas esperan en la bandeja.
     *
     * Solo las recibidas: una emitida es una venta y no se clasifica aquí. Si
     * se contaran, el menú diría un número y la bandeja mostraría otro.
     */
    public function pendientes(int $empresaId, ?int $anio = null, ?int $mes = null): int
    {
        return ComprobanteSri::where('empresa_id', $empresaId)
            ->where('origen', 'recibido')
            ->when($anio && $mes, fn ($q) => $q->delPeriodo($anio, $mes))
            ->sinClasificar()->count();
    }
}
