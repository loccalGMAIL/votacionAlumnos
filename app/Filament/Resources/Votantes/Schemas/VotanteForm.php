<?php

namespace App\Filament\Resources\Votantes\Schemas;

use App\Models\Eleccion;
use App\Models\Votante;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class VotanteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('eleccion_id')->label('Elección')
                ->relationship('eleccion', 'nombre')
                ->getOptionLabelFromRecordUsing(fn (Eleccion $e) => "{$e->nombre} {$e->anio}")
                ->default(fn () => Eleccion::actual()?->id)
                ->required()->live(),
            TextInput::make('dni')->label('DNI')->required()->maxLength(12)
                ->disabled(fn (?Votante $record) => (bool) $record?->ya_voto)
                ->helperText(fn (?Votante $record) => $record?->ya_voto ? 'Ya votó: no se puede cambiar el DNI.' : null)
                ->dehydrateStateUsing(fn ($state) => Votante::normalizarDni($state))
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, $get) => $rule->where('eleccion_id', $get('eleccion_id')))
                ->validationMessages(['unique' => 'Ese DNI ya está en el padrón de esta elección.']),
            TextInput::make('apellido')->label('Apellido')->required()->maxLength(80),
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(80),
            TextInput::make('curso')->label('Curso / división')->maxLength(30),
        ]);
    }
}
