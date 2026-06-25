<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmpresaServiciosResource\RelationManagers\UsuariosAccesoRelationManager;
use App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;
use App\Models\Empresa;
use App\Services\EmpresaStatsService;
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
    protected static ?string $navigationGroup   = 'Operaciones';
    protected static ?int    $navigationSort    = 1;
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
                    Forms\Components\Toggle::make('servicio_cms_activo')
                        ->label('CMS')
                        ->helperText('Desactivar oculta todos los módulos CMS del panel de la empresa.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $threshold = now()->subMinutes(5)->timestamp;

        return $table
            // Query optimizada: un solo JOIN resuelve sesiones activas y último login (antes N+1)
            ->query(
                app(\App\Services\EmpresaStatsService::class)
                    ->empresasConActividad()
                    ->withCount('users')
            )
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
                    ->badge()
                    ->color('gray'),

                // Columna de módulos activos — íconos ●◑○ por módulo (9 puntos)
                Tables\Columns\TextColumn::make('modulos_activos')
                    ->label('Módulos')
                    ->html()
                    ->state(function (Empresa $record): string {
                        $modulos = config('erp_features', []);
                        $dots = [];
                        foreach ($modulos as $key => $cfg) {
                            $status = $record->moduleStatus($key);
                            [$dot, $color, $title] = match ($status) {
                                'complete' => ['●', '#10b981', $cfg['label'] . ': activo'],
                                'partial'  => ['◑', '#f59e0b', $cfg['label'] . ': parcial'],
                                default    => ['○', '#d1d5db', $cfg['label'] . ': inactivo'],
                            };
                            $dots[] = '<span title="' . e($title) . '" style="color:' . $color . ';font-size:14px;line-height:1;">' . $dot . '</span>';
                        }
                        return '<span style="display:flex;gap:2px;align-items:center;">' . implode('', $dots) . '</span>';
                    }),

                // Sesiones activas — viene del JOIN optimizado, sin query extra
                Tables\Columns\TextColumn::make('online_count')
                    ->label('Online')
                    ->state(fn (Empresa $record): string =>
                        ($record->online_count ?? 0) > 0
                            ? "{$record->online_count} activo(s)"
                            : '—'
                    )
                    ->badge()
                    ->color(fn (Empresa $record): string =>
                        ($record->online_count ?? 0) > 0 ? 'success' : 'gray'
                    ),

                // Último login — viene del JOIN, sin query extra
                Tables\Columns\TextColumn::make('ultimo_login_at')
                    ->label('Último login')
                    ->state(fn (Empresa $record): string =>
                        $record->ultimo_login_at
                            ? \Carbon\Carbon::parse($record->ultimo_login_at)->diffForHumans()
                            : 'Nunca'
                    )
                    ->color('gray'),

                Tables\Columns\IconColumn::make('servicio_mailing_activo')
                    ->label('Mailing')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-envelope'),

                Tables\Columns\IconColumn::make('servicio_cms_activo')
                    ->label('CMS')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-globe-alt'),

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

                Tables\Filters\Filter::make('con_mailing')
                    ->label('Con Mailing activo')
                    ->query(fn ($query) => $query->whereRaw(
                        "features @> '{\"marketing\":{\"mailing\":{\"activo\":true}}}'::jsonb"
                    )),

                Tables\Filters\Filter::make('con_cms')
                    ->label('Con CMS activo')
                    ->query(fn ($query) => $query->whereRaw(
                        "features @> '{\"marketing\":{\"cms\":{\"activo\":true}}}'::jsonb"
                    )),

                Tables\Filters\Filter::make('con_logistica')
                    ->label('Con Logística activa')
                    ->query(fn ($query) => $query->whereRaw(
                        "features @> '{\"logistica\":{\"activo\":true}}'::jsonb"
                    )),
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

                Tables\Actions\Action::make('toggle_cms')
                    ->label(fn (Empresa $r): string => $r->servicio_cms_activo ? 'Suspender CMS' : 'Activar CMS')
                    ->icon('heroicon-o-globe-alt')
                    ->color(fn (Empresa $r): string => $r->servicio_cms_activo ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Empresa $r): string => $r->servicio_cms_activo ? 'Suspender servicio CMS' : 'Activar servicio CMS')
                    ->modalDescription(fn (Empresa $r): string => $r->servicio_cms_activo
                        ? 'El módulo CMS dejará de ser visible para los usuarios de esta empresa.'
                        : 'El módulo CMS volverá a estar disponible para los usuarios de esta empresa.')
                    ->action(function (Empresa $record): void {
                        $record->update(['servicio_cms_activo' => ! $record->servicio_cms_activo]);
                        Notification::make()
                            ->title($record->servicio_cms_activo ? 'CMS activado' : 'CMS suspendido')
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

                Tables\Actions\Action::make('gestionar_modulos')
                    ->label('Módulos')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('primary')
                    ->url(fn (Empresa $record): string =>
                        static::getUrl('features', ['record' => $record])
                    ),

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
            'index'    => Pages\ListEmpresaServicios::route('/'),
            'create'   => Pages\CreateEmpresaServicios::route('/create'),
            'edit'     => Pages\EditEmpresaServicios::route('/{record}/edit'),
            'features' => Pages\GestionFeaturesPage::route('/{record}/features'),
        ];
    }
}
