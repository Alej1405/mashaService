<?php

namespace App\Filament\App\Pages;

use App\Models\CartaPresentacion;
use App\Models\CmsAbout;
use App\Models\CmsContact;
use App\Models\CmsService;
use App\Models\MailingContact;
use App\Services\MailingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    public static function canAccess(): bool { return true; }

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
            'asunto'           => 'Carta de Presentación — ' . $empresa->name,
            'saludo'           => 'Estimado/a,',
            'intro'            => $intro,
            'servicios_titulo' => 'Nuestros servicios',
            'cierre'           => $cierre,
            'firma_nombre'     => $empresa->name,
            'firma_cargo'      => 'Equipo Comercial',
            'color_primario'   => '#1e3a5f',
            'color_acento'     => '#e8a045',
            'color_texto'      => '#2d2d2d',
            'color_fondo'      => '#f5f7fa',
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
                ->requiresConfirmation()
                ->modalHeading('Enviar a todos los contactos activos')
                ->modalDescription(function () {
                    $count = MailingContact::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->where('active', true)
                        ->count();
                    return "Se enviará la carta a {$count} contacto(s) activo(s).";
                })
                ->action(function () {
                    $empresa  = Filament::getTenant();
                    $carta    = CartaPresentacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
                    $contacts = MailingContact::withoutGlobalScopes()
                        ->where('empresa_id', $empresa->id)
                        ->where('active', true)
                        ->get(['nombre', 'email'])
                        ->toArray();

                    if (empty($contacts)) {
                        Notification::make()->warning()->title('Sin contactos activos')->send();
                        return;
                    }

                    $html    = $this->buildHtml($empresa, $carta);
                    $service = new MailingService($empresa);
                    $sent = 0; $failed = 0;

                    foreach ($contacts as $contact) {
                        $result = $service->sendRawEmail(
                            $contact['email'],
                            $contact['nombre'] ?? '',
                            $carta->asunto,
                            $html
                        );
                        $result['success'] ? $sent++ : $failed++;
                    }

                    Notification::make()
                        ->title("Enviados: {$sent}" . ($failed ? ", Fallidos: {$failed}" : ''))
                        ->color($failed ? 'warning' : 'success')
                        ->send();
                }),

            Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $empresa = Filament::getTenant();
                    $carta   = CartaPresentacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
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

    private function enviar(string $email, string $prefijo = ''): array
    {
        $empresa = Filament::getTenant();
        $carta   = CartaPresentacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
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

        return view('emails.carta-presentacion', compact('empresa', 'carta', 'servicios', 'contacto'))->render();
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $carta   = CartaPresentacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();

        return [
            'previewUrl' => route('carta.preview', ['slug' => $empresa->slug]),
        ];
    }
}
