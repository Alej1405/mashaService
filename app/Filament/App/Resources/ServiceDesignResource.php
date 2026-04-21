<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ServiceDesignResource\Pages;
use App\Models\ServiceChargeConfig;
use App\Models\ServiceDesign;
use App\Models\ServiceSimulation;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ServiceDesignResource extends Resource
{
    protected static ?string $model = ServiceDesign::class;

    protected static ?string $tenantRelationshipName = 'serviceDesigns';

    protected static ?string $navigationIcon   = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel  = 'Diseño de Servicios';
    protected static ?string $navigationGroup  = 'Diseño de Producto';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Diseño de Servicio';
    protected static ?string $pluralModelLabel = 'Diseño de Servicios';

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ══════════════════════════════════════════════════════════════════════
    // FORMULARIO
    // ══════════════════════════════════════════════════════════════════════
    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make()
                ->tabs([

                    // ─────────────────────────────────────────────────────
                    // TAB 1 — INFORMACIÓN GENERAL
                    // ─────────────────────────────────────────────────────
                    Tab::make('Información General')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            TextInput::make('nombre')
                                ->label('Nombre del Servicio')
                                ->required()
                                ->maxLength(150)
                                ->columnSpan(2),

                            Select::make('categoria')
                                ->label('Categoría')
                                ->options([
                                    'consultoria'   => 'Consultoría',
                                    'formacion'     => 'Formación / Capacitación',
                                    'mantenimiento' => 'Mantenimiento',
                                    'diseno'        => 'Diseño / Creatividad',
                                    'software'      => 'Desarrollo de Software',
                                    'salud'         => 'Salud / Bienestar',
                                    'legal'         => 'Legal / Jurídico',
                                    'contabilidad'  => 'Contabilidad / Finanzas',
                                    'marketing'     => 'Marketing / Publicidad',
                                    'logistica'     => 'Logística / Transporte',
                                    'construccion'  => 'Construcción / Arquitectura',
                                    'otro'          => 'Otro',
                                ])
                                ->searchable()
                                ->nullable()
                                ->columnSpan(1),

                            Toggle::make('activo')
                                ->label('Activo')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),
                            Toggle::make('publicado_catalogo')
                                ->label('Publicar en catálogo web')
                                ->helperText('Aparece en la API pública del sitio web y ecommerce.')
                                ->default(false)
                                ->inline(false)
                                ->columnSpan(1),

                            Toggle::make('tiene_multiples_paquetes')
                                ->label('¿Tiene múltiples paquetes?')
                                ->helperText('Ej: Básico, Estándar, Premium. Si ofreces el servicio en una sola modalidad, deja esto desactivado.')
                                ->default(false)
                                ->live()
                                ->inline(false)
                                ->columnSpan(2),

                            Textarea::make('descripcion_servicio')
                                ->label('Descripción del Servicio')
                                ->rows(3)
                                ->placeholder('¿Qué hace exactamente este servicio? ¿A quién va dirigido?')
                                ->columnSpanFull(),

                            RichEditor::make('propuesta_valor')
                                ->label('Propuesta de Valor')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'h2', 'h3'])
                                ->columnSpanFull(),

                            Textarea::make('notas_estrategicas')
                                ->label('Notas Estratégicas')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columns(4),

                    // ─────────────────────────────────────────────────────
                    // TAB 2 — PAQUETES DEL SERVICIO
                    // ─────────────────────────────────────────────────────
                    Tab::make('Paquetes del Servicio')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Repeater::make('packages')
                                ->relationship()
                                ->label(fn (callable $get) => $get('tiene_multiples_paquetes')
                                    ? 'Paquetes del Servicio'
                                    : 'Modalidad del Servicio')
                                ->maxItems(fn (callable $get) => $get('tiene_multiples_paquetes') ? null : 1)
                                ->addActionLabel(fn (callable $get) => $get('tiene_multiples_paquetes')
                                    ? '+ Agregar paquete'
                                    : '+ Definir modalidad')
                                ->defaultItems(0)
                                ->schema([
                                    TextInput::make('nombre')
                                        ->label('Nombre del Paquete')
                                        ->required(fn (callable $get) => (bool) $get('../../tiene_multiples_paquetes'))
                                        ->placeholder('Básico, Estándar, Premium...')
                                        ->visible(fn (callable $get) => (bool) $get('../../tiene_multiples_paquetes'))
                                        ->columnSpan(3),

                                    TextInput::make('duracion_estimada')
                                        ->label('Duración estimada')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->placeholder('Ej: 2')
                                        ->columnSpan(1),

                                    Select::make('duracion_unidad')
                                        ->label('Unidad de duración')
                                        ->options([
                                            'minutos' => 'Minutos',
                                            'horas'   => 'Horas',
                                            'dias'    => 'Días',
                                            'semanas' => 'Semanas',
                                            'meses'   => 'Meses',
                                        ])
                                        ->default('horas')
                                        ->columnSpan(1),

                                    Toggle::make('activo')
                                        ->label('Activo')
                                        ->default(true)
                                        ->inline(false)
                                        ->visible(fn (callable $get) => (bool) $get('../../tiene_multiples_paquetes'))
                                        ->columnSpan(1),

                                    // ── Sección de precios y márgenes ─────────────────
                                    Section::make('Precio y Margen Objetivo')
                                        ->description('Define la base de cobro, el precio estimado y el margen que deseas obtener con este paquete.')
                                        ->schema([
                                            // ── Base de cobro ──────────────────────────
                                            Select::make('base_cobro')
                                                ->label('¿En base a qué se cobra?')
                                                ->options([
                                                    'fijo'      => 'Precio fijo (tarifa única)',
                                                    'peso'      => 'Por peso',
                                                    'volumen'   => 'Por volumen',
                                                    'distancia' => 'Por distancia / recorrido',
                                                    'tiempo'    => 'Por tiempo (hora/día)',
                                                    'paginas'   => 'Por páginas / documentos',
                                                    'tramite'   => 'Por trámite / proceso',
                                                    'sesion'    => 'Por sesión / consulta',
                                                    'unidad'    => 'Por unidad / ítem',
                                                    'otro'      => 'Otro',
                                                ])
                                                ->default('fijo')
                                                ->required()
                                                ->live()
                                                ->columnSpan(2),

                                            // ── Unidad según base de cobro ─────────────
                                            Select::make('unidad_cobro')
                                                ->label('Unidad de medida')
                                                ->options(fn (callable $get) => match ($get('base_cobro')) {
                                                    'peso'      => [
                                                        'kg' => 'Kilogramo (kg)',
                                                        'g'  => 'Gramo (g)',
                                                        'lb' => 'Libra (lb)',
                                                        't'  => 'Tonelada (t)',
                                                        'oz' => 'Onza (oz)',
                                                    ],
                                                    'volumen'   => [
                                                        'l'   => 'Litro (l)',
                                                        'ml'  => 'Mililitro (ml)',
                                                        'm3'  => 'Metro cúbico (m³)',
                                                        'cm3' => 'Centímetro cúbico (cm³)',
                                                        'gal' => 'Galón (gal)',
                                                    ],
                                                    'distancia' => [
                                                        'km' => 'Kilómetro (km)',
                                                        'm'  => 'Metro (m)',
                                                        'mi' => 'Milla (mi)',
                                                    ],
                                                    'tiempo'    => [
                                                        'h'   => 'Hora (h)',
                                                        'min' => 'Minuto (min)',
                                                        'dia' => 'Día',
                                                        'sem' => 'Semana',
                                                        'mes' => 'Mes',
                                                    ],
                                                    'paginas'   => [
                                                        'pag'  => 'Página',
                                                        'hoja' => 'Hoja',
                                                        'doc'  => 'Documento',
                                                    ],
                                                    'tramite'   => ['tramite' => 'Trámite', 'proceso' => 'Proceso', 'expediente' => 'Expediente'],
                                                    'sesion'    => ['sesion' => 'Sesión', 'consulta' => 'Consulta', 'clase' => 'Clase'],
                                                    'unidad'    => ['unidad' => 'Unidad', 'item' => 'Ítem', 'pieza' => 'Pieza'],
                                                    default     => ['otro' => 'Otro'],
                                                })
                                                ->visible(fn (callable $get) => $get('base_cobro') !== 'fijo')
                                                ->nullable()
                                                ->live()
                                                ->columnSpan(2),

                                            // ── Costo base ─────────────────────────────
                                            TextInput::make('costo_base')
                                                ->label(fn (callable $get) => 'Costo por ' . match ($get('base_cobro')) {
                                                    'peso'      => ($get('unidad_cobro') ?: 'unidad de peso'),
                                                    'volumen'   => ($get('unidad_cobro') ?: 'unidad de volumen'),
                                                    'distancia' => ($get('unidad_cobro') ?: 'km'),
                                                    'tiempo'    => ($get('unidad_cobro') ?: 'hora'),
                                                    'paginas'   => ($get('unidad_cobro') ?: 'página'),
                                                    'tramite'   => ($get('unidad_cobro') ?: 'trámite'),
                                                    'sesion'    => ($get('unidad_cobro') ?: 'sesión'),
                                                    'unidad'    => ($get('unidad_cobro') ?: 'unidad'),
                                                    default     => 'unidad',
                                                })
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->placeholder('Ej: 4.00')
                                                ->helperText('Costo real → se calcula el precio con el margen.')
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $costo  = (float) ($state ?? 0);
                                                    $margen = (float) ($get('margen_objetivo') ?? 0);
                                                    if ($costo > 0 && $margen > 0 && $margen < 100) {
                                                        $set('precio_estimado', round($costo / (1 - $margen / 100), 4));
                                                    }
                                                })
                                                ->columnSpan(2),

                                            // ── Precio y margen ────────────────────────
                                            TextInput::make('margen_objetivo')
                                                ->label('Margen (%)')
                                                ->numeric()
                                                ->suffix('%')
                                                ->live(onBlur: true)
                                                ->placeholder('Ej: 40')
                                                ->helperText('Margen → recalcula el precio.')
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $margen = (float) ($state ?? 0);
                                                    $costo  = (float) ($get('costo_base') ?? 0);
                                                    if ($costo > 0 && $margen > 0 && $margen < 100) {
                                                        $set('precio_estimado', round($costo / (1 - $margen / 100), 4));
                                                    }
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('precio_estimado')
                                                ->label(fn (callable $get) => 'Precio ' . match ($get('base_cobro')) {
                                                    'peso'    => '/ ' . ($get('unidad_cobro') ?: 'unidad'),
                                                    'volumen' => '/ ' . ($get('unidad_cobro') ?: 'unidad'),
                                                    'fijo'    => 'del servicio',
                                                    default   => '/ unidad',
                                                } . ' (sin IVA)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->placeholder('Ej: 6.75')
                                                ->helperText('Precio → recalcula el margen.')
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $precio = (float) ($state ?? 0);
                                                    $costo  = (float) ($get('costo_base') ?? 0);
                                                    if ($precio > 0 && $costo > 0 && $precio > $costo) {
                                                        $set('margen_objetivo', round((($precio - $costo) / $precio) * 100, 2));
                                                    }
                                                })
                                                ->columnSpan(1),

                                            // ── Cargos adicionales ─────────────────────
                                            Select::make('chargeConfigs')
                                                ->label('Cargos adicionales aplicables')
                                                ->relationship('chargeConfigs', 'nombre')
                                                ->multiple()
                                                ->preload()
                                                ->searchable()
                                                ->getOptionLabelFromRecordUsing(fn (ServiceChargeConfig $r) =>
                                                    $r->nombre
                                                    . ' — $' . number_format((float) $r->monto, 2)
                                                    . ' (' . ($r->tipo === 'peso' ? 'por kg' : 'por trámite') . ')'
                                                    . ' · IVA ' . $r->iva_pct . '%'
                                                )
                                                ->createOptionModalHeading('Nuevo cargo adicional')
                                                ->createOptionForm([
                                                    TextInput::make('nombre')
                                                        ->label('Nombre del cargo')
                                                        ->required()
                                                        ->maxLength(150),
                                                    TextInput::make('monto')
                                                        ->label('Monto ($)')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->required(),
                                                    Select::make('tipo')
                                                        ->label('Tipo de cobro')
                                                        ->options([
                                                            'tramite' => 'Por trámite (monto fijo)',
                                                            'peso'    => 'Por peso (monto × kg)',
                                                        ])
                                                        ->default('tramite')
                                                        ->required(),
                                                    Select::make('iva_pct')
                                                        ->label('IVA')
                                                        ->options([
                                                            15 => '15% — Servicio',
                                                            0  => '0% — Impuesto / paso directo',
                                                        ])
                                                        ->default(15)
                                                        ->required(),
                                                ])
                                                ->createOptionUsing(function (array $data) {
                                                    $data['empresa_id'] = Filament::getTenant()->id;
                                                    $data['activo']     = true;
                                                    return ServiceChargeConfig::create($data)->id;
                                                })
                                                ->helperText('Selecciona cargos del catálogo o crea uno nuevo directamente.')
                                                ->columnSpanFull(),

                                            // ── Resumen visual ─────────────────────────
                                            Placeholder::make('_resumen_cobro')
                                                ->label('')
                                                ->columnSpanFull()
                                                ->visible(fn (callable $get) => (float) ($get('precio_estimado') ?? 0) > 0)
                                                ->content(function (callable $get) {
                                                    $base   = $get('base_cobro') ?? 'fijo';
                                                    $unidad = $get('unidad_cobro') ?? '';
                                                    $precio = (float) ($get('precio_estimado') ?? 0);
                                                    $costo  = (float) ($get('costo_base') ?? 0);
                                                    $iva    = round($precio * 1.15, 2);

                                                    // Margen real calculado desde precio y costo
                                                    $margenReal = ($precio > 0 && $costo > 0)
                                                        ? round((($precio - $costo) / $precio) * 100, 2)
                                                        : (float) ($get('margen_objetivo') ?? 0);

                                                    $utilidad = $precio - $costo;

                                                    $baseLabel = match ($base) {
                                                        'fijo'      => 'Precio fijo',
                                                        'peso'      => 'Por ' . ($unidad ?: 'unidad de peso'),
                                                        'volumen'   => 'Por ' . ($unidad ?: 'unidad de volumen'),
                                                        'distancia' => 'Por ' . ($unidad ?: 'km'),
                                                        'tiempo'    => 'Por ' . ($unidad ?: 'hora'),
                                                        'paginas'   => 'Por ' . ($unidad ?: 'página'),
                                                        'tramite'   => 'Por ' . ($unidad ?: 'trámite'),
                                                        'sesion'    => 'Por ' . ($unidad ?: 'sesión'),
                                                        'unidad'    => 'Por ' . ($unidad ?: 'unidad'),
                                                        default     => 'Por unidad',
                                                    };

                                                    $margenColor = $margenReal >= 30 ? '#16a34a' : ($margenReal >= 15 ? '#d97706' : '#dc2626');

                                                    $html = '<div style="display:flex;gap:1.5rem;align-items:center;padding:0.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.5rem;flex-wrap:wrap;">';
                                                    $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Base de cobro</span><br><strong style="color:#374151;">' . e($baseLabel) . '</strong></div>';
                                                    if ($costo > 0) {
                                                        $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Costo</span><br><strong style="color:#dc2626;">$ ' . number_format($costo, 4) . '</strong></div>';
                                                    }
                                                    $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Precio sin IVA</span><br><strong style="color:#15803d;">$ ' . number_format($precio, 4) . '</strong></div>';
                                                    $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">+ IVA 15%</span><br><strong style="color:#374151;">$ ' . number_format($iva, 2) . '</strong></div>';
                                                    if ($costo > 0) {
                                                        $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Utilidad / unidad</span><br><strong style="color:' . $margenColor . ';">$ ' . number_format($utilidad, 4) . '</strong></div>';
                                                    }
                                                    $html .= '<div><span style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Margen real</span><br><strong style="font-size:1.1rem;color:' . $margenColor . ';">' . number_format($margenReal, 1) . '%</strong></div>';
                                                    $html .= '</div>';
                                                    return new \Illuminate\Support\HtmlString($html);
                                                }),
                                        ])
                                        ->columns(4)
                                        ->columnSpanFull(),

                                    // ── Descripción detallada ─────────────────────────
                                    Section::make('Qué incluye este paquete')
                                        ->description('Detalla los entregables, incluidos y condiciones de este paquete.')
                                        ->schema([
                                            Textarea::make('descripcion')
                                                ->label('')
                                                ->rows(4)
                                                ->placeholder("• Sesión inicial de diagnóstico (1 hora)\n• 3 reuniones de seguimiento\n• Informe final en PDF\n• Soporte por email por 30 días")
                                                ->columnSpanFull(),
                                        ])
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ])
                                ->columns(6)
                                ->defaultItems(0)
                                ->itemLabel(function (array $state, callable $get): string {
                                    if (!$get('tiene_multiples_paquetes')) {
                                        return 'Modalidad del servicio';
                                    }
                                    $nombre = $state['nombre'] ?? 'Nuevo paquete';
                                    $precio = isset($state['precio_estimado']) && $state['precio_estimado'] > 0
                                        ? '  —  $ ' . number_format((float) $state['precio_estimado'], 2)
                                        : '';
                                    return $nombre . $precio;
                                })
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                    // ─────────────────────────────────────────────────────
                    // TAB 3 — PROCESO DE ENTREGA Y CAPACIDAD
                    // ─────────────────────────────────────────────────────
                    Tab::make('Proceso y Capacidad')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Repeater::make('deliverySteps')
                                ->relationship()
                                ->label('Pasos del Proceso de Entrega')
                                ->schema([
                                    TextInput::make('orden')
                                        ->label('Orden')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),
                                    TextInput::make('nombre')
                                        ->label('Nombre del Paso')
                                        ->required()
                                        ->placeholder('Diagnóstico, Propuesta, Ejecución, Entrega...')
                                        ->columnSpan(3),
                                    TextInput::make('tiempo_estimado_horas')
                                        ->label('Tiempo (h)')
                                        ->numeric()
                                        ->suffix('h')
                                        ->columnSpan(1),
                                    TextInput::make('responsable')
                                        ->label('Responsable')
                                        ->placeholder('Consultor, Técnico...')
                                        ->columnSpan(2),
                                    Textarea::make('descripcion')
                                        ->label('Descripción del Paso')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(7)
                                ->addActionLabel('+ Agregar paso')
                                ->defaultItems(0)
                                ->reorderable('orden')
                                ->orderColumn('orden')
                                ->columnSpanFull(),

                            // ── Capacidad ─────────────────────────────────────────
                            Section::make('Capacidad Operativa')
                                ->description('Define cuántas sesiones / clientes / horas puedes atender al mes.')
                                ->columns(5)
                                ->schema([
                                    Select::make('unidad_capacidad')
                                        ->label('Unidad de Capacidad')
                                        ->options([
                                            'sesion'    => 'Sesiones',
                                            'hora'      => 'Horas',
                                            'cliente'   => 'Clientes',
                                            'proyecto'  => 'Proyectos',
                                            'consulta'  => 'Consultas',
                                            'clase'     => 'Clases',
                                            'evento'    => 'Eventos',
                                        ])
                                        ->default('sesion')
                                        ->required()
                                        ->live()
                                        ->columnSpan(1),

                                    TextInput::make('capacidad_mensual')
                                        ->label(fn (callable $get) => 'Capacidad Mensual (' . match ($get('unidad_capacidad') ?? 'sesion') {
                                            'hora'     => 'horas',
                                            'cliente'  => 'clientes',
                                            'proyecto' => 'proyectos',
                                            'consulta' => 'consultas',
                                            'clase'    => 'clases',
                                            'evento'   => 'eventos',
                                            default    => 'sesiones',
                                        } . ')')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->placeholder('Ej: 80')
                                        ->columnSpan(1),

                                    TextInput::make('dias_laborales_mes')
                                        ->label('Días Laborales / Mes')
                                        ->numeric()
                                        ->default(22)
                                        ->live(onBlur: true)
                                        ->suffix('días')
                                        ->columnSpan(1),

                                    Placeholder::make('_capacidad_diaria')
                                        ->label('Capacidad Diaria')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $mensual = (float) ($get('capacidad_mensual') ?? 0);
                                            $dias    = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                            if ($mensual <= 0) return '—';
                                            $diaria = $mensual / $dias;
                                            $unidad = match ($get('unidad_capacidad') ?? 'sesion') {
                                                'hora'     => 'h/día',
                                                'cliente'  => 'cli/día',
                                                'proyecto' => 'proy/día',
                                                'consulta' => 'cons/día',
                                                'clase'    => 'cls/día',
                                                'evento'   => 'evt/día',
                                                default    => 'ses/día',
                                            };
                                            return new HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">'
                                                . number_format($diaria, 2) . ' ' . $unidad . '</span>'
                                            );
                                        }),
                                ]),

                            // ── Personal ──────────────────────────────────────────
                            Section::make('Personal del Servicio')
                                ->description('Personas necesarias para cubrir la capacidad operativa mensual.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('num_personas')
                                        ->label('Número de Personas')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->suffix('personas')
                                        ->columnSpan(1),

                                    TextInput::make('costo_persona_mes')
                                        ->label('Costo Mensual por Persona')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->columnSpan(1),

                                    Placeholder::make('_total_personal')
                                        ->label('Total Personal / Mes')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $personas = (float) ($get('num_personas') ?? 0);
                                            $costo    = (float) ($get('costo_persona_mes') ?? 0);
                                            $total    = $personas * $costo;
                                            return new HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">$ ' . number_format($total, 2) . '</span>'
                                            );
                                        }),

                                    Placeholder::make('_costo_personal_por_unidad')
                                        ->label('Personal por Unidad de Servicio')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $personas  = (float) ($get('num_personas') ?? 0);
                                            $costo     = (float) ($get('costo_persona_mes') ?? 0);
                                            $capacidad = (float) ($get('capacidad_mensual') ?? 0);
                                            if ($capacidad <= 0) return '—';
                                            $porUnidad = ($personas * $costo) / $capacidad;
                                            return new HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">$ ' . number_format($porUnidad, 4) . '</span>'
                                            );
                                        }),
                                ]),
                        ]),

                    // ─────────────────────────────────────────────────────
                    // TAB 4 — COSTOS OPERATIVOS
                    // ─────────────────────────────────────────────────────
                    Tab::make('Costos Operativos')
                        ->icon('heroicon-o-calculator')
                        ->schema([

                            Section::make('Otras Inversiones')
                                ->description('Gastos mensuales adicionales: marketing, herramientas, licencias, etc.')
                                ->schema([
                                    Repeater::make('indirectCosts')
                                        ->relationship()
                                        ->label('')
                                        ->schema([
                                            Select::make('tipo')
                                                ->label('Tipo')
                                                ->options([
                                                    'marketing'           => 'Marketing / Publicidad',
                                                    'software_licencias'  => 'Software / Licencias',
                                                    'arriendo'            => 'Arriendo / Espacio',
                                                    'capacitacion'        => 'Capacitación / Certificación',
                                                    'materiales'          => 'Materiales / Insumos',
                                                    'subcontratacion'     => 'Subcontratación',
                                                    'servicios_basicos'   => 'Servicios Básicos (luz, internet)',
                                                    'otro'                => 'Otro',
                                                ])
                                                ->required()
                                                ->live()
                                                ->columnSpan(2),
                                            TextInput::make('descripcion')
                                                ->label('Descripción')
                                                ->placeholder('Detalle opcional...')
                                                ->columnSpan(3),
                                            TextInput::make('monto_mensual')
                                                ->label('Monto')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->columnSpan(2),
                                            Radio::make('frecuencia')
                                                ->label('Frecuencia')
                                                ->options([
                                                    'semanal' => 'Semanal',
                                                    'mensual' => 'Mensual',
                                                    'unico'   => 'Un solo pago',
                                                ])
                                                ->default('mensual')
                                                ->inline()
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(7)
                                        ->addActionLabel('+ Agregar inversión')
                                        ->defaultItems(0)
                                        ->itemLabel(fn (array $state): ?string =>
                                            match ($state['tipo'] ?? null) {
                                                'marketing'          => 'Marketing / Publicidad',
                                                'software_licencias' => 'Software / Licencias',
                                                'arriendo'           => 'Arriendo / Espacio',
                                                'capacitacion'       => 'Capacitación',
                                                'materiales'         => 'Materiales',
                                                'subcontratacion'    => 'Subcontratación',
                                                'servicios_basicos'  => 'Servicios Básicos',
                                                'otro'               => 'Otro',
                                                default              => null,
                                            }
                                            . (isset($state['monto_mensual']) && $state['monto_mensual'] > 0
                                                ? '  —  $ ' . number_format((float) $state['monto_mensual'], 2)
                                                : '')
                                            . (isset($state['frecuencia'])
                                                ? '  (' . match ($state['frecuencia']) {
                                                    'semanal' => 'Semanal',
                                                    'mensual' => 'Mensual',
                                                    'unico'   => 'Un solo pago',
                                                    default   => '',
                                                } . ')'
                                                : '')
                                        )
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ]),

                            // ── Resumen de costos ────────────────────────────────
                            Section::make('Resumen de Costos Operativos')
                                ->schema([
                                    Placeholder::make('_resumen_costos')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(function (callable $get) {
                                            $capacidad = (float) ($get('capacidad_mensual') ?? 0);
                                            $personas  = (float) ($get('num_personas') ?? 0);
                                            $costoP    = (float) ($get('costo_persona_mes') ?? 0);
                                            $totalP    = $personas * $costoP;
                                            $otros     = $get('indirectCosts') ?? [];
                                            $unidad    = match ($get('unidad_capacidad') ?? 'sesion') {
                                                'hora'     => 'hora',
                                                'cliente'  => 'cliente',
                                                'proyecto' => 'proyecto',
                                                'consulta' => 'consulta',
                                                'clase'    => 'clase',
                                                'evento'   => 'evento',
                                                default    => 'sesión',
                                            };

                                            $frecLabel = fn (string $f): string => match ($f) {
                                                'semanal' => 'Semanal',
                                                'mensual' => 'Mensual',
                                                'unico'   => 'Un solo pago',
                                                default   => '—',
                                            };

                                            $tipoLabel = fn (string $t): string => match ($t) {
                                                'marketing'          => 'Marketing',
                                                'software_licencias' => 'Software / Licencias',
                                                'arriendo'           => 'Arriendo',
                                                'capacitacion'       => 'Capacitación',
                                                'materiales'         => 'Materiales',
                                                'subcontratacion'    => 'Subcontratación',
                                                'servicios_basicos'  => 'Servicios Básicos',
                                                'otro'               => 'Otro',
                                                default              => '—',
                                            };

                                            $rows     = '';
                                            $totalMes = 0;

                                            // Fila personal
                                            if ($totalP > 0) {
                                                $porUnidad = $capacidad > 0 ? $totalP / $capacidad : 0;
                                                $totalMes += $totalP;
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                    <td class='py-1 pr-4 text-sm'>Personal</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>{$personas} persona(s) × $ " . number_format($costoP, 2) . "</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700'>Mensual</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($totalP, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidad, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            // Filas otras inversiones
                                            foreach ($otros as $item) {
                                                $monto  = (float) ($item['monto_mensual'] ?? 0);
                                                $frec   = $item['frecuencia'] ?? 'mensual';
                                                $tipo   = $tipoLabel($item['tipo'] ?? '');
                                                $desc   = $item['descripcion'] ?? '';
                                                $nombre = $desc ? "{$tipo} — {$desc}" : $tipo;
                                                $totalMes += $monto;
                                                $porUnidad = $capacidad > 0 ? $monto / $capacidad : 0;
                                                $badgeColor = match ($frec) {
                                                    'semanal' => 'bg-amber-100 text-amber-700',
                                                    'unico'   => 'bg-purple-100 text-purple-700',
                                                    default   => 'bg-blue-100 text-blue-700',
                                                };
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                    <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>—</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs {$badgeColor}'>" . $frecLabel($frec) . "</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($monto, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidad, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            // Costos fijos de empresa
                                            $totalFijosMes = self::costosFijosMensuales();
                                            if ($totalFijosMes > 0) {
                                                $porUnidadFijos = $capacidad > 0 ? $totalFijosMes / $capacidad : 0;
                                                $totalMes += $totalFijosMes;
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700' style='background:rgba(124,58,237,0.05);'>
                                                    <td class='py-1 pr-4 text-sm' style='color:#7c3aed;'>🏢 Costos Fijos Empresa</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>Prorrateo mensual</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700'>Mensual</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono' style='color:#7c3aed;'>$ " . number_format($totalFijosMes, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono' style='color:#7c3aed;'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidadFijos, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            if (empty($rows)) {
                                                return new HtmlString('<p class="text-sm text-gray-400">Sin costos operativos registrados.</p>');
                                            }

                                            $porUnidadTotal = $capacidad > 0 ? $totalMes / $capacidad : 0;

                                            $rows .= "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                                <td class='py-2 pr-4 text-sm' colspan='3'>Total Costo Operativo / Mes</td>
                                                <td class='py-2 pr-4 text-sm text-right font-mono'>$ " . number_format($totalMes, 2) . "</td>
                                                <td class='py-2 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidadTotal, 4) . ' / ' . $unidad : '—') . "</td>
                                            </tr>";

                                            // Costo total por paquete
                                            $packages = $get('packages') ?? [];
                                            $pIdx = 0;
                                            foreach ($packages as $pkg) {
                                                $pIdx++;
                                                $pkgNombre = $pkg['nombre'] ?? ('Paquete ' . $pIdx);
                                                $rows .= "<tr class='border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800'>
                                                    <td class='py-2 pr-4 text-sm font-semibold' colspan='3'>" . e($pkgNombre) . " — Costo Total / {$unidad}</td>
                                                    <td class='py-2 pr-4 text-sm text-right font-mono text-gray-400'>Op: $ " . number_format($porUnidadTotal, 4) . "</td>
                                                    <td class='py-2 text-sm text-right font-mono font-bold' style='color:#dc2626'>$ " . number_format($porUnidadTotal, 4) . "</td>
                                                </tr>";
                                            }

                                            return new HtmlString("
                                                <div class='overflow-x-auto'>
                                                    <table class='w-full'>
                                                        <thead>
                                                            <tr class='border-b border-gray-200 dark:border-gray-600'>
                                                                <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Concepto</th>
                                                                <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Detalle</th>
                                                                <th class='pb-1 pr-4 text-center text-xs font-semibold text-gray-500 uppercase'>Frecuencia</th>
                                                                <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Monto / Mes</th>
                                                                <th class='pb-1 text-right text-xs font-semibold text-gray-500 uppercase'>Por {$unidad}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>{$rows}</tbody>
                                                    </table>
                                                    <p class='mt-2 text-xs text-gray-400'>* La coma (,) es separador de miles. Ej: 1,000 = mil.</p>
                                                </div>
                                            ");
                                        }),
                                ]),
                        ]),

                    // ─────────────────────────────────────────────────────
                    // TAB 5 — ESTRATEGIA COMERCIAL Y SIMULACIÓN
                    // ─────────────────────────────────────────────────────
                    Tab::make('Estrategia Comercial')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([

                            // ── Precio e IVA ──────────────────────────────────────
                            Section::make('Precio de Venta')
                                ->description('Define el precio del servicio. El IVA (15%) aplica como passthrough al cliente.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('_sim_margen')
                                        ->label('Margen de Utilidad (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $costo = self::costoUnitario($get);
                                            if ($costo <= 0) return;
                                            $margen  = (float) ($state ?? 0);
                                            $divisor = 1 - ($margen / 100);
                                            if ($divisor <= 0) return;
                                            $precio     = round($costo / $divisor, 2);
                                            $incluyeIva = (bool) ($get('_sim_incluye_iva') ?? false);
                                            $set('_sim_precio', $incluyeIva ? round($precio * 1.15, 2) : $precio);
                                        })
                                        ->placeholder('Ej: 40')
                                        ->helperText('Ingresa el margen → se calcula el precio.')
                                        ->columnSpan(1),

                                    TextInput::make('_sim_precio')
                                        ->label(fn (callable $get) => (bool) ($get('_sim_incluye_iva') ?? false)
                                            ? 'Precio de Venta (con IVA)'
                                            : 'Precio de Venta (sin IVA)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $costo = self::costoUnitario($get);
                                            if ($costo <= 0) return;
                                            $precio     = (float) ($state ?? 0);
                                            if ($precio <= 0) return;
                                            $incluyeIva = (bool) ($get('_sim_incluye_iva') ?? false);
                                            $precioSinIva = $incluyeIva ? $precio / 1.15 : $precio;
                                            $set('_sim_margen', round((($precioSinIva - $costo) / $precioSinIva) * 100, 2));
                                            $margenRev = (float) ($get('margen_revendedor') ?: 30);
                                            $set('precio_revendedor', round($precioSinIva * (1 - $margenRev / 100), 2));
                                        })
                                        ->placeholder('Ej: 200.00')
                                        ->helperText('Ingresa el precio → se calcula el margen.')
                                        ->columnSpan(1),

                                    Toggle::make('_sim_incluye_iva')
                                        ->label('¿El precio ya incluye IVA (15%)?')
                                        ->dehydrated(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $precio = (float) ($get('_sim_precio') ?? 0);
                                            if ($precio <= 0) return;
                                            $incluyeIva   = (bool) $state;
                                            $precioSinIva = $incluyeIva ? round($precio / 1.15, 4) : $precio;
                                            $costo = self::costoUnitario($get);
                                            if ($costo > 0 && $precioSinIva > 0) {
                                                $set('_sim_margen', round((($precioSinIva - $costo) / $precioSinIva) * 100, 2));
                                            }
                                            $margenRev = (float) ($get('margen_revendedor') ?: 30);
                                            $set('precio_revendedor', round($precioSinIva * (1 - $margenRev / 100), 2));
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('_sim_dias_entrega')
                                        ->label('Días estimados de entrega')
                                        ->numeric()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->suffix('días')
                                        ->placeholder('Ej: 7')
                                        ->helperText('¿En cuántos días esperas entregar / cobrar el servicio?')
                                        ->columnSpan(1),

                                    TextInput::make('_sim_meta_ganancia')
                                        ->label('Meta de Rentabilidad (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->default(5)
                                        ->placeholder('5')
                                        ->helperText('ROI mínimo esperado.')
                                        ->columnSpan(1),
                                ]),

                            // ── Canal Revendedores ────────────────────────────────
                            Section::make('Canal Revendedores / Agencias')
                                ->description('Configura el precio y condiciones para revendedores o agencias que comercialicen tu servicio.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('margen_revendedor')
                                        ->label('Margen Revendedor (%)')
                                        ->numeric()
                                        ->default(30)
                                        ->suffix('%')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $margen       = (float) $state;
                                            $precio       = (float) ($get('_sim_precio') ?? 0);
                                            $incluyeIva   = (bool) ($get('_sim_incluye_iva') ?? false);
                                            $precioSinIva = ($precio > 0 && $incluyeIva) ? $precio / 1.15 : $precio;
                                            if ($precioSinIva > 0) {
                                                $set('precio_revendedor', round($precioSinIva * (1 - $margen / 100), 2));
                                            }
                                        })
                                        ->helperText('Margen → calcula el precio al revendedor.')
                                        ->columnSpan(1),

                                    TextInput::make('precio_revendedor')
                                        ->label('Precio Revendedor')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $precio       = (float) $state;
                                            $pvp          = (float) ($get('_sim_precio') ?? 0);
                                            $incluyeIva   = (bool) ($get('_sim_incluye_iva') ?? false);
                                            $precioSinIva = ($pvp > 0 && $incluyeIva) ? $pvp / 1.15 : $pvp;
                                            if ($precioSinIva > 0 && $precio >= 0) {
                                                $set('margen_revendedor', round((1 - $precio / $precioSinIva) * 100, 2));
                                            }
                                        })
                                        ->helperText('Precio → calcula el margen.')
                                        ->columnSpan(1),

                                    TextInput::make('cantidad_minima_revendedor')
                                        ->label('Cantidad mínima (precio rev.)')
                                        ->numeric()
                                        ->default(5)
                                        ->minValue(1)
                                        ->helperText('Unidades mínimas para aplicar precio de revendedor.')
                                        ->columnSpan(1),

                                    Placeholder::make('_rev_info')
                                        ->label('')
                                        ->content(new HtmlString(
                                            '<p style="font-size:0.72rem;color:#6b7280;line-height:1.5;">'
                                            . 'El precio de revendedor se aplica cuando una agencia o socio comercializa tu servicio por debajo del precio directo al cliente final.'
                                            . '</p>'
                                        ))
                                        ->columnSpan(1),
                                ]),

                            // ── Simulación ────────────────────────────────────────
                            Section::make('Simulación y Análisis Financiero')
                                ->description('Simula diferentes escenarios de venta de servicios.')
                                ->columns(3)
                                ->schema([
                                    Select::make('_sim_cargar')
                                        ->label('📂 Cargar simulación guardada')
                                        ->placeholder('Seleccionar simulación...')
                                        ->dehydrated(false)
                                        ->live()
                                        ->columnSpanFull()
                                        ->options(function ($record) {
                                            if (!$record) return [];
                                            return ServiceSimulation::where('service_design_id', $record->id)
                                                ->orderByDesc('created_at')
                                                ->get()
                                                ->mapWithKeys(fn ($s) => [
                                                    $s->id => $s->nombre
                                                        . '  ·  ' . number_format($s->cantidad, 0) . ' unid.'
                                                        . '  ·  $ ' . number_format($s->precio_sin_iva, 2)
                                                        . '  ·  ' . $s->created_at->format('d/m/Y'),
                                                ])
                                                ->toArray();
                                        })
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (!$state) return;
                                            $sim = ServiceSimulation::find($state);
                                            if (!$sim) return;
                                            $set('_sim_package_key',  $sim->package_nombre);
                                            $set('_sim_cantidad',     $sim->cantidad);
                                            $set('_sim_precio',       $sim->precio_sin_iva);
                                            $set('_sim_margen',       $sim->margen_porcentaje);
                                            $set('_sim_dias_entrega', $sim->dias_entrega);
                                            $set('_sim_meta_ganancia', $sim->meta_ganancia);
                                        })
                                        ->helperText('Carga una simulación guardada para editar sus parámetros.'),

                                    Select::make('_sim_package_key')
                                        ->label('Paquete / Modalidad')
                                        ->options(function (callable $get) {
                                            $packages = $get('packages') ?? [];
                                            $result   = [];
                                            $i        = 1;
                                            foreach ($packages as $key => $pkg) {
                                                $nombre = $pkg['nombre'] ?? ('Paquete ' . $i++);
                                                $precio = isset($pkg['precio_estimado']) && $pkg['precio_estimado'] > 0
                                                    ? '  ($ ' . number_format((float) $pkg['precio_estimado'], 2) . ')'
                                                    : '';
                                                $result[$key] = $nombre . $precio;
                                            }
                                            if (empty($result)) {
                                                $result['default'] = 'Servicio (modalidad única)';
                                            }
                                            return $result;
                                        })
                                        ->dehydrated(false)
                                        ->live()
                                        ->placeholder('Seleccionar paquete...')
                                        ->columnSpan(1),

                                    TextInput::make('_sim_cantidad')
                                        ->label('Cantidad a vender')
                                        ->numeric()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->placeholder('Ej: 20')
                                        ->helperText('¿Cuántas unidades del servicio planeas vender?')
                                        ->columnSpan(1),

                                    Placeholder::make('_sim_tiempo_estimado')
                                        ->label('Tiempo estimado')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $cantidad  = (float) ($get('_sim_cantidad') ?? 0);
                                            $capacidad = (float) ($get('capacidad_mensual') ?? 0);
                                            $dias      = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                            $personas  = (float) ($get('num_personas') ?? 0);
                                            $unidad    = match ($get('unidad_capacidad') ?? 'sesion') {
                                                'hora'     => 'horas',
                                                'cliente'  => 'clientes',
                                                'proyecto' => 'proyectos',
                                                'consulta' => 'consultas',
                                                'clase'    => 'clases',
                                                'evento'   => 'eventos',
                                                default    => 'sesiones',
                                            };
                                            if ($cantidad <= 0 || $capacidad <= 0) {
                                                return new HtmlString('<span class="text-xs text-gray-400">Completa los campos de capacidad y cantidad.</span>');
                                            }
                                            $diaria     = $capacidad / $dias;
                                            $diasNec    = $diaria > 0 ? (int) ceil($cantidad / $diaria) : 0;
                                            $semanasNec = round($diasNec / 5, 1);
                                            $mesesNec   = round($diasNec / $dias, 2);
                                            return new HtmlString(
                                                '<div class="text-sm space-y-1">'
                                                . '<div>⏱ <strong>' . $diasNec . ' días hábiles</strong></div>'
                                                . '<div>≈ ' . $semanasNec . ' semanas / ' . $mesesNec . ' mes(es)</div>'
                                                . '<div>👥 ' . (int) $personas . ' persona(s) · ' . number_format($capacidad, 0) . ' ' . $unidad . '/mes</div>'
                                                . '</div>'
                                            );
                                        }),
                                ]),

                            // ── Liquidación financiera ────────────────────────────
                            Placeholder::make('_sim_liquidacion')
                                ->label('')
                                ->columnSpanFull()
                                ->content(function (callable $get) {
                                    $cantidad  = (float) ($get('_sim_cantidad') ?? 0);
                                    $capacidad = (float) ($get('capacidad_mensual') ?? 0);

                                    if ($cantidad <= 0) {
                                        return new HtmlString('');
                                    }

                                    $unidad    = match ($get('unidad_capacidad') ?? 'sesion') {
                                        'hora'     => 'hora',
                                        'cliente'  => 'cliente',
                                        'proyecto' => 'proyecto',
                                        'consulta' => 'consulta',
                                        'clase'    => 'clase',
                                        'evento'   => 'evento',
                                        default    => 'sesión',
                                    };

                                    // ── Costos operativos ─────────────────────────────
                                    $fracMes  = $capacidad > 0 ? $cantidad / $capacidad : 1;
                                    $personas = (float) ($get('num_personas') ?? 0);
                                    $costoP   = (float) ($get('costo_persona_mes') ?? 0);
                                    $totalP   = $personas * $costoP;

                                    $totalOtros = 0;
                                    foreach ($get('indirectCosts') ?? [] as $ic) {
                                        $monto = (float) ($ic['monto_mensual'] ?? 0);
                                        $totalOtros += match ($ic['frecuencia'] ?? 'mensual') {
                                            'semanal' => $monto * 4.33,
                                            'unico'   => $monto,
                                            default   => $monto,
                                        };
                                    }

                                    $totalFijosEmpresa = self::costosFijosMensuales();
                                    $costoOperativoMes = $totalP + $totalOtros + $totalFijosEmpresa;
                                    $costoProrrateado  = $capacidad > 0
                                        ? $costoOperativoMes * $fracMes
                                        : $costoOperativoMes;
                                    $costoUnitario = $cantidad > 0 ? $costoProrrateado / $cantidad : 0;

                                    // ── Precio de venta ───────────────────────────────
                                    $incluyeIva = (bool) ($get('_sim_incluye_iva') ?? false);
                                    $precioCampo = (float) ($get('_sim_precio') ?? 0);
                                    if ($precioCampo > 0) {
                                        $precioSinIva = $incluyeIva ? round($precioCampo / 1.15, 4) : $precioCampo;
                                    } else {
                                        $margenPct = (float) ($get('_sim_margen') ?? 0);
                                        $divisor   = 1 - ($margenPct / 100);
                                        $precioSinIva = ($divisor > 0 && $costoUnitario > 0)
                                            ? round($costoUnitario / $divisor, 2)
                                            : 0;
                                    }

                                    $precioConIva  = round($precioSinIva * 1.15, 2);
                                    $ingresoNeto   = $precioSinIva * $cantidad;
                                    $ivaTotal      = round($ingresoNeto * 0.15, 2);
                                    $totalFacturado = $ingresoNeto + $ivaTotal;

                                    $utilidadBruta = $ingresoNeto - $costoProrrateado;
                                    $utilidadNeta  = $utilidadBruta;
                                    $margenBruto   = $ingresoNeto > 0 ? ($utilidadBruta / $ingresoNeto) * 100 : 0;
                                    $margenNeto    = $margenBruto;
                                    $roi           = $costoProrrateado > 0 ? ($utilidadNeta / $costoProrrateado) * 100 : 0;
                                    $utilPorUnidad = $cantidad > 0 ? $utilidadNeta / $cantidad : 0;

                                    $margenMostrado = $precioSinIva > 0
                                        ? round((($precioSinIva - $costoUnitario) / $precioSinIva) * 100, 1)
                                        : 0;

                                    $diasLab  = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                    $diaria   = $capacidad > 0 ? $capacidad / $diasLab : 0;
                                    $diasNec  = $diaria > 0 ? (int) ceil($cantidad / $diaria) : 0;

                                    $diasEntrega  = (int) ($get('_sim_dias_entrega') ?? 0);
                                    $metaGanancia = (float) ($get('_sim_meta_ganancia') ?? 5);

                                    $fmt = fn ($v) => number_format((float) $v, 2);
                                    $pct = fn ($v) => number_format((float) $v, 1) . '%';
                                    $cGreen = '#16a34a';
                                    $cRed   = '#dc2626';
                                    $cAmb   = '#d97706';

                                    // Payback
                                    $paybackDias      = null;
                                    $unidadesPayback  = $precioSinIva > 0 ? (int) ceil($costoProrrateado / $precioSinIva) : 0;
                                    if ($diasEntrega > 0 && $precioSinIva > 0 && $costoProrrateado > 0) {
                                        $ingresoDiario = $ingresoNeto / $diasEntrega;
                                        $paybackDias   = (int) ceil($costoProrrateado / $ingresoDiario);
                                    }

                                    $cUtil = $utilidadNeta >= 0 ? $cGreen : $cRed;
                                    $cRoi  = $roi >= $metaGanancia ? $cGreen : ($roi >= 0 ? $cAmb : $cRed);

                                    $kpiBig = function (string $label, string $val, string $sub, string $color, bool $highlight = false, string $tooltip = '') {
                                        $border = $highlight ? 'border:2px solid ' . $color . ';' : 'border:1px solid #e5e7eb;';
                                        return '<div style="background:#fff;' . $border . 'border-radius:0.6rem;padding:0.85rem;text-align:center;">'
                                            . '<p style="font-size:0.6rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.1rem;">' . $label . '</p>'
                                            . '<p style="font-size:1.25rem;font-weight:800;color:' . $color . ';line-height:1.1;" title="' . $tooltip . '">' . $val . '</p>'
                                            . '<p style="font-size:0.6rem;color:#9ca3af;">' . $sub . '</p>'
                                            . '</div>';
                                    };

                                    $html = '';

                                    // ══════════════════════════════════════════════════
                                    // SECCIÓN 1 — KPIs PRINCIPALES
                                    // ══════════════════════════════════════════════════
                                    $html .= '<div style="margin-top:1.5rem;border-top:3px solid #4f46e5;padding-top:1.5rem;">';
                                    $html .= '<div style="margin-bottom:1rem;padding:0.6rem 1rem;background:#eef2ff;border-radius:0.5rem;border:1px solid #c7d2fe;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#3730a3;">📊 LIQUIDACIÓN FINANCIERA — VENTA DIRECTA</span>'
                                        . '<span style="float:right;font-size:0.7rem;color:#4338ca;">' . number_format($cantidad, 0) . ' ' . $unidad . '(es) × $ ' . $fmt($precioSinIva) . ' (sin IVA)</span>'
                                        . '</div>';

                                    // Alerta si precio no definido
                                    if ($precioSinIva <= 0) {
                                        $html .= '<div style="padding:1rem;background:#fef3c7;border:1px solid #fcd34d;border-radius:0.5rem;color:#92400e;font-size:0.8rem;">'
                                            . '⚠ Ingresa el precio de venta en la sección <strong>Precio de Venta</strong> para ver la liquidación financiera.'
                                            . '</div></div>';
                                        return new HtmlString($html);
                                    }

                                    $html .= '<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0.75rem;margin-bottom:1.25rem;">';
                                    $html .= $kpiBig('Precio de Venta', '$ ' . $fmt($precioSinIva), '+ IVA 15% → $ ' . $fmt($precioConIva), '#4338ca');
                                    $html .= $kpiBig('Costo / ' . $unidad, '$ ' . $fmt($costoUnitario), 'Prorrateo operativo', '#6b7280');
                                    $html .= $kpiBig('Margen Neto', $pct($margenNeto), $pct($margenBruto) . ' bruto', $margenNeto >= 20 ? $cGreen : ($margenNeto >= 10 ? $cAmb : $cRed));
                                    $html .= $kpiBig('ROI', $pct($roi), 'Meta: ' . $pct($metaGanancia), $cRoi, false, 'Por cada $1 invertido, recibes $' . number_format(1 + $roi / 100, 2) . ' de vuelta');
                                    $html .= $kpiBig('Utilidad Neta', '$ ' . $fmt($utilidadNeta), '$ ' . $fmt($utilPorUnidad) . ' / ' . $unidad, $cUtil, true);
                                    $html .= '</div>';

                                    // ── Tablas de detalle ─────────────────────────────
                                    $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">';

                                    $tdR = 'style="padding:0.4rem 0.75rem;text-align:right;font-family:monospace;"';
                                    $tdL = 'style="padding:0.4rem 0.75rem;"';

                                    // Columna izquierda — ingresos
                                    $html .= '<div><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;overflow:hidden;">'
                                        . '<div style="background:#f0fdf4;padding:0.5rem 0.75rem;border-bottom:1px solid #e5e7eb;font-size:0.68rem;font-weight:700;color:#15803d;text-transform:uppercase;">💰 Ingresos</div>'
                                        . '<table style="width:100%;border-collapse:collapse;font-size:0.78rem;">'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . '>Precio sin IVA</td><td ' . $tdR . '>$ ' . $fmt($precioSinIva) . ' / ' . $unidad . '</td></tr>'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . '>Cantidad</td><td ' . $tdR . '>' . number_format($cantidad, 0) . ' ' . $unidad . '(es)</td></tr>'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;background:#f9fafb;font-weight:600;"><td ' . $tdL . '>Ingreso Neto</td><td ' . $tdR . ' style="color:#15803d;font-weight:700;">$ ' . $fmt($ingresoNeto) . '</td></tr>'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . ' style="color:#6b7280;">+ IVA 15%</td><td ' . $tdR . ' style="color:#6b7280;">$ ' . $fmt($ivaTotal) . '</td></tr>'
                                        . '<tr style="background:#ecfdf5;font-weight:700;"><td ' . $tdL . '>Total Facturado</td><td ' . $tdR . ' style="color:#059669;">$ ' . $fmt($totalFacturado) . '</td></tr>'
                                        . '</table></div></div>';

                                    // Columna derecha — costos
                                    $html .= '<div><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;overflow:hidden;">'
                                        . '<div style="background:#fef2f2;padding:0.5rem 0.75rem;border-bottom:1px solid #e5e7eb;font-size:0.68rem;font-weight:700;color:#dc2626;text-transform:uppercase;">📋 Costos</div>'
                                        . '<table style="width:100%;border-collapse:collapse;font-size:0.78rem;">'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . '>Personal (prorrateado)</td><td ' . $tdR . '>$ ' . $fmt($totalP * $fracMes) . '</td></tr>'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . '>Otros Operativos</td><td ' . $tdR . '>$ ' . $fmt($totalOtros * $fracMes) . '</td></tr>'
                                        . '<tr style="border-bottom:1px solid #f1f5f9;"><td ' . $tdL . '>Costos Fijos Empresa</td><td ' . $tdR . '>$ ' . $fmt($totalFijosEmpresa * $fracMes) . '</td></tr>'
                                        . '<tr style="background:#fef2f2;font-weight:700;"><td ' . $tdL . '>Total Costos</td><td ' . $tdR . ' style="color:#dc2626;">$ ' . $fmt($costoProrrateado) . '</td></tr>'
                                        . '</table></div></div>';

                                    $html .= '</div>';

                                    // ── Análisis de rentabilidad ──────────────────────
                                    $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;overflow:hidden;margin-bottom:1.25rem;">'
                                        . '<div style="background:#f5f3ff;padding:0.5rem 0.75rem;border-bottom:1px solid #e5e7eb;font-size:0.68rem;font-weight:700;color:#4c1d95;text-transform:uppercase;">📈 Rentabilidad</div>'
                                        . '<div style="display:grid;grid-template-columns:repeat(4,1fr);">'
                                        . $kpiBig('Utilidad Bruta', '$ ' . $fmt($utilidadBruta), '$ ' . $fmt($cantidad > 0 ? $utilidadBruta / $cantidad : 0) . ' / ' . $unidad, $utilidadBruta >= 0 ? '#16a34a' : $cRed)
                                        . $kpiBig('Margen Bruto', $pct($margenBruto), 'Ingreso neto - costos', $margenBruto >= 20 ? $cGreen : ($margenBruto >= 10 ? $cAmb : $cRed))
                                        . $kpiBig('Utilidad Neta', '$ ' . $fmt($utilidadNeta), '$ ' . $fmt($utilPorUnidad) . ' / ' . $unidad, $cUtil, true)
                                        . $kpiBig('Margen Neto', $pct($margenNeto), 'Sobre ingreso neto', $margenNeto >= 15 ? $cGreen : ($margenNeto >= 5 ? $cAmb : $cRed))
                                        . '</div></div>';

                                    // ── Payback ───────────────────────────────────────
                                    if ($unidadesPayback > 0 || $paybackDias !== null) {
                                        $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem 1.25rem;margin-bottom:1.25rem;">';
                                        $html .= '<p style="font-size:0.72rem;font-weight:700;color:#374151;margin-bottom:0.5rem;text-transform:uppercase;">⏳ Punto de Equilibrio (Payback)</p>';
                                        $html .= '<div style="display:flex;gap:2rem;flex-wrap:wrap;">';
                                        if ($unidadesPayback > 0) {
                                            $html .= '<div>'
                                                . '<p style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Unidades para cubrir costos</p>'
                                                . '<p style="font-size:1.2rem;font-weight:700;color:#4338ca;">' . number_format($unidadesPayback, 0) . ' ' . $unidad . '(es)</p>'
                                                . '<p style="font-size:0.65rem;color:#9ca3af;">de ' . number_format($cantidad, 0) . ' planificadas</p>'
                                                . '</div>';
                                        }
                                        if ($paybackDias !== null) {
                                            $html .= '<div>'
                                                . '<p style="font-size:0.65rem;color:#6b7280;text-transform:uppercase;">Días para recuperar inversión</p>'
                                                . '<p style="font-size:1.2rem;font-weight:700;color:#4338ca;">' . $paybackDias . ' días</p>'
                                                . '<p style="font-size:0.65rem;color:#9ca3af;">sobre ' . $diasEntrega . ' días de entrega</p>'
                                                . '</div>';
                                        }
                                        $html .= '</div></div>';
                                    }

                                    // ── Canal Revendedores (si hay precio) ────────────
                                    $precioRev  = (float) ($get('precio_revendedor') ?? 0);
                                    $cantMinRev = (int) ($get('cantidad_minima_revendedor') ?? 5);
                                    $margenRevPct = (float) ($get('margen_revendedor') ?? 30);

                                    $html .= '<div style="margin-top:2rem;border-top:3px solid #6366f1;padding-top:1.5rem;">';
                                    $html .= '<div style="margin-bottom:1rem;padding:0.6rem 1rem;background:#f5f3ff;border-radius:0.5rem;border:1px solid #c4b5fd;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4c1d95;">🤝 CANAL REVENDEDORES</span>'
                                        . '<span style="float:right;font-size:0.7rem;color:#6d28d9;">Mínimo: ' . $cantMinRev . ' unid. para precio de revendedor</span>'
                                        . '</div>';

                                    if ($precioRev > 0) {
                                        $precioRevConIva = round($precioRev * 1.15, 4);
                                        $ingresoRev      = $precioRev * $cantidad;
                                        $utilidadRev     = $ingresoRev - $costoProrrateado;
                                        $margenRevReal   = $ingresoRev > 0 ? ($utilidadRev / $ingresoRev) * 100 : 0;
                                        $roiRev          = $costoProrrateado > 0 ? ($utilidadRev / $costoProrrateado) * 100 : 0;
                                        $difUtil         = $utilidadRev - $utilidadNeta;

                                        $html .= '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;">';
                                        $html .= $kpiBig('Precio Revendedor', '$ ' . $fmt($precioRev), '+ IVA → $ ' . $fmt($precioRevConIva), '#6d28d9');
                                        $html .= $kpiBig('Ingreso Neto Rev.', '$ ' . $fmt($ingresoRev), 'vs. $ ' . $fmt($ingresoNeto) . ' directo', '#374151');
                                        $html .= $kpiBig('Margen Neto Rev.', $pct($margenRevReal), 'Diferencia: ' . ($difUtil >= 0 ? '+' : '') . '$ ' . $fmt($difUtil), $margenRevReal >= 15 ? $cGreen : ($margenRevReal >= 5 ? $cAmb : $cRed));
                                        $html .= $kpiBig('Utilidad Neta Rev.', '$ ' . $fmt($utilidadRev), 'ROI: ' . $pct($roiRev), $utilidadRev >= 0 ? $cGreen : $cRed, true);
                                        $html .= '</div>';
                                    } else {
                                        $html .= '<div style="padding:1.5rem;border:2px dashed #c4b5fd;border-radius:0.75rem;text-align:center;color:#7c3aed;">'
                                            . '<p style="font-size:0.85rem;">📐 Configura el precio de revendedor arriba</p>'
                                            . '</div>';
                                    }
                                    $html .= '</div>';

                                    return new HtmlString($html);
                                }),

                            // ── Botón guardar simulación ──────────────────────────
                            Section::make('Guardar Simulación')
                                ->description('Guarda el escenario actual con un nombre para compararlo después.')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('_sim_nombre')
                                        ->label('Nombre de la simulación')
                                        ->placeholder('Ej: Escenario optimista Q2 2026')
                                        ->dehydrated(false)
                                        ->columnSpan(2),

                                    Select::make('_sim_estado')
                                        ->label('Estado')
                                        ->options([
                                            'en_proyecto' => 'En Proyecto',
                                            'ejecutado'   => 'Ejecutado',
                                            'cancelado'   => 'Cancelado',
                                        ])
                                        ->default('en_proyecto')
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    Placeholder::make('_sim_guardar_btn')
                                        ->label('')
                                        ->content(new HtmlString(
                                            '<p style="font-size:0.75rem;color:#6b7280;">Para guardar la simulación, completa los campos de cantidad y precio, ponle un nombre y usa el botón <strong>Guardar</strong> del formulario. La simulación se registrará automáticamente.</p>'
                                        ))
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // TABLA
    // ══════════════════════════════════════════════════════════════════════
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'consultoria'   => 'Consultoría',
                        'formacion'     => 'Formación',
                        'mantenimiento' => 'Mantenimiento',
                        'diseno'        => 'Diseño',
                        'software'      => 'Software',
                        'salud'         => 'Salud',
                        'legal'         => 'Legal',
                        'contabilidad'  => 'Contabilidad',
                        'marketing'     => 'Marketing',
                        'logistica'     => 'Logística',
                        'construccion'  => 'Construcción',
                        default         => $state ?? '—',
                    })
                    ->searchable(),
                TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('Paquetes')
                    ->badge()
                    ->color('info'),
                TextColumn::make('capacidad_mensual')
                    ->label('Capacidad / Mes')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? number_format((float) $state, 0) . ' ' . match ($record->unidad_capacidad) {
                            'hora'     => 'h.',
                            'cliente'  => 'clientes',
                            'proyecto' => 'proyectos',
                            'consulta' => 'consultas',
                            'clase'    => 'clases',
                            'evento'   => 'eventos',
                            default    => 'ses.',
                        }
                        : '—'),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PÁGINAS
    // ══════════════════════════════════════════════════════════════════════
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceDesigns::route('/'),
            'create' => Pages\CreateServiceDesign::route('/create'),
            'edit'   => Pages\EditServiceDesign::route('/{record}/edit'),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Calcula el costo unitario del servicio (costo operativo por sesión/hora/cliente).
     */
    public static function costoUnitario(callable $get): float
    {
        $capacidad = (float) ($get('capacidad_mensual') ?? 0);
        if ($capacidad <= 0) return 0;

        $personas   = (float) ($get('num_personas') ?? 0);
        $costoP     = (float) ($get('costo_persona_mes') ?? 0);
        $totalP     = $personas * $costoP;

        $totalOtros = 0;
        foreach ($get('indirectCosts') ?? [] as $ic) {
            $monto = (float) ($ic['monto_mensual'] ?? 0);
            $totalOtros += match ($ic['frecuencia'] ?? 'mensual') {
                'semanal' => $monto * 4.33,
                'unico'   => $monto,
                default   => $monto,
            };
        }

        $totalFijos = self::costosFijosMensuales();
        $totalMes   = $totalP + $totalOtros + $totalFijos;

        return $totalMes / $capacidad;
    }

    /**
     * Obtiene el total mensual de costos fijos activos de la empresa.
     */
    public static function costosFijosMensuales(): float
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return 0;

        return \App\Models\CostoFijo::where('empresa_id', $tenant->id)
            ->where('activo', true)
            ->get()
            ->sum(fn ($cf) => $cf->monto_mensual);
    }
}
