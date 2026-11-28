<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OperaLaMesa;
use App\Models\Eleccion;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Panel del directivo: abrir/cerrar/reabrir la votación y habilitar los resultados. El admin solo mira. */
class Mesa extends Page
{
    use OperaLaMesa;

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
            $this->abrirAction(),
            $this->cerrarAction(),
            $this->reabrirAction(),
            $this->publicarAction(),
            $this->actaAction(),
            $this->verAction(),
        ];
    }
}
