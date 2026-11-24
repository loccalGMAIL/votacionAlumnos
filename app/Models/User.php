<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_ADMIN = 'admin';

    public const ROL_DIRECTIVO = 'directivo';

    /** @var array<string, string> */
    public const ROLES = [
        self::ROL_ADMIN => 'Administrador',
        self::ROL_DIRECTIVO => 'Directivo',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_acceso_at' => 'datetime',
        ];
    }

    public function esUltimoAdminActivo(): bool
    {
        return $this->esAdmin()
            && $this->activo
            && ! static::query()
                ->where('rol', self::ROL_ADMIN)
                ->where('activo', true)
                ->whereKeyNot($this->getKey())
                ->exists();
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esDirectivo(): bool
    {
        return $this->rol === self::ROL_DIRECTIVO;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && ($this->esAdmin() || $this->esDirectivo());
    }
}
