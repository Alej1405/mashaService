<?php

namespace App\Filament\App\Resources\ProductDesignResource\Pages;

use App\Filament\App\Resources\ProductDesignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductDesign extends EditRecord
{
    protected static string $resource = ProductDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
