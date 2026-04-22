<?php

namespace App\Console\Commands;

use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use Illuminate\Console\Command;

class LogisticsSincronizarEstados extends Command
{
    protected $signature = 'logistics:sincronizar-estados
                            {--empresa= : ID de empresa (omitir = todas)}
                            {--fix       : Aplicar los cambios (sin --fix solo muestra el reporte)}';

    protected $description = 'Sincroniza el estado de cada LogisticsShipment con el estado real de sus paquetes (Kanban).';

    // ── Mapeo paquete → embarque ──────────────────────────────────────────────

    private static function embarqueDesde(string $pkgEstado, ?string $pkgSecundario): ?string
    {
        return match(true) {
            $pkgEstado === 'embarque_solicitado' && $pkgSecundario === 'embarque_confirmado'
                => 'carga_registrada',
            $pkgEstado === 'embarque_solicitado'
                => 'embarque_solicitado',
            $pkgEstado === 'registrado' && $pkgSecundario === 'arribo_miami'
                => 'carga_embarcada',
            $pkgEstado === 'registrado'
                => 'carga_registrada',
            // Los secundarios de en_aduana coinciden con estados del embarque
            $pkgEstado === 'en_aduana' && $pkgSecundario !== null
                => $pkgSecundario,
            $pkgEstado === 'en_aduana'
                => 'en_aduana',
            $pkgEstado === 'finalizado_aduana' && $pkgSecundario === 'en_despacho'
                => 'autorizado_salida',
            $pkgEstado === 'finalizado_aduana'
                => 'pagada',
            $pkgEstado === 'pago_servicios'
                => 'pagada',
            $pkgEstado === 'en_entrega' && $pkgSecundario === 'entregado'
                => 'entregada',
            $pkgEstado === 'en_entrega'
                => 'autorizado_salida',
            default => null,
        };
    }

    private static function prioridad(string $estado): int
    {
        static $orden = [
            'embarque_solicitado'        => 1,
            'carga_registrada'           => 2,
            'consolidando'               => 3,
            'fraccionamiento_en_proceso' => 4,
            'carga_embarcada'            => 5,
            'en_aduana'                  => 6,
            'declaracion_transmitida'    => 7,
            'aforo_automatico'           => 8,
            'aforo_documental'           => 8,
            'aforo_fisico'               => 8,
            'liquidada'                  => 9,
            'pagada'                     => 10,
            'autorizado_salida'          => 11,
            'entregada'                  => 12,
        ];

        return $orden[$estado] ?? 0;
    }

    // ── Ejecución ─────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $fix       = $this->option('fix');
        $empresaId = $this->option('empresa');

        if (! $fix) {
            $this->warn('Modo LECTURA. Usa --fix para aplicar cambios.');
        }

        $query = LogisticsShipment::withoutGlobalScopes()
            ->with(['packages' => fn ($q) => $q->withoutGlobalScopes()]);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $shipments = $query->get();

        $this->info("Embarques encontrados: {$shipments->count()}");
        $this->newLine();

        $cambios = 0;

        foreach ($shipments as $shipment) {
            $paquetes = $shipment->packages;

            if ($paquetes->isEmpty()) {
                $this->line("  <fg=gray>[{$shipment->numero_embarque}]</> sin paquetes — omitido");
                continue;
            }

            // Estado mínimo (más conservador) entre todos los paquetes
            $estadoMin = null;
            $prioMin   = PHP_INT_MAX;

            foreach ($paquetes as $pkg) {
                $est  = self::embarqueDesde($pkg->estado, $pkg->estado_secundario);
                $prio = self::prioridad($est ?? 'embarque_solicitado');

                if ($prio < $prioMin) {
                    $prioMin   = $prio;
                    $estadoMin = $est;
                }
            }

            if (! $estadoMin || ! array_key_exists($estadoMin, LogisticsShipment::ESTADOS)) {
                $this->line("  <fg=gray>[{$shipment->numero_embarque}]</> mapeo no encontrado — omitido");
                continue;
            }

            if ($shipment->estado === $estadoMin) {
                $this->line("  <fg=green>[{$shipment->numero_embarque}]</> ya correcto ({$estadoMin})");
                continue;
            }

            $antes = $shipment->estado;
            $this->line("  <fg=yellow>[{$shipment->numero_embarque}]</> {$antes} → <fg=cyan>{$estadoMin}</>");

            if ($fix) {
                $shipment->update(['estado' => $estadoMin]);
                $cambios++;
            }
        }

        $this->newLine();

        if ($fix) {
            $this->info("Embarques actualizados: {$cambios}");
        } else {
            $this->comment("Embarques a actualizar: {$cambios} (sin cambios — agrega --fix para aplicar)");
        }

        return self::SUCCESS;
    }
}
