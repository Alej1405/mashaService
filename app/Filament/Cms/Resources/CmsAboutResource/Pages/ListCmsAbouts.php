<?php

namespace App\Filament\Cms\Resources\CmsAboutResource\Pages;

use App\Filament\Cms\Resources\CmsAboutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsAbouts extends ListRecords
{
    protected static string $resource = CmsAboutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => ! \App\Models\CmsAbout::query()->exists()),
        ];
    }
}
