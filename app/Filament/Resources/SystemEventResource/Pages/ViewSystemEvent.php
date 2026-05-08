<?php

namespace App\Filament\Resources\SystemEventResource\Pages;

use App\Filament\Resources\SystemEventResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSystemEvent extends ViewRecord
{
    protected static string $resource = SystemEventResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Detalle del evento')->columns(2)->schema([
                TextEntry::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'error', 'job_fallido' => 'danger',
                        'warning' => 'warning',
                        default   => 'info',
                    }),

                TextEntry::make('empresa.name')->label('Empresa')->placeholder('—'),
                TextEntry::make('modulo')->label('Módulo')->placeholder('—'),
                TextEntry::make('created_at')->label('Ocurrido el')->dateTime('d/m/Y H:i:s'),

                TextEntry::make('titulo')
                    ->label('Título')
                    ->columnSpanFull()
                    ->weight('semibold'),

                TextEntry::make('mensaje')
                    ->label('Mensaje')
                    ->columnSpanFull()
                    ->prose(),
            ]),

            Section::make('Resolución')->columns(2)->schema([
                IconEntry::make('resuelto')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextEntry::make('resuelto_at')->label('Resuelto el')->dateTime('d/m/Y H:i:s')->placeholder('Pendiente'),
                TextEntry::make('resueltoPor.name')->label('Resuelto por')->placeholder('—'),
            ]),

            Section::make('Contexto técnico')
                ->collapsed()
                ->schema([
                    KeyValueEntry::make('contexto')->label('')->columnSpanFull(),
                ])
                ->visible(fn ($record) => ! empty($record->contexto)),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resolver')
                ->label('Marcar resuelto')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => ! $this->record->resuelto)
                ->action(function (): void {
                    $this->record->resolver();
                    $this->refreshFormData(['resuelto', 'resuelto_at', 'resuelto_por']);
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
