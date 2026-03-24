<?php

namespace App\Filament\App\Resources\StoreCouponResource\Pages;

use App\Filament\App\Resources\StoreCouponResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreCoupons extends ListRecords
{
    protected static string $resource = StoreCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
