<?php

namespace App\Filament\Cms\Pages;

use App\Models\CmsAbout;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\CmsTeamMember;
use App\Models\CmsTerminos;
use App\Models\CmsTestimonial;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class CmsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title          = 'Dashboard CMS';
    protected static ?string $slug           = 'dashboard';
    protected static ?int    $navigationSort = -1;

    protected static string $view = 'filament.cms.pages.cms-dashboard';

    public function getTitle(): string
    {
        $empresa = Filament::getTenant();
        return 'CMS — ' . ($empresa?->name ?? 'Sitio Web');
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $eid     = $empresa->id;

        $lastPost = CmsPost::where('empresa_id', $eid)->latest()->first();

        return [
            'empresa'           => $empresa,
            'hasHero'           => CmsHero::where('empresa_id', $eid)->exists(),
            'hasAbout'          => CmsAbout::where('empresa_id', $eid)->exists(),
            'hasContact'        => CmsContact::where('empresa_id', $eid)->exists(),
            'hasTerminos'       => CmsTerminos::where('empresa_id', $eid)->exists(),
            'servicesCount'     => CmsService::where('empresa_id', $eid)->count(),
            'teamCount'         => CmsTeamMember::where('empresa_id', $eid)->count(),
            'testimonialsCount' => CmsTestimonial::where('empresa_id', $eid)->count(),
            'logosCount'        => CmsClientLogo::where('empresa_id', $eid)->count(),
            'faqsCount'         => CmsFaq::where('empresa_id', $eid)->count(),
            'postsCount'        => CmsPost::where('empresa_id', $eid)->count(),
            'lastPost'          => $lastPost,
        ];
    }
}
