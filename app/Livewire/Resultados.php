<?php

namespace App\Livewire;

use App\Enums\EstadoEleccion;
use App\Models\Eleccion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kiosco')]
#[Title('Resultados')]
class Resultados extends Component
{
    public function mount(): void
    {
        $eleccion = Eleccion::actual();

        // Hasta que el directivo no habilite, el servidor no entrega ningún número.
        if (! $eleccion || $eleccion->estado !== EstadoEleccion::Publicada) {
            abort(response()->view('resultados.no-habilitados', [], 403));
        }
    }

    public function render()
    {
        $eleccion = Eleccion::actual();
        $res = $eleccion->resultados();
        $primero = $res['listas']->first();
        $segundo = $res['listas']->get(1);
        $hayGanador = $primero && $primero['votos'] > 0 && (! $segundo || $primero['votos'] > $segundo['votos']);

        return view('livewire.resultados', [
            'eleccion' => $eleccion,
            'res' => $res,
            'ganador' => $hayGanador ? $primero : null,
            'padron' => $eleccion->votantes()->count(),
        ]);
    }
}
