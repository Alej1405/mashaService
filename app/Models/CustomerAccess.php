<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceso del cliente al portal (auth). 1:1 opcional con Customer: solo se crea para
 * clientes con credencial o rol. El login del portal es por cédula/RUC; aquí viven la
 * contraseña (cambio en el perfil), la verificación de email y el rol super admin.
 */
class CustomerAccess extends Model
{
    use HasEmpresa;

    protected $table = 'customer_access';

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'password',
        'email_verified_at',
        'is_super_admin',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin'    => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
