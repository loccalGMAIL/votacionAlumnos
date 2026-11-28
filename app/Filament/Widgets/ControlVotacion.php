<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\OperaLaMesa;
use App\Models\Eleccion;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

/** Dashboard del directivo: los pasos de la votación como botones grandes, en orden. */
class ControlVotacion extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use OperaLaMesa;

    protected string $view = 'filament.widgets.control-votacion';

    protected static ?int $sort = -10;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    public ?Eleccion $eleccion = null;

    public static function canView(): bool
    {
        return auth()->user()?->esDirectivo() ?? false;
    }

    public function mount(): void
    {
        $this->eleccion = Eleccion::actual();
    }
}
