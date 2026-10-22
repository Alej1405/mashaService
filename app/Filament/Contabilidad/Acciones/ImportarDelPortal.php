<?php

namespace App\Filament\Contabilidad\Acciones;

use App\Jobs\RegistrarComprobantesDelPortal;
use App\Services\ImportadorComprobantesSri;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

/**
 * Subir el listado de comprobantes que la empresa baja del portal del SRI
 * (recibidos o emitidos). Una sola acción para Cumplimiento y para la bandeja
 * de clasificar: el archivo se sube donde se va a trabajar.
 *
 * Al subirlo se trae del SRI el detalle de cada factura recibida, en la cola:
 * las que lo tienen quedan listas para registrar; las otras, esperando su XML.
 */
class ImportarDelPortal
{
    public static function make(?callable $despues = null): Action
    {
        return Action::make('importar')
            ->label('Subir archivo del SRI')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->form([
                Placeholder::make('ayuda')
                    ->label('')
                    ->content('Sube el listado de comprobantes recibidos o el de emitidos, tal como '
                        . 'lo descarga el portal del SRI. Lo que ya esté en el ERP no se cuenta dos veces: '
                        . 'la clave de acceso es única.'),

                FileUpload::make('archivo')
                    ->label('Archivo .txt del SRI')
                    ->acceptedFileTypes(['text/plain', 'application/octet-stream'])
                    ->maxSize(10240)
                    ->disk('local')
                    ->directory('sri/temporal')
                    ->required(),
            ])
            ->action(function (array $data) use ($despues) {
                $ruta = storage_path('app/private/' . $data['archivo']);

                if (! file_exists($ruta)) {
                    $ruta = storage_path('app/' . $data['archivo']);
                }

                $r = app(ImportadorComprobantesSri::class)
                    ->importar(Filament::getTenant()->id, file_get_contents($ruta));

                @unlink($ruta);

                if (! ($r['ok'] ?? false)) {
                    Notification::make()->title('No se pudo importar')
                        ->body($r['error'])->danger()->persistent()->send();

                    return;
                }

                $periodos = implode(', ', array_keys($r['periodos'] ?? []));
                $trae = RegistrarComprobantesDelPortal::encolar(Filament::getTenant()->id, auth()->id());

                Notification::make()
                    ->title("{$r['importados']} comprobantes importados")
                    ->body(trim("{$r['tipo']} · {$r['repetidos']} ya estaban · {$r['conciliados']} casan con el ERP"
                        . ($periodos ? " · períodos: {$periodos}" : '')
                        . ' ' . implode(' ', $r['avisos'] ?? [])
                        . ($trae ? ' · Trayendo el detalle del SRI: te aviso en la campana al terminar.' : '')))
                    ->success()->persistent()->send();

                if ($despues) {
                    $despues();
                }
            });
    }
}
