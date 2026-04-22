<?php

namespace App\Filament\App\Resources\LogisticsShipmentBillResource\Pages;

use App\Filament\App\Resources\LogisticsShipmentBillResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLogisticsShipmentBill extends CreateRecord
{
    protected static string $resource = LogisticsShipmentBillResource::class;

    public function mount(): void
    {
        parent::mount();

        if (session()->has('bill_pdf_prefill')) {
            $this->form->fill(session()->pull('bill_pdf_prefill'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = \Filament\Facades\Filament::getTenant()->id;
        unset($data['_modo']);
        return $data;
    }
}
