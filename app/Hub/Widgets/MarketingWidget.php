<?php

namespace App\Hub\Widgets;

use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\Empresa;
use App\Models\MailCampaign;

/**
 * Resumen del módulo Marketing (CMS + mailing) en el hub. Solo agregados.
 */
class MarketingWidget implements HubWidget
{
    public static function module(): string
    {
        return 'marketing';
    }

    public static function meta(): array
    {
        return [
            'titulo' => 'CMS',
            'icono'  => 'heroicon-o-globe-alt',
            'color'  => '#7c3aed',
            'path'   => 'cms',
        ];
    }

    public static function metrics(Empresa $empresa): array
    {
        return [
            [
                'label' => 'Publicaciones',
                'value' => CmsPost::where('empresa_id', $empresa->id)->count(),
            ],
            [
                'label' => 'Servicios',
                'value' => CmsService::where('empresa_id', $empresa->id)->count(),
            ],
            [
                'label' => 'Campañas',
                'value' => MailCampaign::where('empresa_id', $empresa->id)->count(),
            ],
        ];
    }
}
