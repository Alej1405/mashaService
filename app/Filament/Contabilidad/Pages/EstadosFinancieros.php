<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\CatalogoSupercias;
use App\Services\SuperciasService;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Los estados financieros que recibe la Superintendencia.
 *
 * Se arman con **el catálogo de la SCVS**, que es la misma estructura del
 * archivo que se sube al portal: la pantalla y el .txt salen del mismo sitio,
 * así que no pueden decir cosas distintas.
 *
 * Antes se armaban con una lista de líneas propia del ERP que no casaba con
 * ningún código del catálogo, y por eso el informe salía entero en cero.
 *
 * Marco: skills `supercias-ec` y `contabilidad-ec`.
 */
class EstadosFinancieros extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Estados financieros';
    protected static ?string $title           = 'Estados financieros';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.contabilidad.estados-financieros';

    public ?int $anio = null;

    /** La clave del catálogo, no un nombre suelto. */
    public string $estado = 'situacion_financiera';

    /** Mostrar también las líneas en cero, como en el archivo del portal. */
    public bool $verTodas = false;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public function mount(): void
    {
        $this->anio = (int) request()->integer('anio', now()->year);

        $pedido = request()->string('estado')->toString();
        $this->estado = array_key_exists($pedido, CatalogoSupercias::ESTADOS)
            ? $pedido
            : 'situacion_financiera';
    }

    public function cambiarEstado(string $estado): void
    {
        if (array_key_exists($estado, CatalogoSupercias::ESTADOS)) {
            $this->estado = $estado;
        }
    }

    public function cambiarAnio(int $anio): void
    {
        $this->anio = $anio;
    }

    public function alternarVacias(): void
    {
        $this->verTodas = ! $this->verTodas;
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $anio = $this->anio ?? now()->year;

        $servicio = app(SuperciasService::class);

        // El de cambios en el patrimonio no es una lista: es una matriz de
        // conceptos por movimientos, y se pinta distinto.
        if ($this->estado === 'cambios_patrimonio') {
            return [
                'empresa'  => $empresa,
                'anio'     => $anio,
                'estado'   => $this->estado,
                'estados'  => CatalogoSupercias::ESTADOS,
                'anios'    => range(now()->year, now()->year - 3),
                'matriz'   => $servicio->cambiosEnPatrimonio($empresa->id, $anio),
                'lineas'   => [],
                'conValor' => 0,
                'total'    => 0,
                'verTodas' => $this->verTodas,
            ];
        }

        $lineas = $servicio->estado($empresa->id, $anio, $this->estado);
        $conValor = array_filter($lineas, fn ($l) => $l['tiene_valor']);

        return [
            'matriz'    => null,
            'empresa'   => $empresa,
            'anio'      => $anio,
            'estado'    => $this->estado,
            'estados'   => CatalogoSupercias::ESTADOS,
            'lineas'    => $this->verTodas ? $lineas : array_values($conValor),
            'conValor'  => count($conValor),
            'total'     => count($lineas),
            'verTodas'  => $this->verTodas,
            'anios'     => range(now()->year, now()->year - 3),
        ];
    }
}
