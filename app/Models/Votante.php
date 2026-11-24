<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class Votante extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ya_voto' => 'boolean', 'voto_en' => 'datetime'];
    }

    /** Quien ya votó no se borra ni cambia de identidad: se perdería el control de participación. */
    protected static function booted(): void
    {
        static::deleting(function (Votante $votante) {
            if ($votante->ya_voto) {
                throw new RuntimeException('No se puede eliminar a un votante que ya votó.');
            }
        });

        static::updating(function (Votante $votante) {
            if ($votante->getOriginal('ya_voto') && $votante->isDirty(['dni', 'eleccion_id', 'ya_voto', 'puesto_id'])) {
                throw new RuntimeException('No se pueden modificar los datos de voto de quien ya votó.');
            }
        });
    }

    public function eleccion(): BelongsTo
    {
        return $this->belongsTo(Eleccion::class);
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class);
    }

    public function nombreCompleto(): string
    {
        return mb_strtoupper($this->apellido).', '.$this->nombre;
    }

    /** Deja solo dígitos: "40.123.456" => "40123456". */
    public static function normalizarDni(?string $dni): string
    {
        return preg_replace('/\D/', '', (string) $dni);
    }
}
