<?php

namespace App\Filament\Contabilidad\Concerns;

use App\Jobs\GenerarDeclaracion;
use App\Models\Declaracion;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

/**
 * Lo que comparten Cumplimiento y Declaraciones para el 104 y el ATS: pedir (o volver
 * a calcular) la declaración de un mes y subir la confirmación del ATS del portal.
 * Las dos pantallas llaman a lo mismo, así no pueden comportarse distinto.
 */
trait GestionaDeclaraciones
{
    /**
     * Pide la declaración de un período desde la propia fila.
     *
     * Un período tiene una sola declaración: si ya hay una sin presentar, se
     * vuelve a calcular esa misma (por ejemplo, tras registrar facturas que
     * faltaban). Una ya presentada no se toca: corregirla es una sustitutiva.
     */
    public function pedir(int $anio, int $mes, string $tipo = 'f104'): void
    {
        $empresaId = Filament::getTenant()->id;
        $existente = Declaracion::where('empresa_id', $empresaId)->where('tipo', $tipo)
            ->where('anio', $anio)->where('mes', $mes)->latest('id')->first();

        // El ATS no se genera con facturas sin registrar: saldría incompleto.
        if ($tipo === 'ats' && ($faltan = app(\App\Services\AtsService::class)->pendientes($empresaId, $anio, $mes)) > 0) {
            Notification::make()->title('Todavía no se puede generar el ATS')
                ->body(sprintf('%d facturas de %02d/%d no están registradas. Regístralas en «Clasificar comprobantes» y luego genéralo.', $faltan, $mes, $anio))
                ->danger()->persistent()
                ->actions([\Filament\Notifications\Actions\Action::make('clasificar')->label('Ir a Clasificar comprobantes')
                    ->url(\App\Filament\Contabilidad\Pages\ClasificarComprobantes::getUrl(['anio' => $anio, 'mes' => $mes, 'vista' => 'por_registrar']))])
                ->send();

            return;
        }

        // Un 104 presentado se corrige con sustitutiva. El ATS sí se vuelve a subir: el
        // nuevo reemplaza al anterior en el SRI, y aquí queda constancia de lo reemplazado.
        if ($existente?->presentado_en && $tipo !== 'ats') {
            Notification::make()->title('Ese mes ya está presentado')
                ->body('Para cambiarlo hace falta una declaración sustitutiva.')->warning()->send();

            return;
        }

        if ($existente?->presentado_en && $tipo === 'ats') {
            $anterior = $existente->datos['confirmacion']['presentado_en'] ?? $existente->presentado_en->toDateString();
            $existente->fill(['presentado_en' => null, 'avisos' => array_merge($existente->avisos ?? [],
                ["Reemplaza al ATS presentado el {$anterior}: hay que subir este y su nueva confirmación."])]);
        }

        $declaracion = $existente ?? new Declaracion(['empresa_id' => $empresaId, 'tipo' => $tipo, 'anio' => $anio, 'mes' => $mes]);
        $declaracion->fill(['estado' => 'pendiente', 'mensaje' => null, 'solicitado_por' => auth()->id()])->save();
        // En Cumplimiento se cierra el «Qué declarar» abierto: lo que mostraba se recalcula.
        if (property_exists($this, 'abierta')) {
            $this->abierta = null;
        }

        GenerarDeclaracion::dispatch($declaracion->id);

        Notification::make()->title('Se está generando')
            ->body("Seguimiento {$declaracion->seguimiento}.")->info()->send();
    }

