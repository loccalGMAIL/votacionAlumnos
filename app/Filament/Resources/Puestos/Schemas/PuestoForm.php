<?php

namespace App\Filament\Resources\Puestos\Schemas;

use App\Models\Puesto;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PuestoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre (ej. PC 3)')->required()->maxLength(60),
            Toggle::make('activo')->label('Habilitado para votar')->default(true),
            Textarea::make('mac')->label('MAC de la PC (opcional)')->rows(2)
                ->helperText('Si la dejás vacía, el primer uso del acceso directo vincula el puesto a esa PC. También podés cargarla a mano (ver con ipconfig /all); varias placas, separadas por coma o por línea. Con la MAC cargada, el enlace solo funciona desde esa PC.')
                ->dehydrateStateUsing(fn (?string $state) => $state === null || trim($state) === '' ? null : implode(',', Puesto::normalizarMacs($state)))
                ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                    if (trim((string) $value) !== '' && Puesto::normalizarMacs($value) === []) {
                        $fail('No se reconoce ninguna MAC válida (formato AA-BB-CC-DD-EE-FF).');
                    }
                }]),
        ]);
    }
}
