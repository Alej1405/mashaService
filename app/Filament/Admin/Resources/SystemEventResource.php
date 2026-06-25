<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemEventResource\Pages;
use App\Models\SystemEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemEventResource extends Resource
{
    protected static ?string $model            = SystemEvent::class;
    protected static ?string $navigationIcon   = 'heroicon-o-bug-ant';
    protected static ?string $navigationLabel  = 'Eventos';
    protected static ?string $navigationGroup  = 'Monitoreo';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Evento';
    protected static ?string $pluralModelLabel = 'Eventos del Sistema';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'error'       => 'Error',
                        'warning'     => 'Advertencia',
                        'info'        => 'Información',
                        'job_fallido' => 'Job Fallido',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('modulo')
                    ->label('Módulo')
                    ->placeholder('ej. contabilidad, logistica, mailing'),

                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('mensaje')
                    ->label('Mensaje')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('contexto')
                    ->label('Contexto adicional')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\IconColumn::make('resuelto')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->width('40px'),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'danger'  => fn ($state) => in_array($state, ['error', 'job_fallido']),
                        'warning' => 'warning',
                        'info'    => 'info',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'error'       => 'Error',
                        'warning'     => 'Advertencia',
                        'info'        => 'Info',
                        'job_fallido' => 'Job Fallido',
                        default       => $state,
                    }),

                Tables\Columns\TextColumn::make('empresa.name')
                    ->label('Empresa')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('modulo')
                    ->label('Módulo')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (SystemEvent $r): string => $r->titulo),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ocurrido')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resuelto_at')
                    ->label('Resuelto')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'error'       => 'Error',
                        'warning'     => 'Advertencia',
                        'info'        => 'Información',
                        'job_fallido' => 'Job Fallido',
                    ]),

                Tables\Filters\TernaryFilter::make('resuelto')
                    ->label('Estado')
                    ->trueLabel('Solo resueltos')
                    ->falseLabel('Solo pendientes')
                    ->placeholder('Todos'),

                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('modulo')
                    ->options(fn () => SystemEvent::whereNotNull('modulo')->distinct()->pluck('modulo', 'modulo')->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('resolver')
                    ->label('Resolver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (SystemEvent $r): bool => ! $r->resuelto)
                    ->requiresConfirmation()
                    ->action(function (SystemEvent $record): void {
                        $record->resolver();
                        Notification::make()->title('Evento marcado como resuelto')->success()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resolver_todos')
                    ->label('Marcar resueltos')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records): void {
                        $records->each(fn ($r) => $r->resolver());
                        Notification::make()->title('Eventos resueltos')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSystemEvents::route('/'),
            'create' => Pages\CreateSystemEvent::route('/create'),
            'view'   => Pages\ViewSystemEvent::route('/{record}'),
        ];
    }
}
