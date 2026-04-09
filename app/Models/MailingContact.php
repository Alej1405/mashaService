<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailingContact extends Model
{
    use HasFactory, HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'mailing_group_id',
        'nombre',
        'email',
        'telefono',
        'notas',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function mailingGroup()
    {
        return $this->belongsTo(MailingGroup::class);
    }
}
