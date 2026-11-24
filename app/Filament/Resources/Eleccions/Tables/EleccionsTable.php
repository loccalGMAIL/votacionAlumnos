<?php

namespace App\Filament\Resources\Eleccions\Tables;

use App\Enums\EstadoEleccion;
use App\Models\Eleccion;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class EleccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('anio', 'desc')
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable(),
                TextColumn::make('anio')->label('Año')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge(),
                IconColumn::make('permite_voto_en_blanco')->label('Voto en blanco')->boolean(),
                TextColumn::make('votantes_count')->label('Padrón')->counts('votantes'),
                TextColumn::make('listas_count')->label('Listas')->counts('listas'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicar')
                    ->label('Duplicar para nuevo año')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->modalDescription('Copia el nombre, los cargos y la configuración. Las listas, candidatos y el padrón quedan vacíos.')
                    ->action(function (Eleccion $record) {
                        $nueva = DB::transaction(function () use ($record) {
                            $nueva = $record->replicate(['estado', 'abierta_en', 'cerrada_en', 'publicada_en']);
                            $nueva->anio = $record->anio + 1;
                            $nueva->estado = EstadoEleccion::Borrador;
                            $nueva->save();
                            foreach ($record->cargos as $cargo) {
                                $nueva->cargos()->create(['nombre' => $cargo->nombre, 'orden' => $cargo->orden]);
                            }

                            return $nueva;
                        });
                        Notification::make()->title("Elección {$nueva->anio} creada")->success()->send();
                    }),
            ]);
    }
}
