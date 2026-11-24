<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Correo')->searchable()->sortable(),
                TextColumn::make('rol')->label('Rol')->badge()
                    ->color(fn (string $state) => $state === User::ROL_ADMIN ? 'danger' : 'info')
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? $state),
                IconColumn::make('activo')->label('Acceso')->boolean(),
                TextColumn::make('ultimo_acceso_at')->label('Último acceso')
                    ->since()->placeholder('Nunca')->sortable(),
            ])
            ->filters([
                SelectFilter::make('rol')->label('Rol')->options(User::ROLES),
                TernaryFilter::make('activo')->label('Acceso')
                    ->trueLabel('Habilitados')->falseLabel('Deshabilitados'),
            ])
            ->recordActions([
                Action::make('cambiarPassword')
                    ->label('Cambiar contraseña')
                    ->icon('heroicon-o-key')
                    ->color('gray')
                    ->modalHeading(fn (User $record) => "Cambiar la contraseña de {$record->name}")
                    ->schema([
                        TextInput::make('password')->label('Nueva contraseña')
                            ->password()->revealable()->required()->minLength(8)->confirmed(),
                        TextInput::make('password_confirmation')->label('Repetir contraseña')
                            ->password()->revealable()->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => $data['password']]);

                        Notification::make()->title('Contraseña actualizada')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record) => $record->is(auth()->user()) || $record->esUltimoAdminActivo()),
            ]);
    }
}