    /**
     * Sube la «Confirmación de Presentación de Archivo – ATS» del portal. La misma
     * auditoría que el 104: el ATS queda presentado solo con el documento del SRI.
     */
    public function subirConfirmacionAtsAction(): Action
    {
        return Action::make('subirConfirmacionAts')
            ->label('Subir la confirmación del SRI')
            ->modalHeading(fn (array $arguments) => 'Confirmación del ATS · '
                . Declaracion::where('empresa_id', Filament::getTenant()->id)->find($arguments['id'] ?? 0)?->periodo)
            ->modalDescription('El PDF «Confirmación de Presentación de Archivo – ATS» que muestra el portal al '
                . 'subir el anexo. Se comprueba que sea de esta empresa y de este mes. El SRI valida el archivo '
                . 'en las 48 horas siguientes: si llega un archivo de errores, el anexo se corrige y se vuelve a subir.')
            ->modalSubmitActionLabel('Comprobar y registrar')
            ->form([
                \Filament\Forms\Components\FileUpload::make('pdf')
                    ->label('Confirmación en PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, Action $action) {
                $declaracion = Declaracion::where('empresa_id', Filament::getTenant()->id)
                    ->where('tipo', 'ats')->findOrFail($arguments['id'] ?? 0);
                $pdf = is_array($data['pdf']) ? reset($data['pdf']) : $data['pdf'];

                $r = app(\App\Services\ConfirmacionAtsService::class)
                    ->registrar($declaracion, $pdf->get(), $pdf->getClientOriginalName(), auth()->id());
                $pdf->delete();

                if (! $r['ok']) {
                    Notification::make()->title('No se registró la confirmación')->body($r['error'])->danger()->persistent()->send();
                    $action->halt();
                }

                Notification::make()->title('ATS presentado')
                    ->body("{$declaracion->periodo} quedó presentado. El SRI lo valida en 48 horas.")->success()->send();
            });
    }

    /**
     * Sube la validación del SRI de un anexo anual (REBEFICS, ADI): el talón resumen o la
     * confirmación de carga en PDF. Queda archivada junto al anexo y lo da por presentado.
     *
     * A diferencia del ATS, todavía no se lee el PDF por dentro: no hay un ejemplo de su
     * formato. Se guarda tal cual y la fecha la pone quien lo sube.
     */
    public function subirValidacionAnexoAction(): Action
    {
        return Action::make('subirValidacionAnexo')
            ->label('Subir la validación del SRI')
            ->modalHeading(fn (array $arguments) => 'Validación del SRI · '
                . (($d = Declaracion::where('empresa_id', Filament::getTenant()->id)->find($arguments['id'] ?? 0))
                    ? (Declaracion::TIPOS[$d->tipo] ?? $d->tipo) . ' ' . $d->anio : ''))
            ->modalDescription('El talón resumen o la confirmación de carga que entrega SRI en línea, en PDF. '
                . 'Queda guardado con el anexo y el anexo pasa a presentado.')
            ->modalSubmitActionLabel('Guardar y dar por presentado')
            ->form([
                \Filament\Forms\Components\FileUpload::make('pdf')
                    ->label('Validación en PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->required(),
                \Filament\Forms\Components\DatePicker::make('fecha')
                    ->label('Fecha en que se subió al SRI')
                    ->default(now())->maxDate(now())->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $declaracion = Declaracion::where('empresa_id', Filament::getTenant()->id)
                    ->whereIn('tipo', ['rebefics', 'adi'])->findOrFail($arguments['id'] ?? 0);
                $pdf = is_array($data['pdf']) ? reset($data['pdf']) : $data['pdf'];

                $archivo = sprintf('declaraciones/%d/comprobantes/%s-%d-validacion.pdf',
                    $declaracion->empresa_id, $declaracion->tipo, $declaracion->anio);
                \Illuminate\Support\Facades\Storage::disk('local')->put($archivo, $pdf->get());
                $pdf->delete();

                $declaracion->update(['archivo_pdf' => $archivo, 'presentado_en' => $data['fecha']]);

                Notification::make()->title('Anexo presentado')
                    ->body((Declaracion::TIPOS[$declaracion->tipo] ?? $declaracion->tipo) . " {$declaracion->anio}: la validación del SRI quedó guardada.")
                    ->success()->send();
            });
    }

    /**
     * Sube de golpe las confirmaciones del ATS de cualquier mes, también de los que el ERP
     * no generó (meses anteriores o presentados por fuera). El mes sale de cada PDF.
     */
    protected function cargarConfirmacionesAtsAction(): Action
    {
        return Action::make('cargarConfirmacionesAts')
            ->label('Cargar confirmaciones del ATS')
            ->icon('heroicon-o-document-check')
            ->color('gray')
            ->modalHeading('Confirmaciones del ATS')
            ->modalDescription('El PDF «Confirmación de Presentación de Archivo – ATS» que muestra el portal al subir '
                . 'el anexo, de cualquier mes. De cada uno se lee la empresa y el mes, y ese ATS queda presentado, '
                . 'aunque no se haya generado en el ERP. Los de otra empresa se rechazan solos.')
            ->modalSubmitActionLabel('Subir y registrar')
            ->form([
                \Filament\Forms\Components\FileUpload::make('pdfs')
                    ->label('Confirmaciones en PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->multiple()->maxFiles(36)->maxSize(5120)
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data) {
                $servicio = app(\App\Services\ConfirmacionAtsService::class);
                $registrados = [];
                $rechazados = [];

                foreach ((array) $data['pdfs'] as $pdf) {
                    $r = $servicio->registrarDesdePdf(Filament::getTenant()->id, $pdf->get(), $pdf->getClientOriginalName(), auth()->id());
                    $r['ok'] ? $registrados[] = $r['periodo'] : $rechazados[] = $pdf->getClientOriginalName() . ': ' . $r['error'];
                    $pdf->delete();
                }

                Notification::make()
                    ->title($registrados ? 'ATS presentados: ' . implode(', ', $registrados) : 'No se registró ninguno')
                    ->body($rechazados ? implode(' · ', $rechazados) : 'El SRI valida cada anexo en las 48 horas siguientes a subirlo.')
                    ->{$rechazados ? 'warning' : 'success'}()->persistent()->send();
            });
    }
}
