<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpresaResource\RelationManagers\UsuariosAccesoRelationManager;
use App\Filament\Resources\EmpresaServiciosResource\Pages;
use App\Models\Empresa;
use App\Models\MailCampaign;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use App\Models\MailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmpresaServiciosResource extends Resource
{
    protected static ?string $model             = Empresa::class;
    protected static ?string $navigationIcon    = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel   = 'Empresas';
    protected static ?string $navigationGroup   = 'Servicios';
    protected static ?int    $navigationSort    = 2;
    protected static ?string $slug              = 'servicios-empresas';
    protected static ?string $modelLabel        = 'Empresa';
    protected static ?string $pluralModelLabel  = 'Empresas';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Información Básica')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre de la empresa')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) =>
                            $set('slug', \Illuminate\Support\Str::slug($state))
                        )
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL amigable (slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->dehydrated(),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Identificación y Datos Legales')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('tipo_persona')
                        ->label('Tipo de persona')
                        ->options([
                            'natural'  => 'Persona Natural',
                            'juridica' => 'Persona Jurídica',
                        ])
                        ->required(),

                    Forms\Components\Select::make('tipo_identificacion')
                        ->label('Tipo de identificación')
                        ->options([
                            'ruc'       => 'RUC',
                            'cedula'    => 'Cédula de Identidad',
                            'pasaporte' => 'Pasaporte',
                        ])
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('numero_identificacion')
                        ->label('Número de identificación')
                        ->required()
                        ->numeric()
                        ->minLength(fn (Get $get): int => match ($get('tipo_identificacion')) {
                            'ruc'    => 13,
                            'cedula' => 10,
                            default  => 1,
                        })
                        ->maxLength(fn (Get $get): int => match ($get('tipo_identificacion')) {
                            'ruc'    => 13,
                            'cedula' => 10,
                            default  => 20,
                        })
                        ->hint(fn (Get $get): string => match ($get('tipo_identificacion')) {
                            'ruc'    => '13 dígitos',
                            'cedula' => '10 dígitos',
                            default  => '',
                        }),

                    Forms\Components\TextInput::make('direccion')
                        ->label('Dirección')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('actividad_economica')
                        ->label('¿A qué se dedica la empresa?')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Estado del Servicio')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('activo')
                        ->label('Empresa activa')
                        ->helperText('Desactivar bloquea el acceso al panel de la empresa.')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('plan')
                        ->label('Plan de suscripción')
                        ->options([
                            'basic'      => 'Basic — Solo Mailing',
                            'pro'        => 'Pro — ERP Completo',
                            'enterprise' => 'Enterprise — Todo incluido',
                        ])
                        ->default('pro')
                        ->required()
                        ->native(false),
                ]),

            Forms\Components\Section::make('Módulos habilitados')
                ->description('Active los módulos que correspondan al tipo de operación de la empresa.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('tipo_operacion_productos')->label('Productos'),
                    Forms\Components\Toggle::make('tipo_operacion_servicios')->label('Servicios'),
                    Forms\Components\Toggle::make('tipo_operacion_manufactura')->label('Manufactura'),
                    Forms\Components\Toggle::make('tiene_logistica')->label('Logística'),
                    Forms\Components\Toggle::make('tiene_comercio_exterior')->label('Comercio Exterior'),
                ]),

            Forms\Components\Section::make('Servicios adicionales')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('servicio_mailing_activo')
                        ->label('Mailing')
                        ->helperText('Desactivar oculta todo el módulo Mailing del panel de la empresa.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Empresa::query()->withCount('users')->with(['users' => fn ($q) => $q->select('id', 'empresa_id', 'last_login_at')]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan')
                    ->colors([
                        'gray'    => 'basic',
                        'primary' => 'pro',
                        'warning' => 'enterprise',
                    ]),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sesiones_activas')
                    ->label('Online ahora')
                    ->state(function (Empresa $record): string {
                        $userIds = $record->users()->pluck('id');
                        $online  = \DB::table('sessions')
                            ->whereIn('user_id', $userIds)
                            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                            ->count();
                        return $online > 0 ? "{$online} activo(s)" : '—';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === '—' ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('ultimo_login')
                    ->label('Último login')
                    ->state(function (Empresa $record): string {
                        $last = $record->users()->whereNotNull('last_login_at')->max('last_login_at');
                        return $last ? \Carbon\Carbon::parse($last)->diffForHumans() : 'Nunca';
                    })
                    ->color('gray'),

                Tables\Columns\IconColumn::make('servicio_mailing_activo')
                    ->label('Mailing')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(['basic' => 'Basic', 'pro' => 'Pro', 'enterprise' => 'Enterprise']),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_activo')
                    ->label(fn (Empresa $r): string => $r->activo ? 'Suspender' : 'Activar')
                    ->icon(fn (Empresa $r): string => $r->activo ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (Empresa $r): string => $r->activo ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Empresa $record): void {
                        $record->update(['activo' => ! $record->activo]);
                        Notification::make()
                            ->title($record->activo ? 'Empresa activada' : 'Empresa suspendida')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('cambiar_plan')
                    ->label('Cambiar plan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('plan')
                            ->label('Nuevo plan')
                            ->options(['basic' => 'Basic', 'pro' => 'Pro', 'enterprise' => 'Enterprise'])
                            ->required(),
                    ])
                    ->action(function (Empresa $record, array $data): void {
                        $record->update(['plan' => $data['plan']]);
                        Notification::make()->title('Plan actualizado')->success()->send();
                    }),

                Tables\Actions\Action::make('toggle_mailing')
                    ->label(fn (Empresa $r): string => $r->servicio_mailing_activo ? 'Suspender Mailing' : 'Activar Mailing')
                    ->icon('heroicon-o-envelope')
                    ->color(fn (Empresa $r): string => $r->servicio_mailing_activo ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Empresa $r): string => $r->servicio_mailing_activo ? 'Suspender servicio Mailing' : 'Activar servicio Mailing')
                    ->modalDescription(fn (Empresa $r): string => $r->servicio_mailing_activo
                        ? 'El módulo Mailing dejará de ser visible para los usuarios de esta empresa.'
                        : 'El módulo Mailing volverá a estar disponible para los usuarios de esta empresa.')
                    ->action(function (Empresa $record): void {
                        $record->update(['servicio_mailing_activo' => ! $record->servicio_mailing_activo]);
                        Notification::make()
                            ->title($record->servicio_mailing_activo ? 'Mailing activado' : 'Mailing suspendido')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('limpiar_mailing')
                    ->label('Limpiar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Limpiar datos de Mailing')
                    ->modalDescription('Se eliminarán todos los contactos, grupos, plantillas y campañas de esta empresa. Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, limpiar todo')
                    ->action(function (Empresa $record): void {
                        MailCampaign::where('empresa_id', $record->id)->delete();
                        MailingContact::where('empresa_id', $record->id)->delete();
                        MailingGroup::where('empresa_id', $record->id)->delete();
                        MailTemplate::where('empresa_id', $record->id)->delete();

                        Notification::make()
                            ->title('Datos de Mailing eliminados')
                            ->body("Se limpiaron todos los datos de mailing de {$record->name}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activar')
                    ->label('Activar seleccionadas')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn ($records) => $records->each->update(['activo' => true]))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('suspender')
                    ->label('Suspender seleccionadas')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['activo' => false]))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsuariosAccesoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmpresaServicios::route('/'),
            'create' => Pages\CreateEmpresaServicios::route('/create'),
            'edit'   => Pages\EditEmpresaServicios::route('/{record}/edit'),
        ];
    }
}
