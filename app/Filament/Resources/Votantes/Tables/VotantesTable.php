<?php

namespace App\Filament\Resources\Votantes\Tables;

use App\Models\Eleccion;
use App\Models\Votante;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class VotantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('apellido')
            ->columns([
                TextColumn::make('dni')->label('DNI')->searchable(),
                TextColumn::make('apellido')->label('Apellido')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable(),
                TextColumn::make('curso')->label('Curso')->searchable()->sortable(),
                IconColumn::make('ya_voto')->label('Votó')->boolean(),
                TextColumn::make('voto_en')->label('Hora')->dateTime('H:i')->placeholder('—'),
                TextColumn::make('puesto.nombre')->label('Puesto')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('eleccion_id')->label('Elección')
                    ->options(fn () => Eleccion::orderByDesc('anio')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->nombre} {$e->anio}"]))
                    ->default(fn () => Eleccion::actual()?->id),
                TernaryFilter::make('ya_voto')->label('¿Ya votó?'),
                SelectFilter::make('curso')->options(fn () => Votante::whereNotNull('curso')->distinct()->orderBy('curso')->pluck('curso', 'curso')),
            ])
            ->recordActions([
                EditAction::make(),
                // Un votante que ya votó no se puede borrar: se perdería el control de participación.
                DeleteAction::make()->hidden(fn (Votante $r) => $r->ya_voto),
            ])
            ->toolbarActions([BulkActionGroup::make([
                // El borrado masivo saltea a quienes ya votaron en lugar de fallar.
                BulkAction::make('borrar')
                    ->label('Borrar seleccionados')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Quienes ya votaron no se borran.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $borrables = $records->reject(fn (Votante $v) => $v->ya_voto);
                        $borrables->each->delete();
                        $omitidos = $records->count() - $borrables->count();

                        Notification::make()
                            ->title("{$borrables->count()} borrados".($omitidos ? ", {$omitidos} omitidos porque ya votaron" : ''))
                            ->{$omitidos ? 'warning' : 'success'}()
                            ->send();
                    }),
            ])]);
    }
}
