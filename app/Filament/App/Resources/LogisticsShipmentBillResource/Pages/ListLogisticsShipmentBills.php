<?php

namespace App\Filament\App\Resources\LogisticsShipmentBillResource\Pages;

use App\Filament\App\Resources\LogisticsShipmentBillResource;
use App\Services\PdfBillExtractor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListLogisticsShipmentBills extends ListRecords
{
    protected static string $resource = LogisticsShipmentBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cargar_pdf')
                ->label('Cargar desde PDF')
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->form([
                    FileUpload::make('factura_pdf_path')
                        ->label('Sube el PDF de la factura')
                        ->disk('public')
                        ->directory('logistics/facturas-proveedor')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required(),
                ])
                ->modalSubmitActionLabel('Leer PDF y continuar')
                ->action(function (array $data) {
                    $state = $data['factura_pdf_path'];
                    $file  = is_array($state) ? reset($state) : $state;

                    if ($file instanceof TemporaryUploadedFile) {
                        $absolutePath = $file->getRealPath();
                        $storedPath   = $file->store('logistics/facturas-proveedor', 'public');
                    } else {
                        $absolutePath = storage_path('app/public/' . $file);
                        $storedPath   = $file;
                    }

                    $extracted = [];
                    if ($absolutePath && file_exists($absolutePath)) {
                        $extracted = (new PdfBillExtractor())->extract($absolutePath);
                    }

                    session(['bill_pdf_prefill' => array_merge(
                        array_filter($extracted, fn ($v) => filled($v)),
                        ['factura_pdf_path' => $storedPath]
                    )]);

                    $this->redirect(
                        LogisticsShipmentBillResource::getUrl('create', tenant: \Filament\Facades\Filament::getTenant())
                    );
                }),

            CreateAction::make()->label('Nueva manual'),
        ];
    }
}
