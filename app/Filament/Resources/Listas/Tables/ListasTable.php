<?php

namespace App\Filament\Resources\Listas\Tables;

use App\Models\Lista;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('eleccion_id', 'desc')
            ->columns([
                TextColumn::make('eleccion.anio')->label('Año')->sortable(),
                TextColumn::make('numero')->label('N°')->sortable(),
                ImageColumn::make('logo')->label('Logo')->disk('public'),
                TextColumn::make('nombre')->label('Lista')->searchable(),
                ColorColumn::make('color')->label('Color'),
                TextColumn::make('candidatos_count')->label('Candidatos')->counts('candidatos'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Lista $lista) => ! $lista->eleccion->enPreparacion()),
            ]);
    }
}
