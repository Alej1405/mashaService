<?php

namespace App\Filament\App\Pages;

use App\Mail\NuevaPlanificacionMail;
use App\Models\Empresa;
use App\Models\ProductionPlan;
use App\Models\ProductSimulation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class PlanificacionPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Planificación';
    protected static ?string $title           = 'Planificación de Producción';
    protected static ?string $navigationGroup = 'Planificación y Producción';
    protected static ?int    $navigationSort  = 1;

    protected static string $view = 'filament.app.pages.planificacion';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function planificarAction(): Action
    {
        return Action::make('planificar')
            ->modalHeading(function (array $arguments): string {
                return 'Planificar: ' . ($arguments['nombre'] ?? '');
            })
            ->modalWidth('md')
            ->form([
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                DatePicker::make('fecha_fin')
                    ->label('Fecha de finalización')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('fecha_inicio'),
            ])
            ->action(function (array $data, array $arguments): void {
                $empresa    = Filament::getTenant();
                $simulation = ProductSimulation::find($arguments['simulation_id']);

                if (!$simulation) {
                    Notification::make()
                        ->title('Error')
                        ->body('No se encontró la simulación seleccionada.')
                        ->danger()
                        ->send();
                    return;
                }

                $plan = ProductionPlan::create([
                    'empresa_id'            => $empresa->id,
                    'product_simulation_id' => $simulation->id,
                    'fecha_inicio'          => $data['fecha_inicio'],
                    'fecha_fin'             => $data['fecha_fin'],
                ]);

                $simulation->update(['estado' => 'en_proyecto']);

                $this->enviarNotificacion($plan, $simulation, $empresa);

                Notification::make()
                    ->title('Planificación guardada')
                    ->body('La producción de "' . ($arguments['nombre'] ?? $simulation->nombre) . '" quedó planificada del '
                        . \Carbon\Carbon::parse($data['fecha_inicio'])->format('d/m/Y')
                        . ' al '
                        . \Carbon\Carbon::parse($data['fecha_fin'])->format('d/m/Y') . '.')
                    ->success()
                    ->send();

                $this->redirect(static::getUrl());
            });
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $simulations = ProductSimulation::where('empresa_id', $empresa->id)
            ->with('productDesign')
            ->orderBy('nombre')
            ->get();

        return compact('simulations');
    }

    private function enviarNotificacion(
        ProductionPlan $plan,
        ProductSimulation $simulation,
        Empresa $empresa
    ): void {
        try {
            $destinatarios = $this->obtenerEmails($empresa);
            if (empty($destinatarios)) {
                return;
            }

            $mail = new NuevaPlanificacionMail(
                $plan,
                $simulation->nombre,
                'simulacion',
                $empresa
            );

            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => $destinatarios,
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Throwable $e) {
            Log::error('PlanificacionPage: error al enviar notificación', [
                'plan_id' => $plan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function obtenerEmails(Empresa $empresa): array
    {
        $emails = collect();

        $emails = $emails->merge(
            $empresa->users()->whereNotNull('email')->pluck('email')
        );

        $emails = $emails->merge(
            $empresa->accessUsers()->whereNotNull('email')->pluck('email')
        );

        return $emails
            ->unique()
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }
}
