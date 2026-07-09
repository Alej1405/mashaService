<?php

namespace App\Filament\Basic\Pages;

use App\Models\Role;
use App\Models\SupportTicket;
use App\Support\PanelAccess;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Hub de inicio. Punto de aterrizaje del usuario tras el login.
 *
 * Muestra información puntual de la empresa (tenant) y los accesos a los paneles
 * que le corresponden, según la intersección Plan (plan_panel) ∩ Rol (role_module).
 * El tenant (empresa activa) es la piedra angular: todo se resuelve sobre él.
 */
class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title           = 'Inicio';
    protected static ?int    $navigationSort  = -2;
    protected static string  $view            = 'filament.basic.pages.dashboard';

    /** Etiquetas legibles de roles para el resumen del usuario. */
    private const ROL_LABEL = [
        'super_admin'       => 'Super administrador',
        'admin_empresa'     => 'Administrador',
        'contador'          => 'Contador',
        'inventario'        => 'Inventario',
        'marketing'         => 'Marketing',
        'cms_editor'        => 'Editor de contenido',
        'ecommerce_manager' => 'Gestor de tienda',
    ];

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $user    = auth()->user();

        Carbon::setLocale('es');
        $hora   = now()->hour;
        $saludo = match (true) {
            $hora >= 6 && $hora < 12  => 'Buenos días',
            $hora >= 12 && $hora < 19 => 'Buenas tardes',
            default                    => 'Buenas noches',
        };

        // Excluye el panel actual (el hub vive en él): no se ofrece "entrar" a uno mismo.
        $panelActual = Filament::getCurrentPanel()?->getId();
        $paneles = collect(PanelAccess::accessiblePanels())
            ->reject(fn (array $p): bool => $p['key'] === $panelActual)
            ->values()
            ->all();

        // Un widget de resumen por cada módulo que el usuario ve y que tenga widget.
        $widgets = [];
        foreach (PanelAccess::accessibleModuleKeys() as $moduleKey) {
            $cls = \App\Hub\HubWidgetRegistry::for($moduleKey);
            if (! $cls) {
                continue;
            }
            $meta = $cls::meta();
            $widgets[] = [
                'titulo'  => $meta['titulo'],
                'icono'   => $meta['icono'],
                'color'   => $meta['color'],
                'url'     => '/' . $meta['path'] . '/' . ($empresa->slug ?? ''),
                'metrics' => $cls::metrics($empresa),
            ];
        }

        // Rol del usuario en esta empresa (pivote preciso, respaldo Spatie global).
        $rolName = $user->empresasAcceso()
            ->where('empresas.id', $empresa->id)
            ->first()?->pivot->rol
            ?: $user->getRoleNames()->first();

        // Equipo: usuarios únicos de la empresa (directos + por acceso).
        $equipo = $empresa->users()->pluck('users.id')
            ->merge($empresa->usuariosAcceso()->pluck('users.id'))
            ->unique()
            ->count();

        return [
            'saludo'       => $saludo . ', ' . explode(' ', $user->name)[0],
            'fecha'        => ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')),
            'empresa'      => $empresa,
            // Clientes: acceso transversal (no vive en ningún panel/módulo).
            'clientesUrl'  => \App\Filament\App\Resources\CustomerResource::getUrl('index'),
            'user'         => $user,
            'logo'         => ($lp = $empresa->logo_path) && Storage::disk('public')->exists($lp) ? asset('storage/' . ltrim($lp, '/')) : null,
            'inicial'      => mb_strtoupper(mb_substr($empresa->name ?? '?', 0, 1)),
            'paneles'      => $paneles,
            'widgets'      => $widgets,
            'stats'        => [
                'plan'         => $empresa->servicePlan?->nombre ?? ucfirst($empresa->plan ?? 'basic'),
                'panelesCount' => count($paneles),
                'equipo'       => $equipo,
                'rol'          => self::ROL_LABEL[$rolName] ?? ucfirst((string) $rolName),
                'miembroDesde' => $empresa->created_at?->translatedFormat('M Y') ?? '—',
            ],
        ];
    }

    public function solicitarAmpliarPlan(): void
    {
        $empresa = Filament::getTenant();

        $existente = SupportTicket::where('empresa_id', $empresa->id)
            ->where('asunto', 'like', '%Mailing%')
            ->whereIn('status', ['abierto', 'en_proceso'])
            ->exists();

        if ($existente) {
            Notification::make()
                ->title('Solicitud ya registrada')
                ->body('Ya tienes un ticket de soporte activo. El equipo lo está gestionando.')
                ->warning()
                ->send();

            return;
        }

        SupportTicket::create([
            'empresa_id'  => $empresa->id,
            'user_id'     => Auth::id(),
            'asunto'      => 'Solicitud de ampliación de plan',
            'descripcion' => "La empresa \"{$empresa->name}\" solicita ampliar su plan. Por favor contactar para gestionar.",
            'prioridad'   => 'media',
            'status'      => 'abierto',
        ]);

        Notification::make()
            ->title('Solicitud enviada a soporte')
            ->body('El equipo de soporte se pondrá en contacto contigo.')
            ->success()
            ->send();
    }
}
