<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidato extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(fn (Candidato $candidato) => $candidato->lista?->eleccion?->asegurarEnPreparacion());
        static::deleting(fn (Candidato $candidato) => $candidato->lista?->eleccion?->asegurarEnPreparacion());
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(Lista::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function nombreCompleto(): string
    {
        return mb_strtoupper($this->apellido).', '.$this->nombre;
    }
}
