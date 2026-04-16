<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsPackageItem extends Model
{
    protected $table = 'logistics_package_items';

    protected $fillable = [
        'logistics_package_id',
        'nombre',
        'descripcion',
        'valor',
        'foto_path',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(LogisticsPackage::class, 'logistics_package_id');
    }
}
