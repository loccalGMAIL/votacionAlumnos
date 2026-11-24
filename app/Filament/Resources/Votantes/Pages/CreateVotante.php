<?php

namespace App\Filament\Resources\Votantes\Pages;

use App\Filament\Resources\Votantes\VotanteResource;
use App\Models\Eleccion;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVotante extends CreateRecord
{
    protected static string $resource = VotanteResource::class;

    protected function beforeCreate(): void
    {
        $eleccion = Eleccion::find($this->data['eleccion_id'] ?? null);

        if ($eleccion && ! $eleccion->admiteAltasDePadron()) {
            Notification::make()->title('La votación de esa elección ya está cerrada: no se pueden agregar votantes.')->danger()->send();
            $this->halt();
        }
    }
}
