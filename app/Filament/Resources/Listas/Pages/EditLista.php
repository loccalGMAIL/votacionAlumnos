<?php

namespace App\Filament\Resources\Listas\Pages;

use App\Filament\Resources\Listas\ListaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLista extends EditRecord
{
    protected static string $resource = ListaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vistaPrevia')->label('Vista previa de la boleta')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn (): string => route('boleta.vista-previa', $this->getRecord()->eleccion), shouldOpenInNewTab: true),
            DeleteAction::make()->visible(fn () => $this->getRecord()->eleccion->enPreparacion()),
        ];
    }

    /** Fuera de "En preparación" solo se puede volver: no hay nada que guardar. */
    protected function getFormActions(): array
    {
        return $this->getRecord()->eleccion->enPreparacion()
            ? parent::getFormActions()
            : [$this->getCancelFormAction()];
    }
}
