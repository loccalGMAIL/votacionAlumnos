<?php

namespace App\Filament\Resources\Votantes;

use App\Filament\Resources\Votantes\Pages\CreateVotante;
use App\Filament\Resources\Votantes\Pages\EditVotante;
use App\Filament\Resources\Votantes\Pages\ListVotantes;
use App\Filament\Resources\Votantes\Schemas\VotanteForm;
use App\Filament\Resources\Votantes\Tables\VotantesTable;
use App\Models\Votante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VotanteResource extends Resource
{
    protected static ?string $model = Votante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'votante';

    protected static ?string $pluralModelLabel = 'padrón de votantes';

    protected static ?string $navigationLabel = 'Padrón';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return VotanteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VotantesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVotantes::route('/'),
            'create' => CreateVotante::route('/create'),
            'edit' => EditVotante::route('/{record}/edit'),
        ];
    }
}
