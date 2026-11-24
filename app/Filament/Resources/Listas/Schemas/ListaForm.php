<?php

namespace App\Filament\Resources\Listas\Schemas;

use App\Models\Eleccion;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ListaForm
{
    public static function configure(Schema $schema, bool $conEleccion = true): Schema
    {
        return $schema->components([
            ...($conEleccion ? [
                Select::make('eleccion_id')->label('Elección')
                    ->relationship('eleccion', 'nombre')
                    ->getOptionLabelFromRecordUsing(fn (Eleccion $e) => "{$e->nombre} {$e->anio}")
                    ->default(fn () => Eleccion::actual()?->id)
                    ->required(),
            ] : []),
            TextInput::make('numero')->label('Número de lista')->numeric()->required()->minValue(1),
            TextInput::make('nombre')->label('Nombre de la lista')->required()->maxLength(120),
            TextInput::make('sigla')->label('Sigla')->maxLength(12),
            ColorPicker::make('color')->label('Color de la lista')->default('#2563eb')->required(),
            FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('listas')->imageEditor(),
        ]);
    }
}
