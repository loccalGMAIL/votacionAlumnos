<?php

namespace Database\Seeders;

use App\Enums\EstadoEleccion;
use App\Models\Candidato;
use App\Models\Eleccion;
use App\Models\Puesto;
use App\Models\Votante;
use Illuminate\Database\Seeder;

/** Elección de ejemplo para probar: php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $e = Eleccion::create([
            'nombre' => 'Elecciones Centro de Estudiantes',
            'anio' => (int) date('Y'),
            'estado' => EstadoEleccion::Borrador,
        ]);

        $cargos = collect(['Presidente', 'Vicepresidente', 'Sec. General', 'Sec. de Actas', 'Sec. de Finanzas',
            'Sec. de Cultura', 'Sec. de Deportes', 'Sec. de Prensa y Difusión', 'Sec. Pastoral'])
            ->map(fn ($n, $i) => $e->cargos()->create(['nombre' => $n, 'orden' => $i + 1]));

        $listas = [
            ['numero' => 1, 'nombre' => 'Estudiantes Unidos', 'sigla' => 'EU', 'color' => '#dc2626'],
            ['numero' => 2, 'nombre' => 'Grupo Estudiantil por el Progreso', 'sigla' => 'GEP', 'color' => '#2563eb'],
        ];
        foreach ($listas as $l) {
            $lista = $e->listas()->create($l);
            foreach ($cargos as $i => $cargo) {
                Candidato::create([
                    'lista_id' => $lista->id, 'cargo_id' => $cargo->id,
                    'apellido' => "Apellido{$l['numero']}-".($i + 1), 'nombre' => 'Nombre'.($i + 1), 'curso' => '6° A',
                ]);
            }
        }

        for ($i = 1; $i <= 30; $i++) {
            Votante::create([
                'eleccion_id' => $e->id, 'dni' => (string) (40000000 + $i),
                'apellido' => "Alumno{$i}", 'nombre' => 'Demo', 'curso' => ($i % 2 ? '5° A' : '5° B'),
            ]);
        }

        foreach (range(1, 3) as $n) {
            Puesto::create(['nombre' => "PC {$n}"]);
        }
    }
}
