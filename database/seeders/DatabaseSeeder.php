<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    private const CLAVE_POR_DEFECTO = 'cambiar-esta-clave';

    /**
     * Usuarios iniciales. En producción es obligatorio definir en el .env:
     *   ADMIN_EMAIL, ADMIN_PASSWORD, DIRECTIVO_EMAIL, DIRECTIVO_PASSWORD
     * (fuera de producción se usan valores de ejemplo).
     */
    public function run(): void
    {
        $this->exigirCredencialesPropiasEnProduccion();

        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@colegio.test')], [
            'name' => 'Administrador', 'password' => env('ADMIN_PASSWORD', self::CLAVE_POR_DEFECTO), 'rol' => User::ROL_ADMIN,
        ]);
        User::updateOrCreate(['email' => env('DIRECTIVO_EMAIL', 'directivo@colegio.test')], [
            'name' => 'Directivo', 'password' => env('DIRECTIVO_PASSWORD', self::CLAVE_POR_DEFECTO), 'rol' => User::ROL_DIRECTIVO,
        ]);
    }

    /** El código es público: una clave por defecto en producción sería una clave que todos conocen. */
    private function exigirCredencialesPropiasEnProduccion(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        foreach (['ADMIN_EMAIL', 'ADMIN_PASSWORD', 'DIRECTIVO_EMAIL', 'DIRECTIVO_PASSWORD'] as $variable) {
            if (! env($variable)) {
                throw new RuntimeException("Defina {$variable} en el .env antes de crear los usuarios en producción.");
            }
        }

        foreach (['ADMIN_PASSWORD', 'DIRECTIVO_PASSWORD'] as $variable) {
            if (env($variable) === self::CLAVE_POR_DEFECTO) {
                throw new RuntimeException("{$variable} no puede ser la clave por defecto: elija una propia.");
            }
        }
    }
}
