<?php

namespace App\Observers;

use App\Models\AccountPlan;
use App\Services\MapeoSuperciasService;

/**
 * Una cuenta nueva nace con su línea del estado puesta.
 *
 * Si hay que acordarse de mapearla, no se mapea, y la cuenta desaparece del
 * balance que recibe la Superintendencia sin que nadie se entere hasta abril.
 */
class AccountPlanObserver
{
    public function created(AccountPlan $cuenta): void
    {
        $this->asignar($cuenta);
    }

    public function updated(AccountPlan $cuenta): void
    {
        // Si le cambiaron el nombre y nadie había revisado la línea, se vuelve
        // a proponer: el nombre es de donde sale la propuesta.
        if ($cuenta->wasChanged('name') && ! $cuenta->codigo_supercias_revisado) {
            $this->asignar($cuenta, true);
        }
    }

    private function asignar(AccountPlan $cuenta, bool $rehacer = false): void
    {
        if (! $cuenta->accepts_movements) {
            return;
        }

        if ($cuenta->codigo_supercias && ! $rehacer) {
            return;
        }

        $propuesta = app(MapeoSuperciasService::class)->proponer($cuenta);

        if (! $propuesta['codigo']) {
            return;
        }

        // saveQuietly: no se vuelve a disparar el observer con este mismo guardado.
        $cuenta->forceFill([
            'codigo_supercias'           => $propuesta['codigo'],
            'codigo_supercias_confianza' => $propuesta['confianza'],
            'codigo_supercias_revisado'  => false,
        ])->saveQuietly();
    }
}
