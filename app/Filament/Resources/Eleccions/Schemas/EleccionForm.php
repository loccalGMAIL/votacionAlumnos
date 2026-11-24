<?php

namespace App\Filament\Resources\Eleccions\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EleccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la elección')->columns(2)->schema([
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
                TextInput::make('anio')->label('Año')->required()->numeric()->minValue(2000)->maxValue(2100)
                    ->default((int) date('Y')),
                Toggle::make('permite_voto_en_blanco')->label('Permitir voto en blanco')->default(true)
                    ->disabled(fn ($record) => $record && ! $record->enPreparacion())
                    ->helperText(fn ($record) => $record && ! $record->enPreparacion() ? 'No se puede cambiar una vez abierta la votación.' : null),
                Placeholder::make('estado_actual')->label('Estado')
                    ->content(fn ($record) => $record?->estado->etiqueta() ?? 'En preparación (se abre desde "Mesa")'),
            ]),
            Section::make('Seguridad (opcional)')->schema([
                Textarea::make('ips_permitidas')->label('IPs permitidas para votar')
                    ->helperText('Una por línea: IP suelta (200.1.1.1) o rango (192.168.1.0/24). Vacío = sin restricción. Solo restringe /votar.')
                    ->rows(3),
            ])->collapsed(),
        ]);
    }
}
