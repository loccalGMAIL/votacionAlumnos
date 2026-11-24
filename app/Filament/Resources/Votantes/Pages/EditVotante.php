<?php

namespace App\Filament\Resources\Votantes\Pages;

use App\Filament\Resources\Votantes\VotanteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVotante extends EditRecord
{
    protected static string $resource = VotanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Un votante que ya votó no se puede borrar: se perdería el control de participación.
            DeleteAction::make()->hidden(fn () => $this->getRecord()->ya_voto),
        ];
    }
}
