<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lista extends Model
{
    protected $guarded = [];

    /** Fuera de "En preparación" la boleta es intocable: borrar una lista borraría sus votos. */
    protected static function booted(): void
    {
        static::saving(fn (Lista $lista) => $lista->eleccion?->asegurarEnPreparacion());
        static::deleting(fn (Lista $lista) => $lista->eleccion?->asegurarEnPreparacion());
    }

    public function eleccion(): BelongsTo
    {
        return $this->belongsTo(Eleccion::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    public function conteo(): HasMany
    {
        return $this->hasMany(ConteoVoto::class);
    }
}
