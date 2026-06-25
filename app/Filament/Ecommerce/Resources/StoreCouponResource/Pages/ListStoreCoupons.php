<?php

namespace App\Filament\Ecommerce\Resources\StoreCouponResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreCoupons extends ListRecords
{
    protected static string $resource = StoreCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
