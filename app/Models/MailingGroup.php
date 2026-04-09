<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailingGroup extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    const CAPACITY = 1500;

    private static array $ORDINALS = [
        'Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis', 'Siete', 'Ocho', 'Nueve', 'Diez',
        'Once', 'Doce', 'Trece', 'Catorce', 'Quince', 'Dieciséis', 'Diecisiete', 'Dieciocho',
        'Diecinueve', 'Veinte', 'Veintiuno', 'Veintidós', 'Veintitrés', 'Veinticuatro', 'Veinticinco',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(MailingContact::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MailCampaign::class);
    }

    /**
     * Devuelve el group_id al que asignar el próximo contacto de la empresa.
     * Rellena el último grupo con espacio disponible; si no hay, crea uno nuevo.
     */
    public static function assignGroup(int $empresaId): int
    {
        $group = static::where('empresa_id', $empresaId)
            ->withCount('contacts')
            ->having('contacts_count', '<', static::CAPACITY)
            ->orderBy('sort_order')
            ->first();

        if ($group) {
            return $group->id;
        }

        return static::createNextGroup($empresaId)->id;
    }

    /**
     * Versión optimizada para importaciones masivas.
     * El array $state se pasa por referencia para evitar consultas por cada contacto.
     * $state = ['id' => int, 'remaining' => int]
     */
    public static function assignGroupBatch(int $empresaId, array &$state): int
    {
        if (empty($state) || $state['remaining'] <= 0) {
            $groupId = static::assignGroup($empresaId);
            $count   = static::withCount('contacts')->find($groupId)?->contacts_count ?? 0;
            $state   = ['id' => $groupId, 'remaining' => static::CAPACITY - $count];
        }

        $state['remaining']--;

        return $state['id'];
    }

    /** Crea el siguiente grupo en la secuencia para la empresa. */
    private static function createNextGroup(int $empresaId): static
    {
        $nextOrder = ((int) static::where('empresa_id', $empresaId)->max('sort_order')) + 1;
        $ordinals  = static::$ORDINALS;
        $name      = 'Grupo ' . ($ordinals[$nextOrder - 1] ?? $nextOrder);

        return static::create([
            'empresa_id'  => $empresaId,
            'name'        => $name,
            'sort_order'  => $nextOrder,
        ]);
    }

    /** Reagrupa todos los contactos activos de la empresa en grupos de 1500. */
    public static function rebalance(int $empresaId): void
    {
        // Borrar grupos vacíos existentes
        static::where('empresa_id', $empresaId)
            ->withCount('contacts')
            ->having('contacts_count', 0)
            ->get()
            ->each->delete();

        $contacts = MailingContact::where('empresa_id', $empresaId)
            ->orderBy('id')
            ->pluck('id');

        // Asignar grupos en lotes de CAPACITY
        $chunks = $contacts->chunk(static::CAPACITY);
        $order  = 1;

        // Obtener grupos existentes ordenados
        $existingGroups = static::where('empresa_id', $empresaId)
            ->orderBy('sort_order')
            ->get();

        $groupIndex = 0;

        foreach ($chunks as $chunk) {
            if (isset($existingGroups[$groupIndex])) {
                $group = $existingGroups[$groupIndex];
            } else {
                $ordinals = static::$ORDINALS;
                $name     = 'Grupo ' . ($ordinals[$order - 1] ?? $order);
                $group    = static::create([
                    'empresa_id' => $empresaId,
                    'name'       => $name,
                    'sort_order' => $order,
                ]);
            }

            MailingContact::whereIn('id', $chunk)->update(['mailing_group_id' => $group->id]);
            $groupIndex++;
            $order++;
        }
    }
}
