<?php

namespace App\Filament\App\Resources\StoreCouponResource\Pages;

use App\Filament\App\Resources\StoreCouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreCoupon extends EditRecord
{
    protected static string $resource = StoreCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
