<?php

namespace App\Actions;

use App\Models\ConteoVoto;
use App\Models\Eleccion;
use App\Models\Lista;
use App\Models\Puesto;
use App\Models\Votante;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Único lugar donde se registra un voto.
 *
 * En una sola transacción: marca al votante como "ya votó" y suma 1 al conteo de la lista.
 * El conteo no guarda quién votó ni cuándo, por lo que no hay forma de ligar voto y votante.
 */
class EmitirVoto
{
    /**
     * @param  int|null  $listaId  null = voto en blanco
     *
     * @throws RuntimeException con mensaje apto para mostrar al alumno
     */
    public function __invoke(Eleccion $eleccion, Votante $votante, ?int $listaId, ?Puesto $puesto = null): void
    {
        DB::transaction(function () use ($eleccion, $votante, $listaId, $puesto) {
            // Releer dentro de la transacción (el lock de escritura ya está tomado: modo IMMEDIATE).
            $eleccion = Eleccion::findOrFail($eleccion->getKey());
            if (! $eleccion->estaAbierta()) {
                throw new RuntimeException('La votación no está abierta.');
            }

            $actual = Votante::where('eleccion_id', $eleccion->id)->findOrFail($votante->getKey());
            if ($actual->ya_voto) {
                throw new RuntimeException('Ud. ya ha votado.');
            }

            if ($listaId === null) {
                if (! $eleccion->permite_voto_en_blanco) {
                    throw new RuntimeException('Debe elegir una lista.');
                }
            } elseif (! Lista::where('eleccion_id', $eleccion->id)->whereKey($listaId)->exists()) {
                throw new RuntimeException('La lista elegida no es válida.');
            }

            $actual->update([
                'ya_voto' => true,
                'voto_en' => now(),
                'puesto_id' => $puesto?->id,
            ]);

            $conteo = ConteoVoto::firstOrCreate(
                ['eleccion_id' => $eleccion->id, 'lista_id' => $listaId],
                ['cantidad' => 0],
            );
            ConteoVoto::whereKey($conteo->id)->increment('cantidad');
        });
    }
}
