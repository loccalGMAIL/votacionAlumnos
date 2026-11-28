<?php

namespace App\Filament\Concerns;

use App\Models\Eleccion;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Acciones del directivo sobre la elección actual: iniciar, detener, reabrir, habilitar resultados,
 * acta de escrutinio y pantalla de resultados. Las usan la página "Mesa de votación" y el dashboard.
 *
 * Requiere una propiedad pública `?Eleccion $eleccion`.
 */
trait OperaLaMesa
{
    public function puedeOperar(): bool
    {
        return auth()->user()?->esDirectivo() ?? false;
    }

    public function abrirAction(): Action
    {
        return $this->accion('abrir', 'Iniciar votación', 'success', 'heroicon-o-play', 'abrir', ['borrador'],
            '¿Iniciar la votación? Desde ese momento las PCs registradas pueden votar.');
    }

    public function cerrarAction(): Action
    {
        return $this->accion('cerrar', 'Detener votación', 'danger', 'heroicon-o-stop', 'cerrar', ['abierta'],
            '¿Detener la votación? No se podrá votar más.');
    }

    public function reabrirAction(): Action
    {
        return $this->accionConContrasena('reabrir', 'Reabrir votación', 'warning', 'heroicon-o-arrow-path', 'reabrir', ['cerrada'],
            'Deshace el cierre: las PCs registradas vuelven a poder votar. Confirme con su contraseña.');
    }

    public function publicarAction(): Action
    {
        return $this->accionConContrasena('publicar', 'Habilitar resultados', 'primary', 'heroicon-o-trophy', 'publicar', ['cerrada'],
            'Confirme con su contraseña. Una vez habilitados, la pantalla /resultados queda disponible.');
    }

    public function actaAction(): Action
    {
        return Action::make('acta')->label('Acta de escrutinio')->icon('heroicon-o-document-text')->color('gray')
            ->url(fn (): string => route('acta'), shouldOpenInNewTab: true)
            ->visible(fn (): bool => $this->puedeOperar() && $this->estadoEs('cerrada', 'publicada'));
    }

    public function verAction(): Action
    {
        return Action::make('ver')->label('Pantalla de resultados')->icon('heroicon-o-presentation-chart-bar')->color('info')
            ->url(fn (): string => route('resultados'), shouldOpenInNewTab: true)
            ->visible(fn (): bool => $this->estadoEs('publicada'));
    }

    /** @param  list<string>  $estados  estados en los que la acción está disponible */
    private function accion(string $nombre, string $label, string $color, string $icono, string $metodo, array $estados, string $pregunta): Action
    {
        return Action::make($nombre)->label($label)->color($color)->icon($icono)
            ->visible(fn (): bool => $this->puedeOperar() && $this->estadoEs(...$estados))
            ->authorize(fn (): bool => $this->puedeOperar())
            ->requiresConfirmation()->modalDescription($pregunta)
            ->action(fn () => $this->ejecutar($metodo));
    }

    /** @param  list<string>  $estados  estados en los que la acción está disponible */
    private function accionConContrasena(string $nombre, string $label, string $color, string $icono, string $metodo, array $estados, string $descripcion): Action
    {
        return Action::make($nombre)->label($label)->color($color)->icon($icono)
            ->visible(fn (): bool => $this->puedeOperar() && $this->estadoEs(...$estados))
            ->authorize(fn (): bool => $this->puedeOperar())
            ->modalHeading($label)
            ->modalDescription($descripcion)
            ->schema([TextInput::make('password')->label('Su contraseña')->password()->required()])
            ->action(function (array $data) use ($metodo): void {
                if (! Hash::check($data['password'], auth()->user()->password)) {
                    Notification::make()->title('Contraseña incorrecta')->danger()->send();

                    return;
                }
                $this->ejecutar($metodo);
            });
    }

    protected function estadoEs(string ...$estados): bool
    {
        $eleccion = $this->eleccion?->fresh();

        return $eleccion instanceof Eleccion && in_array($eleccion->estado->value, $estados, true);
    }

    private function ejecutar(string $metodo): void
    {
        try {
            $this->eleccion->fresh()->{$metodo}();
            Notification::make()->title('Listo')->success()->send();
        } catch (RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
        $this->eleccion = $this->eleccion->fresh();
    }
}
