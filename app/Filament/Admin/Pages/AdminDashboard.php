<?php

namespace App\Filament\Admin\Pages;

use App\Models\Empresa;
use App\Models\SystemEvent;
use App\Services\EmpresaStatsService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title           = 'Dashboard';
    protected static ?string $navigationGroup = null;
    protected static ?int    $navigationSort  = -10;
    protected static string  $view            = 'filament.admin.pages.admin-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getKpis(): array
    {
        $stats = app(EmpresaStatsService::class)->statsGlobales();

        $threshold   = now()->subMinutes(5)->timestamp;
        $onlineAhora = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $threshold)
            ->distinct('user_id')
            ->count('user_id');

        $incidentes = SystemEvent::where('resuelto', false)
            ->where('tipo', 'error')
            ->count();

        return [
            'total'         => (int) $stats->total,
            'activas'       => (int) $stats->activas,
            'inactivas'     => (int) $stats->inactivas,
            'online'        => $onlineAhora,
            'incidentes'    => $incidentes,
            'nuevas'        => (int) $stats->nuevas_este_mes,
            'porPlan'       => [
                'enterprise' => (int) $stats->enterprise,
                'pro'        => (int) $stats->pro,
                'basic'      => (int) $stats->basic,
            ],
        ];
    }

    public function getEmpresasMatriz(): \Illuminate\Support\Collection
    {
        $modulos = config('erp_features', []);

        return Empresa::query()
            ->select(['id', 'name', 'slug', 'plan', 'activo', 'features'])
            ->withCount('users')
            ->orderByDesc('activo')
            ->orderBy('name')
            ->get()
            ->map(function (Empresa $e) use ($modulos) {
                $modulosStatus = [];
                foreach ($modulos as $key => $cfg) {
                    $modulosStatus[$key] = [
                        'label'  => $cfg['label'],
                        'status' => $e->moduleStatus($key),
                        'icon'   => $cfg['icon'] ?? null,
                    ];
                }

                // Sesión activa en los últimos 5 min
                $threshold = now()->subMinutes(5)->timestamp;
                $online = DB::table('sessions')
                    ->where('last_activity', '>=', $threshold)
                    ->whereIn('user_id', $e->users()->pluck('users.id'))
                    ->exists();

                return (object) [
                    'id'            => $e->id,
                    'name'          => $e->name,
                    'slug'          => $e->slug,
                    'plan'          => $e->plan,
                    'activo'        => $e->activo,
                    'users_count'   => $e->users_count,
                    'online'        => $online,
                    'modulos'       => $modulosStatus,
                    'completados'   => collect($modulosStatus)->where('status', 'complete')->count(),
                    'parciales'     => collect($modulosStatus)->where('status', 'partial')->count(),
                ];
            });
    }

    public function getActividad(): \Illuminate\Support\Collection
    {
        return SystemEvent::query()
            ->select(['id', 'tipo', 'titulo', 'mensaje', 'empresa_id', 'created_at'])
            ->with('empresa:id,name')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function getAdopcion(): array
    {
        return app(EmpresaStatsService::class)
            ->adopcionPorModulo()
            ->toArray();
    }
}
