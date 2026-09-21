<?php

namespace App\Observers;

use App\Models\CustomerWeb;
use App\Shared\Actions\OlvidarCacheCmsPunto;

/**
 * La descripción, el horario, el logo y la ubicación del local viven en
 * customer_web. Son justo los campos que pinta el modal, así que al guardarlos
 * hay que tirar el caché del listado: si no, el cambio tarda hasta 10 minutos
 * en verse en la web.
 */
class CustomerWebObserver
{
    public function saved(CustomerWeb $web): void
    {
        $this->olvidar($web);
    }

    public function deleted(CustomerWeb $web): void
    {
        $this->olvidar($web);
    }

    private function olvidar(CustomerWeb $web): void
    {
        $customer = $web->customer()->withoutGlobalScopes()->first();

        if ($customer) {
            (new OlvidarCacheCmsPunto)->handle($customer);
        }
    }
}
