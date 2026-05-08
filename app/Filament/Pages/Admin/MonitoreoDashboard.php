<?php

namespace App\Filament\Pages\Admin;

use App\Models\SystemEvent;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MonitoreoDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Monitoreo';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.admin.monitoreo-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getStats(): array
    {
        $activos   = SystemEvent::where('resuelto', false)->count();
        $resueltos = SystemEvent::where('resuelto', true)->count();
        $errores   = SystemEvent::where('resuelto', false)->whereIn('tipo', ['error', 'job_fallido'])->count();
        $warnings  = SystemEvent::where('resuelto', false)->where('tipo', 'warning')->count();

        $jobsFallidos = DB::table('failed_jobs')->count();

        $porEmpresa = SystemEvent::where('resuelto', false)
            ->join('empresas', 'system_events.empresa_id', '=', 'empresas.id')
            ->selectRaw('empresas.name as empresa, COUNT(*) as total')
            ->groupBy('empresas.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $porModulo = SystemEvent::where('resuelto', false)
            ->whereNotNull('modulo')
            ->selectRaw('modulo, COUNT(*) as total')
            ->groupBy('modulo')
            ->orderByDesc('total')
            ->get();

        return compact('activos', 'resueltos', 'errores', 'warnings', 'jobsFallidos', 'porEmpresa', 'porModulo');
    }

    public function getEventosRecientes()
    {
        return SystemEvent::with('empresa')
            ->where('resuelto', false)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function getJobsFallidos()
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->job_name = class_basename($payload['displayName'] ?? 'Unknown');
                $job->failed_human = \Carbon\Carbon::parse($job->failed_at)->diffForHumans();
                $job->exception_short = substr($job->exception ?? '', 0, 200);
                return $job;
            });
    }
}
