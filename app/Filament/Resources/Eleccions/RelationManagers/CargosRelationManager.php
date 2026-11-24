<?php

namespace App\Filament\Resources\Eleccions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CargosRelationManager extends RelationManager
{
    protected static string $relationship = 'cargos';

    protected static ?string $title = 'Cargos';

    /** Fuera de "En preparación" la boleta se puede ver pero no tocar. */
    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->enPreparacion();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Cargo')->required()->maxLength(80),
            TextInput::make('orden')->label('Orden en la boleta')->numeric()->default(fn () => ($this->getOwnerRecord()->cargos()->max('orden') ?? 0) + 1)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->reorderable('orden')
            ->columns([
                TextColumn::make('orden')->label('#'),
                TextColumn::make('nombre')->label('Cargo'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
