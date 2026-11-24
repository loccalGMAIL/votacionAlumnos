<?php

namespace App\Imports;

use App\Models\Eleccion;
use App\Models\Votante;
use InvalidArgumentException;

/**
 * Importa el padrón desde un CSV con encabezado: dni, apellido, nombre, curso.
 * Acepta separador "," o ";" (Excel en español) y UTF-8 o Windows-1252.
 * Los DNI repetidos se actualizan (salvo que ya hayan votado: esos no se tocan).
 */
class PadronImport
{
    /** @return array{creados:int, actualizados:int, omitidos:int, errores:array<int,string>} */
    public function __invoke(Eleccion $eleccion, string $ruta): array
    {
        if (! $eleccion->admiteAltasDePadron()) {
            throw new InvalidArgumentException('La votación de esa elección ya está cerrada: no se puede modificar el padrón.');
        }

        $contenido = file_get_contents($ruta);
        if ($contenido === false || trim($contenido) === '') {
            throw new InvalidArgumentException('El archivo está vacío.');
        }
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $lineas = preg_split('/\R/', trim($contenido));
        $sep = substr_count($lineas[0], ';') > substr_count($lineas[0], ',') ? ';' : ',';
        $cabecera = array_map(fn ($c) => $this->clave($c), str_getcsv(array_shift($lineas), $sep));

        foreach (['dni', 'apellido', 'nombre'] as $req) {
            if (! in_array($req, $cabecera, true)) {
                throw new InvalidArgumentException("Falta la columna \"{$req}\" en la primera fila.");
            }
        }

        $res = ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'errores' => []];

        foreach ($lineas as $i => $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $n = $i + 2; // número de fila en el archivo
            $fila = array_combine($cabecera, array_pad(array_slice(str_getcsv($linea, $sep), 0, count($cabecera)), count($cabecera), ''));

            $dni = Votante::normalizarDni($fila['dni'] ?? '');
            $apellido = trim($fila['apellido'] ?? '');
            $nombre = trim($fila['nombre'] ?? '');
            if (strlen($dni) < 6 || strlen($dni) > 9 || $apellido === '' || $nombre === '') {
                $res['errores'][] = "Fila {$n}: datos incompletos o DNI inválido.";

                continue;
            }

            $votante = Votante::firstOrNew(['eleccion_id' => $eleccion->id, 'dni' => $dni]);
            if ($votante->exists && $votante->ya_voto) {
                $res['omitidos']++;

                continue;
            }
            $existia = $votante->exists;
            $votante->fill(['apellido' => $apellido, 'nombre' => $nombre, 'curso' => trim($fila['curso'] ?? '') ?: null])->save();
            $res[$existia ? 'actualizados' : 'creados']++;
        }

        return $res;
    }

    private function clave(string $c): string
    {
        return strtolower(trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $c) ?: $c));
    }
}
