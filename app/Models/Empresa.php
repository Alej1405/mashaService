<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Panel;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class Empresa extends Model implements HasName
{
    use HasApiTokens;

    protected $table = 'empresas';
    protected $fillable = [
        'name', 'email', 'slug', 'activo',
        'tipo_persona', 'tipo_identificacion', 'numero_identificacion', 'direccion', 'actividad_economica',
        // Plan de suscripción
        'plan',
        // Credenciales Mailgun (por empresa)
        'mailgun_api_key', 'mailgun_domain', 'mailgun_from_email', 'mailgun_from_name',
        // Logo de la empresa
        'logo_path',
        // Credenciales SMTP personalizadas (por empresa)
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            if (empty($empresa->slug)) {
                $empresa->slug = \Illuminate\Support\Str::slug($empresa->name);
            }
        });
    }

    public static function getTenantRouteKeyName(): string
    {
        return 'slug';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function accountPlans(): HasMany
    {
        return $this->hasMany(AccountPlan::class, 'empresa_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'empresa_id');
    }

    public function measurementUnits(): HasMany
    {
        return $this->hasMany(MeasurementUnit::class, 'empresa_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'empresa_id');
    }

    public function inventoryItemFiles(): HasMany
    {
        return $this->hasMany(InventoryItemFile::class, 'empresa_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'empresa_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'empresa_id');
    }

    /**
     * Relaciones del Módulo de Ventas
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'empresa_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'empresa_id');
    }

    /**
     * Relaciones del Módulo de Manufactura (Producción)
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'empresa_id');
    }

    /**
     * Relaciones del Módulo de Finanzas
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'empresa_id');
    }

    /**
     * Relaciones del Módulo de Tesorería
     */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'empresa_id');
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class, 'empresa_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'empresa_id');
    }

    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class, 'empresa_id');
    }

    public function creditCardMovements(): HasMany
    {
        return $this->hasMany(CreditCardMovement::class, 'empresa_id');
    }

    public function mailTemplates(): HasMany
    {
        return $this->hasMany(\App\Models\MailTemplate::class, 'empresa_id');
    }

    public function cmsTeamMembers(): HasMany
    {
        return $this->hasMany(\App\Models\CmsTeamMember::class, 'empresa_id');
    }

    public function cmsFaqs(): HasMany
    {
        return $this->hasMany(\App\Models\CmsFaq::class, 'empresa_id');
    }

    public function cmsServices(): HasMany
    {
        return $this->hasMany(\App\Models\CmsService::class, 'empresa_id');
    }

    public function cmsClientLogos(): HasMany
    {
        return $this->hasMany(\App\Models\CmsClientLogo::class, 'empresa_id');
    }

    public function cmsTestimonials(): HasMany
    {
        return $this->hasMany(\App\Models\CmsTestimonial::class, 'empresa_id');
    }

    public function cmsPosts(): HasMany
    {
        return $this->hasMany(\App\Models\CmsPost::class, 'empresa_id');
    }

    public function mailingContacts(): HasMany
    {
        return $this->hasMany(\App\Models\MailingContact::class, 'empresa_id');
    }

    public function mailCampaigns(): HasMany
    {
        return $this->hasMany(\App\Models\MailCampaign::class, 'empresa_id');
    }

    public function cmsTerminos(): HasMany
    {
        return $this->hasMany(\App\Models\CmsTerminos::class, 'empresa_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(\App\Models\SupportTicket::class, 'empresa_id');
    }

    public function productDesigns(): HasMany
    {
        return $this->hasMany(\App\Models\ProductDesign::class, 'empresa_id');
    }

    public function itemRequests(): HasMany
    {
        return $this->hasMany(\App\Models\ItemRequest::class, 'empresa_id');
    }

    public function costosFijos(): HasMany
    {
        return $this->hasMany(\App\Models\CostoFijo::class, 'empresa_id');
    }

    public function storeProducts(): HasMany
    {
        return $this->hasMany(\App\Models\StoreProduct::class, 'empresa_id');
    }

    public function storeCategories(): HasMany
    {
        return $this->hasMany(\App\Models\StoreCategory::class, 'empresa_id');
    }

    public function storeOrders(): HasMany
    {
        return $this->hasMany(\App\Models\StoreOrder::class, 'empresa_id');
    }

    public function storeCustomers(): HasMany
    {
        return $this->hasMany(\App\Models\StoreCustomer::class, 'empresa_id');
    }

    public function storeCoupons(): HasMany
    {
        return $this->hasMany(\App\Models\StoreCoupon::class, 'empresa_id');
    }
}
