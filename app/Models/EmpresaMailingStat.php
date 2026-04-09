<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaMailingStat extends Model
{
    protected $fillable = [
        'empresa_id',
        'accepted',
        'delivered',
        'failed',
        'opened',
        'clicked',
        'bounced',
        'complained',
        'unsubscribed',
        'last_synced_at',
    ];

    protected $casts = [
        'accepted'       => 'integer',
        'delivered'      => 'integer',
        'failed'         => 'integer',
        'opened'         => 'integer',
        'clicked'        => 'integer',
        'bounced'        => 'integer',
        'complained'     => 'integer',
        'unsubscribed'   => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
