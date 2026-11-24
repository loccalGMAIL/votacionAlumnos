<?php

namespace App\Filament\Resources\Listas;

use App\Filament\Resources\Listas\Pages\CreateLista;
use App\Filament\Resources\Listas\Pages\EditLista;
use App\Filament\Resources\Listas\Pages\ListListas;
use App\Filament\Resources\Listas\RelationManagers\CandidatosRelationManager;
use App\Filament\Resources\Listas\Schemas\ListaForm;
use App\Filament\Resources\Listas\Tables\ListasTable;
use App\Models\Lista;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ListaResource extends Resource
{
    protected static ?string $model = Lista::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $modelLabel = 'lista';

    protected static ?string $pluralModelLabel = 'listas y candidatos';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ListaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [CandidatosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListas::route('/'),
            'create' => CreateLista::route('/create'),
            'edit' => EditLista::route('/{record}/edit'),
        ];
    }
}
