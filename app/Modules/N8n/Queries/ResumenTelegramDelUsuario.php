<?php

namespace App\Modules\N8n\Queries;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use App\Models\ServiceInvoice;
use App\Models\SupportTicket;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Collection;

/**
 * Lo que el bot le presenta a una persona al ligar su número: qué avisos va a
 * recibir y un resumen de las empresas a su cargo. Todo sale del ERP.
 *
 * Avisos:
 * - Soporte: siempre.
 * - Renovaciones y pagos: solo si alguna de sus empresas tiene una suscripción
 *   activa, es decir, facturas de servicio emitidas por MashaCorp.
 */
#[Documentado(
    grupo: 'Integración n8n',
    descripcion: 'Arma el resumen que ve el usuario en Telegram al ligar su número: avisos y empresas a su cargo.',
    tipo: 'query',
)]
final class ResumenTelegramDelUsuario
{
    /** @return array{avisos: array<int,array{clave:string,titulo:string,detalle:string}>, empresas: array<int,array<string,mixed>>} */
    public function handle(User $user): array
    {
        $empresas = $this->empresasACargo($user);
        $resumen = $empresas->map(fn (Empresa $empresa) => $this->resumenEmpresa($empresa))->values();

        $avisos = [[
            'clave'   => 'soporte',
            'titulo'  => 'Tickets de soporte',
            'detalle' => 'Tickets nuevos, respuestas y cambios de estado.',
        ]];

        if ($resumen->contains('suscripcion_activa', true)) {
            $avisos[] = [
                'clave'   => 'pagos',
                'titulo'  => 'Renovaciones y pagos',
                'detalle' => 'Facturas de tu suscripción por vencer y pagos pendientes.',
            ];
        }

        return ['avisos' => $avisos, 'empresas' => $resumen->all()];
    }

    /** Empresas activas del usuario. Super_admin atiende a todas: no se listan una por una. */
    private function empresasACargo(User $user): Collection
    {
        if ($user->hasRole('super_admin')) {
            return collect();
        }

        return $user->empresasAcceso()
            ->where('activo', true)
            ->with('servicePlan:key,nombre')
            ->orderBy('name')
            ->get();
    }

    /** @return array<string,mixed> */
    private function resumenEmpresa(Empresa $empresa): array
    {
        $facturas = ServiceInvoice::where('empresa_id', $empresa->id)->get(['estado', 'monto', 'fecha_vencimiento']);
        $pendientes = $facturas->whereIn('estado', [ServiceInvoice::PENDIENTE, ServiceInvoice::VENCIDO]);

        return [
            'nombre'               => $empresa->name,
            'plan'                 => $empresa->servicePlan?->nombre,
            'sitio'                => $this->sinProtocolo($empresa->website_url),
            'tickets_en_curso'     => SupportTicket::withoutGlobalScope(EmpresaScope::class)
                ->where('empresa_id', $empresa->id)
                ->where('status', '!=', SupportTicket::CERRADO)
                ->count(),
            'suscripcion_activa'   => $facturas->isNotEmpty(),
            'facturas_pendientes'  => $pendientes->count(),
            'monto_pendiente'      => number_format((float) $pendientes->sum('monto'), 2),
            'proximo_vencimiento'  => $pendientes->sortBy('fecha_vencimiento')->first()?->fecha_vencimiento?->format('d/m/Y'),
        ];
    }

    private function sinProtocolo(?string $url): ?string
    {
        return $url ? rtrim(preg_replace('#^https?://#', '', $url), '/') : null;
    }
}
