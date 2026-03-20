<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailTemplateResource\Pages;
use App\Models\MailTemplate;
use App\Services\MailingService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MailTemplateResource extends Resource
{
    protected static ?string $model = MailTemplate::class;
    protected static ?string $tenantRelationshipName = 'mailTemplates';

    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationLabel  = 'Plantillas';
    protected static ?string $navigationGroup  = 'Mailing';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Plantilla';
    protected static ?string $pluralModelLabel = 'Plantillas de correo';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        $variablesCard = new HtmlString('
            <div style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:14px 18px;">
                <p style="font-size:.75rem;font-weight:700;color:#6366f1;margin:0 0 8px;text-transform:uppercase;letter-spacing:.05em;">Variables disponibles</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    ' . collect([
                        '{{nombre}}', '{{empresa}}', '{{email}}',
                        '{{fecha}}', '{{numero}}', '{{url}}', '{{portal}}',
                    ])->map(fn ($v) =>
                        "<code style='background:rgba(99,102,241,.12);color:#6366f1;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;'>{$v}</code>"
                    )->implode('') . '
                </div>
                <p style="font-size:.7rem;color:#94a3b8;margin:8px 0 0;">El sistema reemplaza estas variables con los datos reales al enviar el correo.</p>
            </div>
        ');

        return $form
            ->schema([
                // ── Cabecera fija: nombre + asunto ────────────────────────
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre interno de la plantilla')
                            ->placeholder('Bienvenida, Factura mensual, Recordatorio…')
                            ->prefixIcon('heroicon-o-tag')
                            ->required()
                            ->maxLength(120),

                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto del correo')
                            ->placeholder('Hola {{nombre}}, tu factura está lista')
                            ->prefixIcon('heroicon-o-envelope')
                            ->required()
                            ->maxLength(255),
                    ]),

                // ── Tabs del diseñador ─────────────────────────────────────
                Forms\Components\Tabs::make('Diseñador')
                    ->tabs([

                        // ── Tab 1: Contenido ───────────────────────────────
                        Forms\Components\Tabs\Tab::make('Contenido')
                            ->icon('heroicon-o-document-text')
                            ->schema([

                                Forms\Components\Placeholder::make('vars_hint')
                                    ->label('')
                                    ->content($variablesCard)
                                    ->columnSpanFull(),

                                // Encabezado
                                Forms\Components\Fieldset::make('Encabezado')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('header_text')
                                            ->label('Texto')
                                            ->placeholder('¡Hola {{nombre}}!')
                                            ->maxLength(255)
                                            ->columnSpan(2),

                                        Forms\Components\ColorPicker::make('header_background_color')
                                            ->label('Fondo')
                                            ->default('#1e40af'),

                                        Forms\Components\ColorPicker::make('header_text_color')
                                            ->label('Texto')
                                            ->default('#ffffff'),
                                    ]),

                                // Cuerpo
                                Forms\Components\RichEditor::make('body')
                                    ->label('Cuerpo del correo')
                                    ->toolbarButtons([
                                        'bold', 'italic', 'underline', 'strike',
                                        'h2', 'h3',
                                        'bulletList', 'orderedList',
                                        'blockquote',
                                        'link',
                                        'undo', 'redo',
                                    ])
                                    ->placeholder('Escribe el contenido principal aquí…')
                                    ->required()
                                    ->columnSpanFull(),

                                // Botón CTA
                                Forms\Components\Fieldset::make('Botón de acción (opcional)')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('button_text')
                                            ->label('Texto del botón')
                                            ->placeholder('Ver detalle, Ir al portal…')
                                            ->maxLength(80)
                                            ->columnSpan(2),

                                        Forms\Components\ColorPicker::make('button_color')
                                            ->label('Fondo')
                                            ->default('#1e40af'),

                                        Forms\Components\ColorPicker::make('button_text_color')
                                            ->label('Texto')
                                            ->default('#ffffff'),

                                        Forms\Components\TextInput::make('button_url')
                                            ->label('URL de destino')
                                            ->placeholder('https://tudominio.com  ó  {{url}}')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ]),

                                // Pie
                                Forms\Components\Fieldset::make('Pie de página (opcional)')
                                    ->schema([
                                        Forms\Components\TextInput::make('footer_text')
                                            ->label('Texto del pie')
                                            ->placeholder('© 2026 Mi Empresa · Todos los derechos reservados')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ]),

                            ]),

                        // ── Tab 2: Diseño visual ───────────────────────────
                        Forms\Components\Tabs\Tab::make('Diseño visual')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([

                                Forms\Components\Fieldset::make('Tipografía')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('font_family')
                                            ->label('Fuente')
                                            ->options([
                                                'Arial'        => 'Arial — simple y universal',
                                                'Verdana'      => 'Verdana — legible en pantalla',
                                                'Tahoma'       => 'Tahoma — compacta y clara',
                                                'Trebuchet MS' => 'Trebuchet MS — moderna humanista',
                                                'Georgia'      => 'Georgia — elegante con serifa',
                                                'Inter'        => 'Inter — diseño contemporáneo',
                                            ])
                                            ->default('Arial')
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\Select::make('base_font_size')
                                            ->label('Tamaño de fuente')
                                            ->options([
                                                13 => '13 px — pequeña',
                                                14 => '14 px — compacta',
                                                15 => '15 px — normal−',
                                                16 => '16 px — normal ✓',
                                                17 => '17 px — cómoda',
                                                18 => '18 px — grande',
                                            ])
                                            ->default(16)
                                            ->native(false)
                                            ->required(),

                                        Forms\Components\ColorPicker::make('text_color')
                                            ->label('Color del texto')
                                            ->default('#374151'),
                                    ]),

                                Forms\Components\Fieldset::make('Fondos')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\ColorPicker::make('background_color')
                                            ->label('Fondo exterior del correo')
                                            ->helperText('El color que rodea la caja del correo.')
                                            ->default('#f3f4f6'),

                                        Forms\Components\ColorPicker::make('content_background_color')
                                            ->label('Fondo del contenido')
                                            ->helperText('El color de la caja principal del correo.')
                                            ->default('#ffffff'),
                                    ]),

                                Forms\Components\Placeholder::make('design_note')
                                    ->label('')
                                    ->content(new HtmlString('
                                        <p style="font-size:.75rem;color:#94a3b8;margin:0;">
                                            💡 Los colores del encabezado y el botón se configuran en la pestaña
                                            <strong style="color:#6366f1;">Contenido</strong>.
                                            Usa el botón <strong>Vista previa</strong> para ver el resultado final.
                                        </p>
                                    ')),

                            ]),

                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plantilla')
                    ->description(fn (MailTemplate $r) => $r->subject)
                    ->searchable(['name', 'subject'])
                    ->sortable()
                    ->wrap(),

                // Características activas de la plantilla
                Tables\Columns\TextColumn::make('caracteristicas')
                    ->label('Elementos')
                    ->html()
                    ->getStateUsing(function (MailTemplate $record): string {
                        $items = [];
                        if (! empty($record->header_text)) {
                            $items[] = "<span style='display:inline-flex;align-items:center;gap:3px;background:rgba(99,102,241,.1);color:#6366f1;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;'>
                                <svg style='width:10px;height:10px;' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M3 4.5h18M3 9h18M3 13.5h10'/></svg>
                                Encabezado</span>";
                        }
                        if (! empty($record->button_text)) {
                            $items[] = "<span style='display:inline-flex;align-items:center;gap:3px;background:rgba(16,185,129,.1);color:#059669;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;'>
                                <svg style='width:10px;height:10px;' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15 15l-6-6m0 0l6-6m-6 6h12'/></svg>
                                Botón</span>";
                        }
                        if (! empty($record->footer_text)) {
                            $items[] = "<span style='display:inline-flex;align-items:center;gap:3px;background:rgba(107,114,128,.1);color:#6b7280;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;'>
                                <svg style='width:10px;height:10px;' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M3 19.5h18M3 15h10'/></svg>
                                Pie</span>";
                        }
                        return $items
                            ? "<div style='display:flex;flex-wrap:wrap;gap:4px;'>" . implode('', $items) . "</div>"
                            : "<span style='color:#d1d5db;font-size:.75rem;'>Solo cuerpo</span>";
                    }),

                // Paleta de colores
                Tables\Columns\TextColumn::make('paleta')
                    ->label('Paleta')
                    ->html()
                    ->getStateUsing(fn (MailTemplate $r): string =>
                        "<div style='display:flex;align-items:center;gap:3px;'>"
                        . self::swatch($r->background_color, 'Fondo exterior')
                        . self::swatch($r->content_background_color, 'Contenido')
                        . self::swatch($r->header_background_color, 'Encabezado')
                        . self::swatch($r->text_color, 'Texto')
                        . "</div>"
                    ),

                Tables\Columns\TextColumn::make('font_family')
                    ->label('Fuente')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Inter'        => 'Inter',
                        'Georgia'      => 'Georgia',
                        'Verdana'      => 'Verdana',
                        'Trebuchet MS' => 'Trebuchet',
                        'Tahoma'       => 'Tahoma',
                        default        => 'Arial',
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modificada')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('Vista previa')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn (MailTemplate $r) => $r->name)
                        ->modalDescription(fn (MailTemplate $r) => 'Asunto: ' . $r->subject)
                        ->modalWidth('4xl')
                        ->modalContent(fn (MailTemplate $r) => view(
                            'filament.app.modals.mail-template-preview',
                            ['html' => $r->toHtml(), 'template' => $r]
                        ))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar'),

                    Tables\Actions\Action::make('sendTest')
                        ->label('Enviar prueba')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->form([
                            Forms\Components\TextInput::make('email_destino')
                                ->label('Email de destino')
                                ->email()
                                ->required()
                                ->default(fn () => Auth::user()?->email)
                                ->helperText('Las variables se sustituirán con datos de ejemplo.'),
                        ])
                        ->action(function (MailTemplate $record, array $data) {
                            $service = new MailingService(Filament::getTenant());

                            if (! $service->isConfigured()) {
                                Notification::make()
                                    ->title('Servicio de correo no configurado')
                                    ->body('El administrador debe activar el servicio de correo primero.')
                                    ->warning()->send();
                                return;
                            }

                            $result = $service->sendTemplateTest($data['email_destino'], $record);

                            Notification::make()
                                ->title($result['success'] ? 'Correo enviado' : 'Error al enviar')
                                ->body($result['message'])
                                ->{$result['success'] ? 'success' : 'danger'}()
                                ->send();
                        }),

                    Tables\Actions\EditAction::make()->label('Editar'),
                    Tables\Actions\DeleteAction::make()->label('Eliminar'),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Opciones'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Genera una pastilla de color para la paleta en la tabla. */
    private static function swatch(string $color, string $title): string
    {
        return "<span title='{$title}' style='display:inline-block;width:18px;height:18px;border-radius:50%;background:{$color};border:2px solid rgba(0,0,0,.12);box-shadow:0 1px 2px rgba(0,0,0,.1);'></span>";
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMailTemplates::route('/'),
            'create' => Pages\CreateMailTemplate::route('/create'),
            'edit'   => Pages\EditMailTemplate::route('/{record}/edit'),
        ];
    }
}
