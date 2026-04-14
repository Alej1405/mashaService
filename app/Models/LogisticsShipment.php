<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class LogisticsShipment extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_shipments';

    protected $fillable = [
        'empresa_id',
        'bodega_id',
        'consignatario_id',
        'numero_embarque',
        'tipo',
        'estado',
        'fecha_embarque',
        'fecha_llegada_ecuador',
        'numero_declaracion_aduana',
        'numero_guia_aerea',
        'valor_total_declarado',
        'peso_total_kg',
        'impuestos_pagados',
        'observaciones',
    ];

    protected $casts = [
        'fecha_embarque'        => 'date',
        'fecha_llegada_ecuador' => 'date',
        'valor_total_declarado' => 'decimal:2',
        'peso_total_kg'         => 'decimal:3',
        'impuestos_pagados'     => 'decimal:2',
    ];

    // ── Tipos ────────────────────────────────────────────────────────────────
    public const TIPOS = [
        'individual'  => 'Individual',
        'consolidado' => 'Consolidado',
        'fraccionado' => 'Fraccionado',
    ];

    // ── Estados — Pre-embarque + Aduana SENAE Courier (Régimen 91) ───────────
    public const ESTADOS = [
        // Estado inicial al crear
        'embarque_solicitado'        => [
            'label' => 'Embarque Solicitado',
            'color' => '#64748b',
            'grupo' => 'Pre-embarque',
        ],
        // Pre-embarque
        'carga_registrada'           => [
            'label' => 'Carga Registrada',
            'color' => '#6366f1',
            'grupo' => 'Pre-embarque',
        ],
        'consolidando'               => [
            'label' => 'En Consolidación',
            'color' => '#8b5cf6',
            'grupo' => 'Pre-embarque',
        ],
        'fraccionamiento_en_proceso' => [
            'label' => 'Fraccionamiento en Proceso',
            'color' => '#a855f7',
            'grupo' => 'Pre-embarque',
        ],
        'carga_embarcada'            => [
            'label' => 'Carga Embarcada',
            'color' => '#3b82f6',
            'grupo' => 'Pre-embarque',
        ],
        // Aduana Ecuador — SENAE Régimen 91 Courier
        'en_aduana'                  => [
            'label' => 'En Aduana',
            'color' => '#f59e0b',
            'grupo' => 'Aduana Ecuador',
        ],
        'declaracion_transmitida'    => [
            'label' => 'Declaración Transmitida',
            'color' => '#f97316',
            'grupo' => 'Aduana Ecuador',
        ],
        'aforo_automatico'           => [
            'label' => 'Aforo Automático',
            'color' => '#10b981',
            'grupo' => 'Aduana Ecuador',
        ],
        'aforo_documental'           => [
            'label' => 'Aforo Documental',
            'color' => '#f97316',
            'grupo' => 'Aduana Ecuador',
        ],
        'aforo_fisico'               => [
            'label' => 'Aforo Físico',
            'color' => '#ef4444',
            'grupo' => 'Aduana Ecuador',
        ],
        'liquidada'                  => [
            'label' => 'Liquidada',
            'color' => '#06b6d4',
            'grupo' => 'Aduana Ecuador',
        ],
        'pagada'                     => [
            'label' => 'Pagada',
            'color' => '#84cc16',
            'grupo' => 'Aduana Ecuador',
        ],
        'autorizado_salida'          => [
            'label' => 'Autorizado para Salida',
            'color' => '#22c55e',
            'grupo' => 'Aduana Ecuador',
        ],
        'entregada'                  => [
            'label' => 'Entregada',
            'color' => '#15803d',
            'grupo' => 'Finalizado',
        ],
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(LogisticsBodega::class, 'bodega_id');
    }

    public function consignatario(): BelongsTo
    {
        return $this->belongsTo(LogisticsConsignatario::class, 'consignatario_id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            LogisticsPackage::class,
            'logistics_shipment_packages',
            'shipment_id',
            'package_id'
        )->withTimestamps();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(LogisticsDocument::class, 'documentable');
    }

    public function history(): HasMany
    {
        return $this->hasMany(LogisticsShipmentHistory::class, 'shipment_id')->latest();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function generarNumero(int $empresaId): string
    {
        $year = now()->format('Y');
        $last = static::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return 'EMB-' . $year . '-' . str_pad($last, 5, '0', STR_PAD_LEFT);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado]['label'] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADOS[$this->estado]['color'] ?? '#6b7280';
    }
}
