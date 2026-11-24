<?php

namespace App\Livewire;

use App\Actions\EmitirVoto;
use App\Http\Middleware\PuestoRegistrado;
use App\Models\Eleccion;
use App\Models\Puesto;
use App\Models\Votante;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.kiosco')]
#[Title('Votá')]
class CuartoOscuro extends Component
{
    /** inicio → confirmar → boleta → gracias */
    #[Locked]
    public string $paso = 'inicio';

    // Locked: el navegador no puede cambiar a qué votante corresponde esta sesión.
    #[Locked]
    public ?int $votanteId = null;

    public string $dni = '';

    /** null = todavía sin elegir; 0 = voto en blanco; >0 = id de lista */
    public ?int $seleccion = null;

    public ?string $error = null;

    public function buscar(): void
    {
        $this->error = null;
        $eleccion = Eleccion::actual();
        $clave = 'dni-puesto:'.($this->puesto()?->id ?? request()->ip());

        if (RateLimiter::tooManyAttempts($clave, 30)) {
            $this->error = 'Demasiados intentos. Espere un momento.';

            return;
        }
        RateLimiter::hit($clave, 60);

        $dni = Votante::normalizarDni($this->dni);
        if (strlen($dni) < 6) {
            $this->error = 'Debe ingresar un DNI válido.';

            return;
        }
        if (! $eleccion || ! $eleccion->estaAbierta()) {
            $this->error = 'La votación no está abierta en este momento.';

            return;
        }

        $votante = $eleccion->votantes()->where('dni', $dni)->first();
        if (! $votante) {
            $this->error = 'Su DNI no se encuentra en el padrón.';

            return;
        }
        if ($votante->ya_voto) {
            $this->error = 'Ud. ya ha votado.';

            return;
        }

        $this->votanteId = $votante->id;
        $this->paso = 'confirmar';
    }

    public function esMiIdentidad(): void
    {
        abort_unless($this->votanteId, 403);
        $this->seleccion = null;
        $this->paso = 'boleta';
    }

    public function noSoyYo(): void
    {
        $this->reiniciar();
    }

    public function elegir(int $listaId): void
    {
        $this->seleccion = $listaId;
    }

    public function votar(): void
    {
        $this->error = null;
        $eleccion = Eleccion::actual();

        if ($this->seleccion === null) {
            $this->error = 'Debe elegir una opción.';

            return;
        }

        try {
            (new EmitirVoto)($eleccion, Votante::findOrFail($this->votanteId), $this->seleccion === 0 ? null : $this->seleccion, $this->puesto());
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();

            return;
        }

        // Borrar todo rastro de la elección en el componente antes de mostrar "gracias".
        $this->reset('dni', 'seleccion', 'votanteId');
        $this->paso = 'gracias';
    }

    public function reiniciar(): void
    {
        $this->reset('dni', 'seleccion', 'votanteId', 'error');
        $this->paso = 'inicio';
    }

    private function puesto(): ?Puesto
    {
        $token = request()->cookie(PuestoRegistrado::COOKIE);

        return $token ? Puesto::where('token', $token)->first() : null;
    }

    public function render()
    {
        $eleccion = Eleccion::actual();

        return view('livewire.cuarto-oscuro', [
            'eleccion' => $eleccion,
            'votante' => $this->votanteId ? Votante::find($this->votanteId) : null,
            'listas' => $this->paso === 'boleta' ? $eleccion->listas()->with(['candidatos.cargo'])->get() : collect(),
            'cargos' => $this->paso === 'boleta' ? $eleccion->cargos : collect(),
        ]);
    }
}
