<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Puesto extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Puesto $p) {
            $p->token ??= Str::random(48);
        });

        // Siempre se guarda normalizado ("AA-BB-CC-DD-EE-FF,..."), venga de la carga manual o del acceso directo.
        static::saving(function (Puesto $p) {
            $macs = self::normalizarMacs($p->mac);
            $p->mac = $macs === [] ? null : implode(',', $macs);
        });
    }

    /**
     * Acepta "aa:bb:cc:dd:ee:ff", "AA-BB-CC-DD-EE-FF" o "aabb.ccdd.eeff", varias separadas por coma,
     * punto y coma o salto de línea. Descarta lo que no sea una MAC de 12 dígitos hexadecimales.
     *
     * @return list<string>
     */
    public static function normalizarMacs(?string $texto): array
    {
        $macs = [];
        foreach (preg_split('/[,;\r\n]+/', (string) $texto) as $candidata) {
            $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $candidata));
            if (strlen($hex) === 12) {
                $macs[] = implode('-', str_split($hex, 2));
            }
        }

        return array_values(array_unique($macs));
    }

    public function estaVinculado(): bool
    {
        return $this->mac !== null && $this->mac !== '';
    }

    /** @return list<string> */
    public function macsVinculadas(): array
    {
        return self::normalizarMacs($this->mac);
    }

    /**
     * Sin vincular: cualquier PC puede registrarse. Vinculado: alcanza con que coincida una de las placas
     * (la PC puede usar cable o Wi-Fi).
     *
     * @param  list<string>  $presentadas  MAC ya normalizadas que informa el acceso directo
     */
    public function aceptaDispositivo(array $presentadas): bool
    {
        return ! $this->estaVinculado() || array_intersect($this->macsVinculadas(), $presentadas) !== [];
    }

    /** Primer uso con MAC informada: vincula el puesto (solo si nadie lo vinculó antes). */
    public function vincular(array $macs): bool
    {
        if ($macs === [] || $this->estaVinculado()) {
            return false;
        }

        return static::whereKey($this->getKey())->whereNull('mac')->update(['mac' => implode(',', $macs)]) === 1;
    }
}
