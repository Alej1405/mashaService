<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model implements HasName
{
    protected $fillable = ['name', 'email'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
