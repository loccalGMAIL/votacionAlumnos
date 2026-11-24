<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(fn (Cargo $cargo) => $cargo->eleccion?->asegurarEnPreparacion());
        static::deleting(fn (Cargo $cargo) => $cargo->eleccion?->asegurarEnPreparacion());
    }

    public function eleccion(): BelongsTo
    {
        return $this->belongsTo(Eleccion::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }
}
