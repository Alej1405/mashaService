<?php
namespace App\Filament\App\Resources\CmsTeamMemberResource\Pages;
use App\Filament\App\Resources\CmsTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsTeamMember extends EditRecord {
    protected static string $resource = CmsTeamMemberResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
