<?php

namespace App\Filament\Ecommerce\Resources\StoreCouponResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCouponResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreCoupon extends EditRecord
{
    protected static string $resource = StoreCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
