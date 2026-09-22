<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Carga el saldo inicial valorado de un inventario que ya venía andando.
 *
 * El problema que resuelve: los movimientos viejos no tienen costo correcto
 * porque el sistema usaba el último precio de compra. Recalcular esa historia
 * sería inventar costos que nunca existieron, y falsear un kardex es peor que
 * no tenerlo.
 *
 * Lo que hace en cambio: corta a una fecha, toma el stock que hay hoy, lo
 * valora con el único costo disponible y lo deja como la primera fila del
 * kardex. De ahí en adelante el promedio ponderado es real.
 *
 *   php artisan inventario:saldo-inicial rivet-ecuador-sas --fecha=2026-09-30
 *   php artisan inventario:saldo-inicial rivet-ecuador-sas --fecha=2026-09-30 --aplicar
 */
class InventarioSaldoInicial extends Command
{
    protected $signature = 'inventario:saldo-inicial
        {empresa : slug de la empresa}
        {--fecha= : fecha de corte (por defecto, hoy)}
        {--aplicar : sin esta bandera solo simula}';

    protected $description = 'Valora el stock actual y lo deja como saldo inicial del kardex';

    public function handle(): int
    {
        $empresa = Empresa::where('slug', $this->argument('empresa'))->first();

        if (! $empresa) {
            $this->error("No existe la empresa '{$this->argument('empresa')}'.");
            return self::FAILURE;
        }

        $fecha   = $this->option('fecha') ?: now()->toDateString();
        $aplicar = (bool) $this->option('aplicar');

        $items = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('stock_actual', '>', 0)
            ->where(function ($q) {
                $q->whereNull('costo_promedio')->orWhere('costo_promedio', '<=', 0);
            })
            ->orderBy('codigo')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No hay nada que valorar: todos los ítems con stock ya tienen costo.');
            return self::SUCCESS;
        }

        $filas = [];
        $total = 0.0;
        $sinCosto = 0;

        foreach ($items as $item) {
            $costo = (float) ($item->purchase_price ?? 0);
            $valor = round((float) $item->stock_actual * $costo, 4);
            $total += $valor;

            if ($costo <= 0) {
                $sinCosto++;
            }

            $filas[] = [
                $item->codigo,
                mb_strimwidth($item->nombre, 0, 28, '…'),
                rtrim(rtrim(number_format((float) $item->stock_actual, 4, ',', '.'), '0'), ','),
                $costo > 0 ? number_format($costo, 4, ',', '.') : 'SIN COSTO',
                number_format($valor, 2, ',', '.'),
            ];
        }

        $this->table(['Código', 'Ítem', 'Stock', 'Costo', 'Valor'], $filas);
        $this->line('');
        $this->info("Ítems: {$items->count()} · Valor del saldo inicial: $ " . number_format($total, 2, ',', '.'));

        if ($sinCosto > 0) {
            $this->warn("{$sinCosto} ítem(s) no tienen precio de compra: entrarían al kardex con costo cero.");
            $this->warn('Cárgales el costo antes de aplicar, o su costo de ventas saldrá mal.');
        }

        if (! $aplicar) {
            $this->line('');
            $this->comment('Esto fue una simulación. Repite con --aplicar para escribirlo.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($items, $fecha, $empresa) {
            foreach ($items as $item) {
                $costo = (float) ($item->purchase_price ?? 0);
                $valor = round((float) $item->stock_actual * $costo, 4);

                InventoryMovement::create([
                    'empresa_id'        => $empresa->id,
                    'inventory_item_id' => $item->id,
                    'type'              => 'entrada',
                    'motivo'            => 'saldo_inicial',
                    'quantity'          => $item->stock_actual,
                    'unit_price'        => $costo,
                    'total'             => $valor,
                    'saldo_cantidad'    => $item->stock_actual,
                    'costo_promedio'    => $costo,
                    'saldo_valor'       => $valor,
                    'description'       => 'Saldo inicial valorado al corte',
                    'date'              => $fecha,
                ]);

                $item->forceFill([
                    'costo_promedio' => $costo,
                    'saldo_valorado' => $valor,
                ])->save();
            }
        });

        $this->info('Saldo inicial cargado. De aquí en adelante el promedio se calcula solo.');

        return self::SUCCESS;
    }
}
