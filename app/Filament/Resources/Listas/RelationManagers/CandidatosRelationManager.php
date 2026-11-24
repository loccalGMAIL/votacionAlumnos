<?php

namespace App\Filament\Resources\Listas\RelationManagers;

use App\Models\Cargo;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class CandidatosRelationManager extends RelationManager
{
    protected static string $relationship = 'candidatos';

    protected static ?string $title = 'Candidatos';

    /** Fuera de "En preparación" la boleta se puede ver pero no tocar. */
    public function isReadOnly(): bool
    {
        return ! $this->getOwnerRecord()->eleccion->enPreparacion();
    }

    public function form(Schema $schema): Schema
    {
        $lista = $this->getOwnerRecord();

        return $schema->components([
            Select::make('cargo_id')->label('Cargo')
                ->options($lista->eleccion->cargos()->pluck('nombre', 'id'))
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('lista_id', $lista->id))
                ->validationMessages(['unique' => 'Esta lista ya tiene un candidato para ese cargo.']),
            TextInput::make('apellido')->label('Apellido')->required()->maxLength(80),
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(80),
            TextInput::make('curso')->label('Curso')->maxLength(30),
            FileUpload::make('foto')->label('Foto')->image()->disk('public')->directory('candidatos')->imageEditor()->avatar(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Orden de la boleta: por el "orden" del cargo. El parámetro DEBE llamarse $query: Filament inyecta
            // la consulta por nombre, y con otro nombre recibe un Builder vacío (sin modelo) y la tabla falla.
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy(Cargo::select('orden')->whereColumn('cargos.id', 'candidatos.cargo_id')))
            ->columns([
                ImageColumn::make('foto')->label('Foto')->disk('public')->circular(),
                TextColumn::make('cargo.nombre')->label('Cargo'),
                TextColumn::make('apellido')->label('Apellido'),
                TextColumn::make('nombre')->label('Nombre'),
                TextColumn::make('curso')->label('Curso'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
