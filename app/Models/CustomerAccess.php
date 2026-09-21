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
        'verification_token',
        'verification_sent_at',
        'reset_token',
        'reset_expires_at',
    ];

    protected $hidden = ['password', 'verification_token', 'reset_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'is_super_admin'       => 'boolean',
        'verification_sent_at' => 'datetime',
        'reset_expires_at'     => 'datetime',
    ];

    public function estaVerificado(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** El token de reset caduca a la hora; se limpia al usarlo. */
    public function resetTokenVigente(string $token): bool
    {
        return $this->reset_token !== null
            && $this->reset_expires_at !== null
            && $this->reset_expires_at->isFuture()
            && hash_equals($this->reset_token, $token);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
