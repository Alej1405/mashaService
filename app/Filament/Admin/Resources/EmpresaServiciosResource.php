<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmpresaServiciosResource\RelationManagers\UsuariosAccesoRelationManager;
use App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;
use App\Models\Empresa;
use App\Models\MailCampaign;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use App\Models\MailTemplate;
use App\Models\ServicePlan;
use App\Services\EmpresaFeaturesService;
use App\Services\EmpresaStatsService;
use App\Shared\Actions\AplicarPlanAEmpresa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class EmpresaServiciosResource extends Resource
{
    protected static ?string $model            = Empresa::class;
    protected static ?string $navigationIcon   = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel  = 'Empresas';
    protected static ?string $navigationGroup  = 'Clientes';
    protected static ?int    $navigationSort   = 1;
    protected static ?string $slug             = 'empresas';
    protected static ?string $modelLabel       = 'Empresa';
    protected static ?string $pluralModelLabel = 'Empresas';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        $catalogo  = config('erp_features', []);

        // Un solo query para todos los planes — reutilizado en closures (sin N+1)
        $planesMap = ServicePlan::orderBy('sort_order')
            ->get(['key', 'modules_template'])
            ->keyBy('key')
            ->map(fn ($p) => $p->modules_template ?? [])
            ->toArray();

        return $form->schema([
            Forms\Components\Tabs::make('empresa_tabs')
                ->tabs([

                    // ── Tab 1: Empresa ─────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Empresa')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Forms\Components\Toggle::make('activo')
                                ->label('Empresa activa')
                                ->helperText('Desactivar bloquea el acceso de todos los usuarios de esta empresa.')
                                ->onColor('success')
                                ->columnSpanFull(),

                            Forms\Components\Section::make('Información básica')
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

                                    Forms\Components\TextInput::make('website_url')
                                        ->label('Sitio web')
                                        ->url()
                                        ->placeholder('https://www.ejemplo.com')
                                        ->maxLength(255),
                                ]),

                            Forms\Components\Section::make('Identificación y datos legales')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('tipo_persona')
                                        ->label('Tipo de persona')
                                        ->options([
                                            'natural'  => 'Persona Natural',
                                            'juridica' => 'Persona Jurídica',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Forms\Components\Select::make('tipo_identificacion')
                                        ->label('Tipo de identificación')
                                        ->options([
                                            'ruc'       => 'RUC',
                                            'cedula'    => 'Cédula de Identidad',
                                            'pasaporte' => 'Pasaporte',
                                        ])
                                        ->required()
                                        ->native(false)
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
                        ]),

                    // ── Tab 2: Plan y Módulos ───────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Plan y Módulos')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([

                            // Plan activo + preview de módulos del plan
                            Forms\Components\Section::make('Suscripción')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('plan')
                                        ->label('Plan activo')
                                        ->options(
                                            ServicePlan::orderBy('sort_order')
                                                ->pluck('nombre', 'key')
                                                ->toArray()
                                        )
                                        ->default('pro')
                                        ->required()
                                        ->live()
                                        ->helperText('Cambiar el plan aquí solo actualiza la etiqueta. Usa "Aplicar template" para sincronizar los módulos.'),

                                    // Módulos que incluye el plan seleccionado
                                    Forms\Components\Placeholder::make('modulos_del_plan')
                                        ->label('Módulos incluidos en este plan')
                                        ->content(function (Get $get) use ($planesMap, $catalogo): HtmlString {
                                            $planKey  = $get('plan') ?? '';
                                            $template = $planesMap[$planKey] ?? [];

                                            if (empty($template)) {
                                                return new HtmlString(
                                                    '<span style="font-size:13px;color:#94a3b8;">Selecciona un plan para ver sus módulos.</span>'
                                                );
                                            }

                                            $activos = array_keys(array_filter($template, fn ($v) => $v === true));

                                            if (empty($activos)) {
                                                return new HtmlString(
                                                    '<span style="font-size:13px;color:#94a3b8;">Este plan no incluye módulos de ERP.</span>'
                                                );
                                            }

                                            $badges = collect($activos)
                                                ->map(fn ($k) => sprintf(
                                                    '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:6px;font-size:11px;font-weight:600;background:#fef3c7;color:#92400e;margin:2px 2px 2px 0;">%s</span>',
                                                    e($catalogo[$k]['label'] ?? $k)
                                                ))
                                                ->join('');

                                            return new HtmlString(
                                                '<div style="display:flex;flex-wrap:wrap;gap:2px;padding-top:2px;">' . $badges . '</div>'
                                            );
                                        }),
                                ]),

                            // Grid de módulos: 9 filas con toggle individual
                            Forms\Components\Section::make('Módulos de la empresa')
                                ->description('Activa o desactiva módulos de forma individual. Un módulo puede estar activo aunque el plan base no lo incluya.')
                                ->schema(
                                    collect($catalogo)
                                        ->map(fn (array $cfg, string $key) =>
                                            Forms\Components\Grid::make(12)
                                                ->schema([
                                                    Forms\Components\Placeholder::make("mod_card_{$key}")
                                                        ->label('')
                                                        ->columnSpan(11)
                                                        ->content(function (Get $get) use ($key, $cfg, $planesMap): \Illuminate\Contracts\View\View {
                                                            $planKey = $get('plan') ?? '';
                                                            $enPlan  = (bool) ($planesMap[$planKey][$key] ?? false);
                                                            $activo  = (bool) ($get("features.{$key}.activo") ?? false);

                                                            return view('filament.admin.empresa-module-card', [
                                                                'icon'        => $cfg['icon'],
                                                                'label'       => $cfg['label'],
                                                                'color'       => $cfg['color'],
                                                                'descripcion' => $cfg['descripcion'],
                                                                'badgeType'   => match (true) {
                                                                    $activo && $enPlan  => 'plan',
                                                                    $activo && !$enPlan => 'adicional',
                                                                    default             => 'inactivo',
                                                                },
                                                            ]);
                                                        }),

                                                    Forms\Components\Toggle::make("features.{$key}.activo")
                                                        ->label('')
                                                        ->columnSpan(1)
                                                        ->onColor('success')
                                                        ->offColor('gray')
                                                        ->inline(false)
                                                        ->live()
                                                        ->afterStateUpdated(function (bool $state, $livewire) use ($key): void {
                                                            $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                                            if ($record?->exists) {
                                                                app(EmpresaFeaturesService::class)->setModule($record, $key, $state);
                                                            }
                                                        }),
                                                ])
                                                ->extraAttributes(['class' => 'py-2 border-b border-slate-100'])
                                        )
                                        ->values()
                                        ->toArray()
                                )
                                ->columns(1),
                        ]),

                    // ── Tab 3: Configuración técnica ────────────────────────────
                    Forms\Components\Tabs\Tab::make('Configuración')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Section::make('Servicio de correo')
                                ->description('Credenciales Mailgun para el envío de campañas y notificaciones de esta empresa.')
                                ->icon('heroicon-o-envelope')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('servicio_mailing_activo')
                                        ->label('Mailing activo')
                                        ->helperText('Desactivar oculta todo el módulo Mailing para los usuarios.')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('mailgun_api_key')
                                        ->label('API Key de Mailgun')
                                        ->password()
                                        ->revealable()
                                        ->placeholder('key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                        ->helperText('Panel Mailgun → Account → API Keys.')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('mailgun_domain')
                                        ->label('Dominio verificado')
                                        ->placeholder('mg.tudominio.com')
                                        ->helperText('El dominio verificado en tu cuenta Mailgun.')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('mailgun_from_email')
                                        ->label('Email de origen')
                                        ->email()
                                        ->placeholder('no-reply@tudominio.com')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('mailgun_from_name')
                                        ->label('Nombre de origen')
                                        ->placeholder('Mi Empresa')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('mailing_monthly_limit')
                                        ->label('Límite mensual de envíos')
                                        ->numeric()
                                        ->default(3000)
                                        ->minValue(0)
                                        ->helperText('Máximo de correos permitidos por período.'),

                                    Forms\Components\TextInput::make('mailing_billing_day')
                                        ->label('Día de renovación (1–28)')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->maxValue(28)
                                        ->helperText('Día del mes en que se restablece la cuota.'),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                app(EmpresaStatsService::class)
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

                Tables\Columns\TextColumn::make('modulos_activos')
                    ->label('Módulos')
                    ->html()
                    ->state(function (Empresa $record): string {
                        $modulos = config('erp_features', []);
                        $dots    = [];
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

                Tables\Actions\Action::make('limpiar_mailing')
                    ->label('Limpiar mailing')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Limpiar datos de Mailing')
                    ->modalDescription('Se eliminarán todos los contactos, grupos, plantillas y campañas. No se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, limpiar todo')
                    ->action(function (Empresa $record): void {
                        MailCampaign::where('empresa_id', $record->id)->delete();
                        MailingContact::where('empresa_id', $record->id)->delete();
                        MailingGroup::where('empresa_id', $record->id)->delete();
                        MailTemplate::where('empresa_id', $record->id)->delete();

                        Notification::make()
                            ->title('Datos de Mailing eliminados')
                            ->body("Se limpiaron los datos de mailing de {$record->name}.")
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
            'index'    => Pages\ListEmpresaServicios::route('/'),
            'create'   => Pages\CreateEmpresaServicios::route('/create'),
            'edit'     => Pages\EditEmpresaServicios::route('/{record}/edit'),
            'features' => Pages\GestionFeaturesPage::route('/{record}/features'),
        ];
    }
}
