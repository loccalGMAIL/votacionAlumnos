<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Urna: totales por lista (lista_id null = voto en blanco). Sin votante ni fechas, a propósito. */
class ConteoVoto extends Model
{
    protected $table = 'conteo_votos';

    public $timestamps = false;

    protected $guarded = [];

    public function lista(): BelongsTo
    {
        return $this->belongsTo(Lista::class);
    }
}
