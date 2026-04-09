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

    protected static function boot(): void
    {
        parent::boot();

        // Auto-asignar grupo al crear un contacto (aplica siempre: form, import, API)
        static::creating(function (self $contact) {
            if (empty($contact->mailing_group_id) && ! empty($contact->empresa_id)) {
                $contact->mailing_group_id = MailingGroup::assignGroup($contact->empresa_id);
            }
        });
    }

    public function mailingGroup()
    {
        return $this->belongsTo(MailingGroup::class);
    }
}
