<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Filament\Facades\Filament;

class QuickLinksWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.quick-links';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $empresa    = Filament::getTenant();
        $websiteUrl = $empresa?->website_url;

        $thumbnailUrl = null;
        if ($websiteUrl) {
            $encoded      = urlencode($websiteUrl);
            $thumbnailUrl = "https://api.microlink.io/?url={$encoded}&screenshot=true&meta=false&embed=screenshot.url";
        }

        return [
            'empresa'      => $empresa,
            'websiteUrl'   => $websiteUrl,
            'thumbnailUrl' => $thumbnailUrl,
        ];
    }
}
