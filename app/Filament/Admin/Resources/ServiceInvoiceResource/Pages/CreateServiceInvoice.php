<?php

namespace App\Filament\Admin\Resources\ServiceInvoiceResource\Pages;

use App\Filament\Admin\Resources\ServiceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceInvoice extends CreateRecord
{
    protected static string $resource = ServiceInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
