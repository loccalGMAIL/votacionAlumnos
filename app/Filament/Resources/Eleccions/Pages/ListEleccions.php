<?php

namespace App\Filament\Resources\Eleccions\Pages;

use App\Filament\Resources\Eleccions\EleccionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEleccions extends ListRecords
{
    protected static string $resource = EleccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
