<?php

namespace App\Filament\Resources\Puestos;

use App\Filament\Resources\Puestos\Pages\CreatePuesto;
use App\Filament\Resources\Puestos\Pages\EditPuesto;
use App\Filament\Resources\Puestos\Pages\ListPuestos;
use App\Filament\Resources\Puestos\Schemas\PuestoForm;
use App\Filament\Resources\Puestos\Tables\PuestosTable;
use App\Models\Puesto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PuestoResource extends Resource
{
    protected static ?string $model = Puesto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $modelLabel = 'puesto de votación';

    protected static ?string $pluralModelLabel = 'puestos de votación (PCs)';

    protected static ?string $navigationLabel = 'Puestos (PCs)';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return PuestoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PuestosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPuestos::route('/'),
            'create' => CreatePuesto::route('/create'),
            'edit' => EditPuesto::route('/{record}/edit'),
        ];
    }
}
