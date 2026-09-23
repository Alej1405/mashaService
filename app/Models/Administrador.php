<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Representante legal, gerente y demás administradores inscritos. */
class Administrador extends Model
{
    use HasEmpresa;

    protected $table = 'administradores';

    protected $fillable = ['empresa_id','identificacion','nombre','cargo','desde','hasta'];

    protected $casts = ['desde' => 'date', 'hasta' => 'date'];

    public function getVigenteAttribute(): bool
    {
        return $this->hasta === null || $this->hasta->isFuture();
    }
}
