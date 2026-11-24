<?php

namespace App\Filament\Resources\Votantes\Pages;

use App\Filament\Resources\Votantes\VotanteResource;
use App\Imports\PadronImport;
use App\Models\Eleccion;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ListVotantes extends ListRecords
{
    protected static string $resource = VotanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar')
                ->label('Importar padrón (CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalDescription('Columnas (primera fila): dni, apellido, nombre, curso. Desde Excel: "Guardar como" → CSV.')
                ->schema([
                    Select::make('eleccion_id')->label('Elección')
                        ->options(fn () => Eleccion::orderByDesc('anio')->get()->mapWithKeys(fn ($e) => [$e->id => "{$e->nombre} {$e->anio}"]))
                        ->default(fn () => Eleccion::actual()?->id)->required(),
                    FileUpload::make('archivo')->label('Archivo CSV')->required()
                        ->disk('local')->directory('importaciones')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', '.csv']),
                ])
                ->action(function (array $data) {
                    try {
                        $res = (new PadronImport)(Eleccion::findOrFail($data['eleccion_id']), Storage::disk('local')->path($data['archivo']));
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title('No se pudo importar')->body($e->getMessage())->danger()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['archivo']); // el padrón tiene DNI: no dejarlo guardado
                    }

                    $n = Notification::make()
                        ->title("Importación: {$res['creados']} nuevos, {$res['actualizados']} actualizados, {$res['omitidos']} omitidos (ya votaron)")
                        ->{$res['errores'] ? 'warning' : 'success'}();
                    if ($res['errores']) {
                        $n->body(implode("\n", array_slice($res['errores'], 0, 8)).(count($res['errores']) > 8 ? "\n…y ".(count($res['errores']) - 8).' más.' : ''));
                    }
                    $n->persistent()->send();
                }),
            CreateAction::make(),
        ];
    }
}
