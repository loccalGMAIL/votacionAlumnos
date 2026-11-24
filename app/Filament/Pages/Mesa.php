<?php

namespace App\Filament\Pages;

use App\Models\Eleccion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Panel del directivo: abrir/cerrar/reabrir la votación y habilitar los resultados. El admin solo mira. */
class Mesa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Mesa de votación';

    protected static ?string $title = 'Mesa de votación';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.mesa';

    public ?Eleccion $eleccion = null;

    public function mount(): void
    {
        $this->eleccion = Eleccion::actual();
    }

    public function puedeOperar(): bool
    {
        return auth()->user()?->esDirectivo() ?? false;
    }

    /** @return array{padron: int, votaron: int, porcentaje: int|float} */
    public function participacion(): array
    {
        $e = $this->eleccion?->fresh();
        $padron = $e?->votantes()->count() ?? 0;
        $votaron = $e?->votantes()->where('ya_voto', true)->count() ?? 0;

        return ['padron' => $padron, 'votaron' => $votaron, 'porcentaje' => $padron ? round($votaron * 100 / $padron) : 0];
    }

    /** @return array<int, array{curso: string, padron: int, votaron: int, porcentaje: int|float}> */
    public function participacionPorCurso(): array
    {
        if (! $this->eleccion) {
            return [];
        }

        return $this->eleccion->votantes()->toBase()
            ->selectRaw('curso, count(*) as padron, sum(case when ya_voto then 1 else 0 end) as votaron')
            ->groupBy('curso')
            ->orderBy('curso')
            ->get()
            ->map(fn ($fila) => [
                'curso' => $fila->curso ?: 'Sin curso',
                'padron' => (int) $fila->padron,
                'votaron' => (int) $fila->votaron,
                'porcentaje' => $fila->padron ? round($fila->votaron * 100 / $fila->padron) : 0,
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->accion('abrir', 'Abrir votación', 'success', 'abrir', ['borrador'], '¿Abrir la votación? Desde ese momento las PCs registradas pueden votar.'),
            $this->accion('cerrar', 'Cerrar votación', 'warning', 'cerrar', ['abierta'], '¿Cerrar la votación? No se podrá votar más.'),
            $this->accionConContrasena('reabrir', 'Reabrir votación', 'warning', 'heroicon-o-arrow-path', 'reabrir', ['cerrada'],
                'Deshace el cierre: las PCs registradas vuelven a poder votar. Confirme con su contraseña.'),
            $this->accionConContrasena('publicar', 'Habilitar resultados', 'primary', 'heroicon-o-trophy', 'publicar', ['cerrada'],
                'Confirme con su contraseña. Una vez habilitados, la pantalla /resultados queda disponible.'),
            Action::make('acta')->label('Acta de escrutinio')->icon('heroicon-o-document-text')
                ->url(fn () => route('acta'), shouldOpenInNewTab: true)
                ->visible(fn () => $this->puedeOperar() && $this->estadoEs('cerrada', 'publicada')),
            Action::make('ver')->label('Abrir pantalla de resultados')->icon('heroicon-o-presentation-chart-bar')
                ->url(fn () => route('resultados'), shouldOpenInNewTab: true)
                ->visible(fn () => $this->estadoEs('publicada')),
        ];
    }

    /** @param  list<string>  $estados  estados en los que la acción está disponible */
    private function accion(string $nombre, string $label, string $color, string $metodo, array $estados, string $pregunta): Action
    {
        return Action::make($nombre)->label($label)->color($color)
            ->visible(fn () => $this->puedeOperar() && $this->estadoEs(...$estados))
            ->authorize(fn () => $this->puedeOperar())
            ->requiresConfirmation()->modalDescription($pregunta)
            ->action(fn () => $this->ejecutar($metodo));
    }

    /** @param  list<string>  $estados  estados en los que la acción está disponible */
    private function accionConContrasena(string $nombre, string $label, string $color, string $icono, string $metodo, array $estados, string $descripcion): Action
    {
        return Action::make($nombre)->label($label)->color($color)->icon($icono)
            ->visible(fn () => $this->puedeOperar() && $this->estadoEs(...$estados))
            ->authorize(fn () => $this->puedeOperar())
            ->modalHeading($label)
            ->modalDescription($descripcion)
            ->schema([TextInput::make('password')->label('Su contraseña')->password()->required()])
            ->action(function (array $data) use ($metodo) {
                if (! Hash::check($data['password'], auth()->user()->password)) {
                    Notification::make()->title('Contraseña incorrecta')->danger()->send();

                    return;
                }
                $this->ejecutar($metodo);
            });
    }

    private function estadoEs(string ...$estados): bool
    {
        $eleccion = $this->eleccion?->fresh();

        return $eleccion !== null && in_array($eleccion->estado->value, $estados, true);
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
