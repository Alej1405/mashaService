<?php

namespace App\Filament\Admin\Pages;

use App\Models\Empresa;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SesionesActivasPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Sesiones';
    protected static ?string $navigationGroup = 'Monitoreo';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.admin.pages.sesiones-activas';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getSesionesActivas(): \Illuminate\Support\Collection
    {
        $threshold = now()->subMinutes(5)->timestamp;

        return DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->join('empresas', 'users.empresa_id', '=', 'empresas.id')
            ->where('sessions.last_activity', '>=', $threshold)
            ->whereNotNull('sessions.user_id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.last_login_at',
                'empresas.name as empresa_name',
                'empresas.plan',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
            )
            ->orderByDesc('sessions.last_activity')
            ->get()
            ->map(function ($row) {
                $row->last_activity_human = \Carbon\Carbon::createFromTimestamp($row->last_activity)->diffForHumans();
                $row->login_human         = $row->last_login_at
                    ? \Carbon\Carbon::parse($row->last_login_at)->diffForHumans()
                    : 'Desconocido';
                $row->device = str_contains(strtolower($row->user_agent ?? ''), 'mobile') ? 'Móvil' : 'Escritorio';
                return $row;
            });
    }

    public function getStats(): array
    {
        $threshold   = now()->subMinutes(5)->timestamp;
        $totalOnline = DB::table('sessions')->where('last_activity', '>=', $threshold)->whereNotNull('user_id')->count();
        $totalUsers  = User::count();
        $totalEmpresas = Empresa::where('activo', true)->count();

        $empresasOnline = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->where('sessions.last_activity', '>=', $threshold)
            ->whereNotNull('sessions.user_id')
            ->distinct('users.empresa_id')
            ->count('users.empresa_id');

        return compact('totalOnline', 'totalUsers', 'totalEmpresas', 'empresasOnline');
    }
}
