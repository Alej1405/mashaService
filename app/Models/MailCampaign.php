<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailCampaign extends Model
{
    use HasFactory, HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'mail_template_id',
        'mailing_group_id',
        'name',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'sent_at',
        'error_log',
    ];

    protected $casts = [
        'sent_at'          => 'datetime',
        'total_recipients' => 'integer',
        'sent_count'       => 'integer',
        'failed_count'     => 'integer',
    ];

    public function mailTemplate(): BelongsTo
    {
        return $this->belongsTo(MailTemplate::class);
    }

    public function mailingGroup(): BelongsTo
    {
        return $this->belongsTo(MailingGroup::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'   => 'Borrador',
            'sending' => 'Enviando…',
            'sent'    => 'Enviada',
            'failed'  => 'Con errores',
            default   => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'   => 'gray',
            'sending' => 'warning',
            'sent'    => 'success',
            'failed'  => 'danger',
            default   => 'gray',
        };
    }
}
