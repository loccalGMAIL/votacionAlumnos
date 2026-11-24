<?php

namespace App\Filament\Resources\Eleccions;

use App\Filament\Resources\Eleccions\Pages\CreateEleccion;
use App\Filament\Resources\Eleccions\Pages\EditEleccion;
use App\Filament\Resources\Eleccions\Pages\ListEleccions;
use App\Filament\Resources\Eleccions\RelationManagers\CargosRelationManager;
use App\Filament\Resources\Eleccions\RelationManagers\ListasRelationManager;
use App\Filament\Resources\Eleccions\Schemas\EleccionForm;
use App\Filament\Resources\Eleccions\Tables\EleccionsTable;
use App\Models\Eleccion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EleccionResource extends Resource
{
    protected static ?string $model = Eleccion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'elección';

    protected static ?string $pluralModelLabel = 'elecciones';

    protected static ?string $slug = 'elecciones';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return EleccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EleccionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [CargosRelationManager::class, ListasRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEleccions::route('/'),
            'create' => CreateEleccion::route('/create'),
            'edit' => EditEleccion::route('/{record}/edit'),
        ];
    }
}
