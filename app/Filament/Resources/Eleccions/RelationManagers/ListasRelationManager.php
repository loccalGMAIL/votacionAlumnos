<?php

namespace App\Filament\Resources\Eleccions\RelationManagers;

use App\Filament\Resources\Listas\Schemas\ListaForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListasRelationManager extends RelationManager
{
    protected static string $relationship = 'listas';

    protected static ?string $title = 'Listas';

    /** Fuera de "En preparación" la boleta se puede ver pero no tocar. */
    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->enPreparacion();
    }

    public function form(Schema $schema): Schema
    {
        return ListaForm::configure($schema, conEleccion: false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('numero')
            ->columns([
                TextColumn::make('numero')->label('N°'),
                ImageColumn::make('logo')->label('Logo')->disk('public'),
                TextColumn::make('nombre')->label('Lista'),
                ColorColumn::make('color')->label('Color'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
