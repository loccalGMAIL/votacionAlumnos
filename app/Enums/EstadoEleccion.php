<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoEleccion: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Abierta = 'abierta';
    case Cerrada = 'cerrada';
    case Publicada = 'publicada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'En preparación',
            self::Abierta => 'Votación abierta',
            self::Cerrada => 'Votación cerrada',
            self::Publicada => 'Resultados publicados',
        };
    }

    public function getLabel(): string
    {
        return $this->etiqueta();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Abierta => 'success',
            self::Cerrada => 'warning',
            self::Publicada => 'info',
        };
    }
}
