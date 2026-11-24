<?php

namespace App\Filament\Resources\Listas\Pages;

use App\Filament\Resources\Listas\ListaResource;
use App\Models\Eleccion;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLista extends CreateRecord
{
    protected static string $resource = ListaResource::class;

    protected function beforeCreate(): void
    {
        $eleccion = Eleccion::find($this->data['eleccion_id'] ?? null);

        if ($eleccion && ! $eleccion->enPreparacion()) {
            Notification::make()->title('Esa elección ya fue abierta: no se pueden agregar listas.')->danger()->send();
            $this->halt();
        }
    }
}
