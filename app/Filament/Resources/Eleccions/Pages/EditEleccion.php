<?php

namespace App\Filament\Resources\Eleccions\Pages;

use App\Filament\Resources\Eleccions\EleccionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEleccion extends EditRecord
{
    protected static string $resource = EleccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vistaPrevia')->label('Vista previa de la boleta')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn (): string => route('boleta.vista-previa', $this->getRecord()), shouldOpenInNewTab: true),
            DeleteAction::make()->visible(fn () => $this->getRecord()->enPreparacion()),
        ];
    }
}
