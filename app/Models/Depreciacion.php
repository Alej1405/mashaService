<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciacion extends Model
{
    protected $table = 'depreciaciones';

    protected $fillable = ['activo_fijo_id','journal_entry_id','anio','mes','valor'];

    protected $casts = ['valor' => 'decimal:2'];

    public function activoFijo(): BelongsTo { return $this->belongsTo(ActivoFijo::class); }
}
