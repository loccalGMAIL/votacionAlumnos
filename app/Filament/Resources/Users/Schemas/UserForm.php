<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $esPropioOUltimoAdmin = fn (?User $record): bool => $record !== null
            && ($record->is(auth()->user()) || $record->esUltimoAdminActivo());

        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('email')->label('Correo electrónico')->email()->required()->maxLength(255)
                ->unique(ignoreRecord: true)
                ->validationMessages(['unique' => 'Ya hay un usuario con ese correo.']),
            Select::make('rol')->label('Rol')
                ->options(User::ROLES)
                ->default(User::ROL_DIRECTIVO)
                ->required()
                ->native(false)
                ->disabled($esPropioOUltimoAdmin)
                ->helperText('El administrador gestiona la elección; el directivo opera la mesa y el acta.'),
            Toggle::make('activo')->label('Acceso habilitado')
                ->default(true)
                ->disabled($esPropioOUltimoAdmin)
                ->helperText('Si lo desactivás, el usuario no puede entrar, pero se conserva su registro. No podés cambiar tu propio rol ni tu estado, ni los del último administrador activo.'),
            TextInput::make('password')->label('Contraseña')
                ->password()->revealable()
                ->required()->minLength(8)
                ->confirmed()
                ->visibleOn('create'),
            TextInput::make('password_confirmation')->label('Repetir contraseña')
                ->password()->revealable()
                ->required()
                ->dehydrated(false)
                ->visibleOn('create'),
        ]);
    }
}
