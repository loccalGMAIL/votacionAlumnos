<?php

namespace App\Filament\Resources\Puestos\Tables;

use App\Actions\GenerarAccesoDirecto;
use App\Models\Puesto;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Js;

class PuestosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Puesto')->searchable(),
                IconColumn::make('activo')->label('Habilitado')->boolean(),
                TextColumn::make('mac')->label('PC vinculada (MAC)')
                    ->placeholder('Sin vincular')
                    ->badge()
                    ->color(fn (Puesto $r) => $r->estaVinculado() ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state) => $state ? str_replace(',', ' · ', $state) : null)
                    ->wrap(),
                TextColumn::make('token')->label('Enlace para registrar la PC')
                    ->state(fn (Puesto $r) => route('puesto.registrar', $r->token))
                    ->description('Usá «Copiar enlace» y abrilo una vez en la PC que quieras habilitar.')
                    ->wrap()
                    ->extraAttributes(['class' => 'select-all break-all']),
            ])
            ->recordActions([
                Action::make('copiarEnlace')
                    ->label('Copiar enlace')
                    ->icon('heroicon-o-clipboard-document')
                    ->button()
                    ->color('gray')
                    ->alpineClickHandler(fn (Puesto $record): string => e('(() => {
                        const enlace = '.Js::from(route('puesto.registrar', $record->token)).';
                        const confirmar = () => new FilamentNotification().title("Enlace completo copiado").success().send();
                        const avisar = () => new FilamentNotification()
                            .title("El navegador bloqueó el copiado automático")
                            .body("Seleccioná el enlace completo de la tabla y presioná Ctrl+C (o Cmd+C).")
                            .warning().send();
                        const anterior = document.activeElement;
                        const campo = document.createElement("textarea");
                        campo.value = enlace;
                        campo.readOnly = true;
                        campo.style.cssText = "position:fixed;top:0;left:-9999px;opacity:0";
                        document.body.appendChild(campo);
                        let copiado = false;
                        try {
                            campo.focus();
                            campo.select();
                            campo.setSelectionRange(0, campo.value.length);
                            copiado = document.execCommand("copy");
                        } catch (error) {
                            copiado = false;
                        } finally {
                            campo.remove();
                            anterior?.focus({ preventScroll: true });
                        }
                        if (copiado) {
                            confirmar();
                        } else if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(enlace).then(confirmar).catch(avisar);
                        } else {
                            avisar();
                        }
                    })()')
                    ),
                Action::make('accesoDirecto')
                    ->label('Acceso directo')
                    ->icon('heroicon-o-computer-desktop')
                    ->button()
                    ->color('gray')
                    ->tooltip('Descarga un instalador que crea en el Escritorio de la PC un acceso directo con el escudo y el enlace de este puesto.')
                    ->action(function (Puesto $record) {
                        $generar = new GenerarAccesoDirecto;

                        return response()->streamDownload(
                            fn () => print ($generar($record)),
                            "Instalar acceso directo - {$generar->nombre($record)}.bat",
                            ['Content-Type' => 'application/octet-stream'],
                        );
                    }),
                Action::make('desvincular')
                    ->label('Desvincular')
                    ->icon('heroicon-o-link-slash')
                    ->color('warning')
                    ->visible(fn (Puesto $record) => $record->estaVinculado())
                    ->requiresConfirmation()
                    ->modalDescription('El puesto queda sin PC asociada: el próximo acceso directo que se abra lo vincula a esa PC. Usalo al reemplazar una placa o una computadora.')
                    ->action(fn (Puesto $record) => $record->update(['mac' => null])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
