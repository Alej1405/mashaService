<?php
namespace App\Filament\App\Resources\CmsTeamMemberResource\Pages;
use App\Filament\App\Resources\CmsTeamMemberResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCmsTeamMember extends CreateRecord {
    protected static string $resource = CmsTeamMemberResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
