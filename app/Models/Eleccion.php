<?php

namespace App\Models;

use App\Enums\EstadoEleccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

class Eleccion extends Model
{
    protected $table = 'elecciones';

    protected $guarded = [];

    /** Defaults también en memoria (la DB los tiene, pero un modelo recién creado no se relee). */
    protected $attributes = [
        'estado' => 'borrador',
        'permite_voto_en_blanco' => true,
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoEleccion::class,
            'permite_voto_en_blanco' => 'boolean',
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'publicada_en' => 'datetime',
        ];
    }

    /**
     * Una vez abierta la votación no se puede borrar la elección ni cambiar si admite voto en blanco
     * (borrarla arrastraría en cascada la urna). Nombre e IPs siguen siendo editables.
     */
    protected static function booted(): void
    {
        static::deleting(function (Eleccion $eleccion) {
            $eleccion->asegurarEnPreparacion('No se puede eliminar una elección que ya fue abierta.');
        });

        static::updating(function (Eleccion $eleccion) {
            $original = $eleccion->getOriginal('estado');
            $original = $original instanceof EstadoEleccion ? $original : EstadoEleccion::from($original);

            if ($original !== EstadoEleccion::Borrador && $eleccion->isDirty('permite_voto_en_blanco')) {
                throw new RuntimeException('Con la elección ya abierta no se puede cambiar el voto en blanco.');
            }
        });
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class)->orderBy('orden');
    }

    public function listas(): HasMany
    {
        return $this->hasMany(Lista::class)->orderBy('numero');
    }

    public function votantes(): HasMany
    {
        return $this->hasMany(Votante::class);
    }

    public function conteos(): HasMany
    {
        return $this->hasMany(ConteoVoto::class);
    }

    /** La elección "en curso": la más reciente que no está publicada, o la última publicada. */
    public static function actual(): ?self
    {
        return static::query()->orderByRaw("estado = 'publicada'")->latest('anio')->latest('id')->first();
    }

    public function estaAbierta(): bool
    {
        return $this->estado === EstadoEleccion::Abierta;
    }

    public function enPreparacion(): bool
    {
        return $this->estado === EstadoEleccion::Borrador;
    }

    /** El padrón solo admite altas antes de cerrar la votación (un alumno faltante se puede sumar el mismo día). */
    public function admiteAltasDePadron(): bool
    {
        return in_array($this->estado, [EstadoEleccion::Borrador, EstadoEleccion::Abierta], true);
    }

    /** @throws RuntimeException si la elección ya salió de "En preparación" */
    public function asegurarEnPreparacion(string $mensaje = 'La elección ya fue abierta: no se pueden modificar listas, cargos ni candidatos.'): void
    {
        if (! $this->enPreparacion()) {
            throw new RuntimeException($mensaje);
        }
    }

    public function abrir(): void
    {
        if ($this->estado !== EstadoEleccion::Borrador) {
            throw new RuntimeException('Solo se puede abrir una elección en preparación.');
        }
        if ($this->listas()->count() < 1) {
            throw new RuntimeException('Cargue al menos una lista antes de abrir la votación.');
        }
        if ($this->votantes()->count() < 1) {
            throw new RuntimeException('El padrón está vacío.');
        }
        $this->update(['estado' => EstadoEleccion::Abierta, 'abierta_en' => now()]);
    }

    public function cerrar(): void
    {
        if ($this->estado !== EstadoEleccion::Abierta) {
            throw new RuntimeException('La votación no está abierta.');
        }
        $this->update(['estado' => EstadoEleccion::Cerrada, 'cerrada_en' => now()]);
    }

    /** Deshace un cierre hecho por error: solo de "cerrada" a "abierta". */
    public function reabrir(): void
    {
        if ($this->estado !== EstadoEleccion::Cerrada) {
            throw new RuntimeException('Solo se puede reabrir una votación cerrada.');
        }
        $this->update(['estado' => EstadoEleccion::Abierta, 'cerrada_en' => null]);
    }

    public function publicar(): void
    {
        if ($this->estado !== EstadoEleccion::Cerrada) {
            throw new RuntimeException('Primero debe cerrarse la votación.');
        }
        $this->update(['estado' => EstadoEleccion::Publicada, 'publicada_en' => now()]);
    }

    /**
     * Resultados por lista, de mayor a menor, más blancos y total.
     *
     * @return array{listas: Collection, blancos: int, total: int}
     */
    public function resultados(): array
    {
        $conteos = $this->conteos()->pluck('cantidad', 'lista_id');
        $listas = $this->listas->map(fn (Lista $l) => ['lista' => $l, 'votos' => (int) ($conteos[$l->id] ?? 0)])
            ->sortByDesc('votos')->values();
        $blancos = (int) ($conteos[''] ?? 0); // lista_id null queda con clave ''
        $total = $listas->sum('votos') + $blancos;
        $listas = $listas->map(fn ($r) => $r + ['porcentaje' => $total ? round($r['votos'] * 100 / $total, 1) : 0.0]);

        return ['listas' => $listas, 'blancos' => $blancos, 'total' => $total];
    }

    public function ipPermitida(?string $ip): bool
    {
        $lista = collect(preg_split('/\R/', (string) $this->ips_permitidas))
            ->map(fn ($l) => trim($l))->filter();

        return $lista->isEmpty() || ($ip !== null && IpUtils::checkIp($ip, $lista->values()->all()));
    }

    public function totalVotos(): int
    {
        return (int) $this->conteos()->sum('cantidad');
    }
}
