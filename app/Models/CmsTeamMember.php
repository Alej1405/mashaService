<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsTeamMember extends Model
{
    use HasEmpresa;

    protected $table = 'cms_team_members';

    protected $fillable = [
        'empresa_id', 'nombre', 'cargo', 'bio', 'foto', 'sort_order', 'activo',
    ];

    protected $casts = ['activo' => 'boolean', 'sort_order' => 'integer'];
}
