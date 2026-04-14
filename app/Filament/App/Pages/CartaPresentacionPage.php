<?php

namespace App\Filament\App\Pages;

use App\Jobs\SendRawMassMailJob;
use App\Models\CartaPresentacion;
use App\Models\MailingSendLog;
use App\Models\CmsAbout;
use App\Models\CmsContact;
use App\Models\CmsService;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use App\Services\MailingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CartaPresentacionPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Carta de Presentación';
    protected static ?string $navigationGroup = 'Mailing';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $title           = 'Carta de Presentación';

    protected static string $view = 'filament.app.pages.carta-presentacion';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $empresa = Filament::getTenant();
        $about   = CmsAbout::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
        $contact = CmsContact::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();

        $record = CartaPresentacion::withoutGlobalScopes()
            ->firstOrCreate(
                ['empresa_id' => $empresa->id],
                $this->defaults($empresa, $about, $contact)
            );

        $this->form->fill($record->attributesToArray());
    }

    private function defaults($empresa, $about, $contact): array
    {
        $servicios = CmsService::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->pluck('titulo')
            ->join(', ');

        $intro = 'Nos complace presentar a ' . $empresa->name
            . ', empresa especializada en brindar soluciones de calidad a nuestros clientes.'
            . ($about?->descripcion ? ' ' . $about->descripcion : '');

        $cierre = 'Quedamos a su disposición para ampliar cualquier información y esperamos tener la oportunidad de trabajar juntos.'
            . "\n\nAtentamente,";

        return [
            'asunto'            => 'Carta de Presentación — ' . $empresa->name,
            'saludo'            => 'Estimado/a,',
            'intro'             => $intro,
            'servicios_titulo'  => 'Nuestros servicios',
            'mostrar_servicios' => true,
            'mostrar_equipo'    => true,
            'mostrar_contacto'  => true,
            'cierre'            => $cierre,
            'firma_nombre'      => $empresa->name,
            'firma_cargo'       => 'Equipo Comercial',
            'color_primario'    => '#1e3a5f',
            'color_acento'      => '#e8a045',
            'color_texto'       => '#2d2d2d',
            'color_fondo'       => '#f5f7fa',
            'template'          => 'ejecutivo',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Encabezado del correo')
                    ->schema([
                        TextInput::make('asunto')
                            ->label('Asunto del correo')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Contenido')
                    ->schema([
                        TextInput::make('saludo')
                            ->label('Saludo')
                            ->placeholder('Estimado/a,')
                            ->required(),

                        TextInput::make('servicios_titulo')
                            ->label('Título de la sección de servicios')
                            ->placeholder('Nuestros servicios')
                            ->required(),

                        Textarea::make('intro')
                            ->label('Párrafo de introducción')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('cierre')
                            ->label('Párrafo de cierre')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Firma')
                    ->schema([
                        TextInput::make('firma_nombre')
                            ->label('Nombre')
                            ->required(),

                        TextInput::make('firma_cargo')
                            ->label('Cargo / Área'),
                    ])
                    ->columns(2),

                Section::make('Secciones visibles')
                    ->description('Activa o desactiva cada bloque de la carta.')
                    ->schema([
                        Toggle::make('mostrar_servicios')
                            ->label('Mostrar sección de servicios')
                            ->default(true)
                            ->live(),

                        Toggle::make('mostrar_equipo')
                            ->label('Mostrar sección de equipo')
                            ->default(true)
                            ->live(),

                        Toggle::make('mostrar_contacto')
                            ->label('Mostrar sección de contacto')
                            ->default(true)
                            ->live(),
                    ])
                    ->columns(3),

                Section::make('Template')
                    ->description('Selecciona el diseño de la carta.')
                    ->schema([
                        Radio::make('template')
                            ->label('')
                            ->options([
                                'ejecutivo'  => 'Ejecutivo — Minimalismo refinado con franja de acento y servicios numerados',
                                'vanguardia' => 'Vanguardia — Header en degradé diagonal, servicios con numeración bold',
                                'elite'      => 'Élite — Diseño oscuro premium con detalles dorados y elementos geométricos',
                            ])
                            ->default('ejecutivo')
                            ->required()
                            ->columnSpanFull()
                            ->live(),
                    ]),

                Section::make('Colores')
                    ->description('Personaliza los colores del template.')
                    ->schema([
                        ColorPicker::make('color_primario')
                            ->label('Color primario (header / títulos)')
                            ->required(),

                        ColorPicker::make('color_acento')
                            ->label('Color de acento (bordes / detalles)')
                            ->required(),

                        ColorPicker::make('color_texto')
                            ->label('Color del texto')
                            ->required(),

                        ColorPicker::make('color_fondo')
                            ->label('Color de fondo')
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CartaPresentacion::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            $data
        );

        Notification::make()->success()->title('Carta guardada')->send();

        $this->dispatch('carta-saved');
    }

    // ── Acciones ────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enviar_prueba')
                ->label('Enviar prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->form([
                    TextInput::make('email_prueba')
                        ->label('Correo de destino')
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $result = $this->enviar($data['email_prueba'], '[PRUEBA] ');
                    $this->notificarEnvio($result, $data['email_prueba']);
                }),

            Action::make('enviar_correo')
                ->label('Enviar a correo')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->form([
                    TextInput::make('email_destino')
                        ->label('Correo de destino')
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $result = $this->enviar($data['email_destino']);
                    $this->notificarEnvio($result, $data['email_destino']);
                }),

            Action::make('enviar_base_datos')
                ->label('Enviar a base de datos')
                ->icon('heroicon-o-users')
                ->color('warning')
                ->modalHeading('Enviar carta a grupo de contactos')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\Select::make('mailing_group_id')
                        ->label('Grupo de contactos')
                        ->options(function () {
                            return MailingGroup::where('empresa_id', Filament::getTenant()->id)
                                ->withCount(['contacts' => fn ($q) => $q->where('active', true)])
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($g) => [
                                    $g->id => $g->name . ' — ' . number_format($g->contacts_count) . ' activos',
                                ])
                                ->toArray();
                        })
                        ->required()
                        ->live()
                        ->helperText('Selecciona el grupo al que quieres enviar la carta.'),

                    \Filament\Forms\Components\Placeholder::make('resumen')
                        ->label('Destinatarios')
                        ->content(function (\Filament\Forms\Get $get) {
                            $groupId = $get('mailing_group_id');
                            if (! $groupId) {
                                return new \Illuminate\Support\HtmlString('<span style="color:#d97706;">Selecciona un grupo para ver el total.</span>');
                            }
                            $count = MailingContact::withoutGlobalScopes()
                                ->where('empresa_id', Filament::getTenant()->id)
                                ->where('mailing_group_id', $groupId)
                                ->where('active', true)
                                ->count();
                            return new \Illuminate\Support\HtmlString("<strong>{$count}</strong> contacto(s) activo(s) recibirán esta carta.");
                        })
                        ->live(),
                ])
                ->action(function (array $data) {
                    $empresa  = Filament::getTenant();
                    $carta    = $this->cartaFromForm($empresa);
                    $contacts = MailingContact::withoutGlobalScopes()
                        ->where('empresa_id', $empresa->id)
                        ->where('mailing_group_id', $data['mailing_group_id'])
                        ->where('active', true)
                        ->get(['nombre', 'email'])
                        ->map(fn ($c) => ['nombre' => $c->nombre, 'email' => $c->email])
                        ->toArray();

                    if (empty($contacts)) {
                        Notification::make()->warning()->title('Sin contactos activos en este grupo')->send();
                        return;
                    }

                    $html = $this->buildHtml($empresa, $carta);

                    // Despachar a la cola: envía uno por uno respetando 100/hora.
                    // tipo=carta_presentacion → dedup de 7 días por contacto.
                    SendRawMassMailJob::dispatch(
                        $empresa->id,
                        $carta->asunto,
                        $html,
                        $contacts,
                        MailingSendLog::TIPO_CARTA,
                    );

                    // Calcular cuántos ya fueron enviados esta semana para informar al usuario
                    $emails     = array_column($contacts, 'email');
                    $yaEnviados = \App\Models\MailingSendLog::where('empresa_id', $empresa->id)
                        ->where('tipo', MailingSendLog::TIPO_CARTA)
                        ->where('sent_at', '>=', now()->subDays(7))
                        ->whereIn('email', $emails)
                        ->count();
                    $nuevos = count($contacts) - $yaEnviados;

                    Notification::make()
                        ->title('Envío programado')
                        ->body(
                            $nuevos > 0
                                ? "{$nuevos} correo(s) se enviarán en segundo plano."
                                  . ($yaEnviados > 0 ? " {$yaEnviados} omitido(s) por envío reciente (últimos 7 días)." : '')
                                : "Todos los contactos ya recibieron esta carta en los últimos 7 días. No se enviará nada."
                        )
                        ->color($nuevos > 0 ? 'success' : 'warning')
                        ->send();
                }),

            Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $empresa = Filament::getTenant();
                    $carta   = $this->cartaFromForm($empresa);
                    $html    = $this->buildHtml($empresa, $carta);

                    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'carta-presentacion-' . $empresa->slug . '.pdf'
                    );
                }),
        ];
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function cartaFromForm($empresa): CartaPresentacion
    {
        $carta = CartaPresentacion::withoutGlobalScopes()
            ->firstOrNew(['empresa_id' => $empresa->id]);

        if (! empty($this->data)) {
            $carta->fill($this->data);
        }

        return $carta;
    }

    private function enviar(string $email, string $prefijo = ''): array
    {
        $empresa = Filament::getTenant();
        $carta   = $this->cartaFromForm($empresa);
        $html    = $this->buildHtml($empresa, $carta);
        $service = new MailingService($empresa);

        return $service->sendRawEmail($email, '', $prefijo . $carta->asunto, $html);
    }

    private function notificarEnvio(array $result, string $email): void
    {
        Notification::make()
            ->title($result['success'] ? 'Correo enviado a ' . $email : 'Error al enviar')
            ->body($result['success'] ? null : $result['message'])
            ->color($result['success'] ? 'success' : 'danger')
            ->send();
    }

    private function buildHtml($empresa, CartaPresentacion $carta): string
    {
        $servicios = CmsService::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get();

        $contacto = CmsContact::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->first();

        $equipo = \App\Models\CmsTeamMember::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get();

        $template = in_array($carta->template, ['ejecutivo', 'vanguardia', 'elite'])
            ? $carta->template
            : 'ejecutivo';

        return view("emails.carta-templates.{$template}", compact('empresa', 'carta', 'servicios', 'contacto', 'equipo'))->render();
    }

    public function getViewData(): array
    {
        $empresa   = Filament::getTenant();
        $formData  = $this->data ?? [];

        // Construir un CartaPresentacion temporal con los valores actuales del formulario
        // para que la preview refleje el estado del form aunque no se haya guardado.
        $carta = CartaPresentacion::withoutGlobalScopes()
            ->firstOrNew(['empresa_id' => $empresa->id]);

        if (! empty($formData)) {
            $carta->fill($formData);
        }

        $previewHtml = $carta->exists || ! empty($formData)
            ? $this->buildHtml($empresa, $carta)
            : null;

        return [
            'previewHtml' => $previewHtml,
        ];
    }
}
