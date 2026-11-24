<?php

namespace App\Filament\Resources\Eleccions\Pages;

use App\Filament\Resources\Eleccions\EleccionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEleccion extends EditRecord
{
    protected static string $resource = EleccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn () => $this->getRecord()->enPreparacion()),
        ];
    }
}
