<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Models\User;
use App\Modules\Soporte\Actions\AbrirTicket;
use App\Modules\Soporte\Actions\RegistrarMensajeTicket;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Soporte desde Telegram (vía n8n). El módulo vive en el ERP; n8n solo transporta.
 * Visibilidad igual que en el panel: super_admin ve todos los tickets;
 * admin_empresa, los de sus empresas; el resto, solo los propios.
 * Un usuario puede tener varias empresas: cada ticket dice de cuál es y al
 * crear uno se elige la empresa.
 */
class SoporteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->visibles($request)->with('empresa:id,name')->withCount('mensajes');

        if ($request->query('estado', 'pendientes') === 'pendientes') {
            $query->where('status', '!=', SupportTicket::CERRADO);
        }

        $items = $query->latest()->limit(20)->get()->map(fn (SupportTicket $t) => [
            'id'        => $t->id,
            'asunto'    => $t->asunto,
            'empresa'   => $t->empresa?->name,
            'estado'    => $t->statusLabel(),
            'prioridad' => $t->prioridadLabel(),
            'mensajes'  => $t->mensajes_count,
            'creado'    => $t->created_at?->format('d/m H:i'),
        ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function show(Request $request): JsonResponse
    {
        $ticket = $this->ticket($request);
        if (! $ticket) {
            return $this->noEncontrado();
        }

        $ticket->load('empresa:id,name', 'user:id,name');
        $mensajes = $ticket->mensajes()->with('user:id,name')->latest()->limit(15)->get()->reverse()->values();

        return response()->json(['ok' => true, 'item' => [
            'id'          => $ticket->id,
            'asunto'      => $ticket->asunto,
            'descripcion' => $ticket->descripcion,
            'empresa'     => $ticket->empresa?->name,
            'autor'       => $ticket->user?->name,
            'estado'      => $ticket->statusLabel(),
            'prioridad'   => $ticket->prioridadLabel(),
            'creado'      => $ticket->created_at?->format('d/m/Y H:i'),
            'mensajes'    => $mensajes->map(fn (SupportTicketMensaje $m) => [
                'remitente' => $m->remitente,
                'autor'     => $m->user?->name,
                'texto'     => $m->mensaje,
                'fecha'     => $m->created_at?->format('d/m H:i'),
                'adjunto'   => $m->adjuntoPayload(),
            ]),
        ]]);
    }

    /** Empresas en las que el usuario puede abrir tickets (para elegir al crear). */
    public function empresas(Request $request): JsonResponse
    {
        $items = Empresa::whereIn('id', $this->empresaIds($request))->where('activo', true)
            ->orderBy('name')->get(['id', 'name']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asunto'      => ['required', 'string', 'max:200'],
            'descripcion' => ['required', 'string'],
            'prioridad'   => ['nullable', 'in:baja,media,alta'],
            'empresa_id'  => ['nullable', 'integer'],
        ]);

        $empresaId = $data['empresa_id'] ?? $request->attributes->get('n8n_empresa')->id;
        if (! in_array((int) $empresaId, $this->empresaIds($request), true)) {
            return response()->json(['ok' => false, 'error' => 'empresa_invalida', 'mensaje' => 'No puedes abrir tickets en esa empresa.'], 422);
        }

        $ticket = app(AbrirTicket::class)->handle(
            $this->user($request), (int) $empresaId, $data['asunto'], $data['descripcion'], $data['prioridad'] ?? 'media', SupportTicketMensaje::TELEGRAM,
        );

        return response()->json(['ok' => true, 'mensaje' => 'Ticket #'.$ticket->id.' creado en '.rtrim((string) $ticket->empresa?->name, '.').'.', 'item' => ['id' => $ticket->id]], 201);
    }

    public function mensaje(Request $request): JsonResponse
    {
        $ticket = $this->ticket($request);
        if (! $ticket) {
            return $this->noEncontrado();
        }

        $data = $request->validate([
            'mensaje' => ['nullable', 'string', 'required_without:archivo'],
            'archivo' => ['nullable', 'file', 'max:'.SupportTicket::MAX_ADJUNTO_KB],
        ]);

        try {
            app(RegistrarMensajeTicket::class)->handle(
                $ticket, $this->user($request), SupportTicketMensaje::TELEGRAM, $data['mensaje'] ?? null, $request->file('archivo'),
            );
        } catch (DomainException $e) {
            return response()->json(['ok' => false, 'error' => 'ticket_cerrado', 'mensaje' => $e->getMessage().' Ábrelo con /abrir si hace falta.'], 422);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Mensaje agregado al ticket #'.$ticket->id.'.'], 201);
    }

    public function estado(Request $request): JsonResponse
    {
        $ticket = $this->ticket($request);
        if (! $ticket) {
            return $this->noEncontrado();
        }
        if (! $this->user($request)->hasRole(['super_admin', 'admin_empresa'])) {
            return response()->json(['ok' => false, 'error' => 'sin_permiso', 'mensaje' => 'Solo un administrador cambia el estado.'], 403);
        }

        $data = $request->validate(['status' => ['required', 'in:'.implode(',', SupportTicket::ESTADOS)]]);
        $ticket->update($data);

        return response()->json(['ok' => true, 'mensaje' => 'Ticket #'.$ticket->id.': '.$ticket->statusLabel().'.']);
    }

    // ---- Apoyo ----------------------------------------------------------------

    private function visibles(Request $request): Builder
    {
        $user = $this->user($request);
        $query = SupportTicket::withoutGlobalScope(EmpresaScope::class);

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $query->whereIn('empresa_id', $this->empresaIds($request));

        return $user->hasRole('admin_empresa') ? $query : $query->where('user_id', $user->id);
    }

    /** @return array<int,int> Empresas del usuario: la primaria y las de acceso. Super_admin: la de la sesión. */
    private function empresaIds(Request $request): array
    {
        $user = $this->user($request);
        if ($user->hasRole('super_admin')) {
            return [(int) $request->attributes->get('n8n_empresa')->id];
        }

        return $user->empresasAcceso()->pluck('empresas.id')
            ->push($user->empresa_id)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function ticket(Request $request): ?SupportTicket
    {
        return $this->visibles($request)->find((int) $request->route('id'));
    }

    private function user(Request $request): User
    {
        return $request->attributes->get('n8n_user');
    }

    private function noEncontrado(): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => 'no_encontrado', 'mensaje' => 'Ese ticket no existe o no lo puedes ver.'], 404);
    }
}
