<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsShipmentHistory extends Model
{
    protected $table = 'logistics_shipment_history';

    protected $fillable = [
        'shipment_id',
        'tipo',
        'estado_anterior',
        'estado_nuevo',
        'descripcion',
        'user_id',
        'user_nombre',
    ];

    // ── Tipos de evento ──────────────────────────────────────────────────────
    public const TIPOS = [
        'cambio_estado' => ['label' => 'Cambio de estado', 'icon' => '🔄', 'color' => 'blue'],
        'nota'          => ['label' => 'Nota',             'icon' => '📝', 'color' => 'gray'],
        'documento'     => ['label' => 'Documento',        'icon' => '📄', 'color' => 'yellow'],
        'paquete'       => ['label' => 'Paquetes',         'icon' => '📦', 'color' => 'purple'],
        'creacion'      => ['label' => 'Creación',         'icon' => '✅', 'color' => 'green'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(LogisticsShipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ── Helper estático ───────────────────────────────────────────────────────
    public static function registrar(
        int $shipmentId,
        string $tipo,
        string $descripcion,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
    ): self {
        return static::create([
            'shipment_id'    => $shipmentId,
            'tipo'           => $tipo,
            'estado_anterior'=> $estadoAnterior,
            'estado_nuevo'   => $estadoNuevo,
            'descripcion'    => $descripcion,
            'user_id'        => auth()->id(),
            'user_nombre'    => auth()->user()?->name ?? 'Sistema',
        ]);
    }
}
