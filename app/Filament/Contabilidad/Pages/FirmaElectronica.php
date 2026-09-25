<?php

namespace App\Filament\Contabilidad\Pages;

use App\Services\FirmaElectronicaService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;

/**
 * El certificado de firma electrónica.
 *
 * Caduca a los dos años y sin él no se firma ni un comprobante ni un anexo.
 * Esta pantalla existe sobre todo para que nadie se entere de que venció el
 * día que tenía que declarar.
 */
class FirmaElectronica extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-finger-print';
    protected static ?string $navigationLabel = 'Firma electrónica';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title           = 'Firma electrónica';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.contabilidad.firma-electronica';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    /** El aviso en el menú: un número rojo cuando queda poco. */
    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();

        if (! $empresa) {
            return null;
        }

        $firma = app(FirmaElectronicaService::class)->vigente($empresa->id);
        $dias = $firma?->dias_restantes;

        return ($dias !== null && $dias <= \App\Models\FirmaElectronica::AVISO_DIAS) ? (string) max($dias, 0) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cargar')
                ->label('Cargar certificado')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('archivo')
                        ->label('Archivo .p12 o .pfx')
                        ->helperText('El que te entregó Security Data, Uanataca, el Banco Central o Anfac.')
                        ->acceptedFileTypes(['application/x-pkcs12', 'application/octet-stream'])
                        ->maxSize(2048)
                        ->disk('local')
                        ->directory('firmas/temporal')
                        ->required(),

                    TextInput::make('clave')
                        ->label('Contraseña del certificado')
                        ->password()
                        ->revealable()
                        ->helperText('Se guarda cifrada. Hace falta para firmar; nadie la ve en pantalla.')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $empresa = Filament::getTenant();
                    $ruta = storage_path('app/private/' . $data['archivo']);

                    if (! file_exists($ruta)) {
                        $ruta = storage_path('app/' . $data['archivo']);
                    }

                    $subido = new UploadedFile($ruta, basename($ruta), null, null, true);
                    $r = app(FirmaElectronicaService::class)
                        ->guardar($empresa->id, $subido, $data['clave'], auth()->id());

                    @unlink($ruta);

                    if (! ($r['ok'] ?? false)) {
                        Notification::make()->title('No se pudo cargar')
                            ->body($r['error'])->danger()->persistent()->send();

                        return;
                    }

                    $firma = $r['firma'];

                    Notification::make()
                        ->title('Certificado cargado')
                        ->body("{$firma->titular} · {$firma->estado['texto']}")
                        ->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $servicio = app(FirmaElectronicaService::class);

        return [
            'empresa'   => $empresa,
            'firma'     => $servicio->vigente($empresa->id),
            'historial' => \App\Models\FirmaElectronica::where('empresa_id', $empresa->id)
                ->orderByDesc('created_at')->limit(6)->get(),
        ];
    }
}
