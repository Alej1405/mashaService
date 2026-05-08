<?php

namespace App\Console\Commands;

use App\Models\AccountingMap;
use App\Models\AccountPlan;
use App\Models\Empresa;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Console\Command;

class CheckAccountingMapsCommand extends Command
{
    protected $signature = 'accounting:check-maps
                            {--fix : Reparar mapas faltantes automáticamente}
                            {--empresa= : ID de empresa específica}';

    protected $description = 'Verifica y repara mapas contables de empresas';

    public function handle(): int
    {
        $empresas = Empresa::query()
            ->when($this->option('empresa'), fn ($q) => $q->where('id', $this->option('empresa')))
            ->get();

        $totalProblemas = 0;

        foreach ($empresas as $empresa) {
            $this->line("\n<fg=cyan>Empresa:</> {$empresa->name} (ID: {$empresa->id})");

            // Verificar si tiene mapas contables
            $mapasEmpresa = AccountingMap::where('empresa_id', $empresa->id)->count();
            if ($mapasEmpresa === 0) {
                $this->warn("  Sin mapas contables — ejecutar reparación");
                $totalProblemas++;
                if ($this->option('fix')) {
                    $this->repararMapas($empresa);
                }
                continue;
            }

            // Verificar combinaciones usadas en ventas
            $tiposUsadosVentas = \DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.empresa_id', $empresa->id)
                ->distinct()
                ->pluck('sale_items.tipo_item')
                ->unique();

            $movimientosVenta = ['venta_contado', 'venta_credito', 'costo_venta', 'iva_ventas'];

            foreach ($tiposUsadosVentas as $tipo) {
                foreach ($movimientosVenta as $movimiento) {
                    if ($tipo === 'servicio' && $movimiento === 'costo_venta') continue;

                    $existe = AccountingMap::where('empresa_id', $empresa->id)
                        ->where('tipo_item', $tipo)
                        ->where('tipo_movimiento', $movimiento)
                        ->exists();

                    $existeGlobal = AccountingMap::whereNull('empresa_id')
                        ->where('tipo_item', $tipo)
                        ->where('tipo_movimiento', $movimiento)
                        ->exists();

                    if (!$existe && !$existeGlobal) {
                        $this->error("  FALTA: {$tipo} × {$movimiento}");
                        $totalProblemas++;
                    }
                }
            }

            // Ventas confirmadas sin asiento
            $ventasSinAsiento = Sale::where('empresa_id', $empresa->id)
                ->where('estado', 'confirmado')
                ->whereNull('journal_entry_id')
                ->count();

            if ($ventasSinAsiento > 0) {
                $this->warn("  {$ventasSinAsiento} venta(s) confirmadas sin asiento contable");
                $totalProblemas += $ventasSinAsiento;
            }

            // Compras confirmadas sin asiento
            $comprasSinAsiento = Purchase::where('empresa_id', $empresa->id)
                ->where('status', 'confirmado')
                ->whereNull('journal_entry_id')
                ->count();

            if ($comprasSinAsiento > 0) {
                $this->warn("  {$comprasSinAsiento} compra(s) confirmadas sin asiento contable");
                $totalProblemas += $comprasSinAsiento;
            }

            // Ventas con error contable
            $ventasConError = Sale::where('empresa_id', $empresa->id)
                ->where('error_contable', true)
                ->count();

            if ($ventasConError > 0) {
                $this->error("  {$ventasConError} venta(s) con error_contable=true");
                $totalProblemas += $ventasConError;

                Sale::where('empresa_id', $empresa->id)
                    ->where('error_contable', true)
                    ->each(function ($sale) {
                        $this->line("    Sale #{$sale->id} ({$sale->referencia}): {$sale->error_contable_msg}");
                    });
            }

            // Compras con error contable
            $comprasConError = Purchase::where('empresa_id', $empresa->id)
                ->where('error_contable', true)
                ->count();

            if ($comprasConError > 0) {
                $this->error("  {$comprasConError} compra(s) con error_contable=true");
                $totalProblemas += $comprasConError;
            }

            if ($ventasSinAsiento === 0 && $comprasSinAsiento === 0 && $ventasConError === 0 && $comprasConError === 0) {
                $this->info("  OK — {$mapasEmpresa} mapas configurados, sin errores contables");
            }

            if ($this->option('fix')) {
                $this->repararMapas($empresa);
            }
        }

        if ($totalProblemas > 0) {
            $this->newLine();
            $this->warn("Total de problemas encontrados: {$totalProblemas}");
            $this->line("Usa <fg=yellow>--fix</> para reparar mapas automáticamente.");
        } else {
            $this->newLine();
            $this->info("Todo en orden.");
        }

        return $totalProblemas > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function repararMapas(Empresa $empresa): void
    {
        $baseMaps = AccountingMap::withoutGlobalScopes()->whereNull('empresa_id')->get();
        $reparados = 0;

        foreach ($baseMaps as $baseMap) {
            $baseAccount = AccountPlan::withoutGlobalScopes()->find($baseMap->account_plan_id);
            if (!$baseAccount) continue;

            $empresaAccount = AccountPlan::where('empresa_id', $empresa->id)
                ->where('code', $baseAccount->code)
                ->first();

            if (!$empresaAccount) continue;

            $created = AccountingMap::firstOrCreate(
                [
                    'empresa_id'       => $empresa->id,
                    'tipo_item'        => $baseMap->tipo_item,
                    'tipo_movimiento'  => $baseMap->tipo_movimiento,
                ],
                ['account_plan_id' => $empresaAccount->id]
            );

            if ($created->wasRecentlyCreated) $reparados++;
        }

        if ($reparados > 0) {
            $this->info("  Reparados: {$reparados} mapas creados para {$empresa->name}");
        }
    }
}
