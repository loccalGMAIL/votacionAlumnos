<x-filament-widgets::widget>
    <style>
        .cv-pasos { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); }
        .cv-paso { display: flex; flex-direction: column; gap: .75rem; padding: 1.25rem; border-radius: .75rem;
            border: 1px solid rgb(0 0 0 / .08); background: rgb(0 0 0 / .02); min-height: 11rem; }
        .dark .cv-paso { border-color: rgb(255 255 255 / .1); background: rgb(255 255 255 / .03); }
        .cv-paso.cv-activo { border-color: var(--primary-500); border-width: 2px; }
        .cv-numero { font-size: .75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; opacity: .6; }
        .cv-titulo { font-size: 1.125rem; font-weight: 600; }
        .cv-ayuda { font-size: .875rem; opacity: .7; }
        .cv-botones { margin-top: auto; display: flex; flex-direction: column; gap: .5rem; }
        .cv-botones > * , .cv-botones .fi-btn { width: 100%; justify-content: center; }
        .cv-botones .fi-btn { padding-block: .875rem; font-size: 1rem; }
        .cv-encabezado { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
        .cv-eleccion { font-size: 1.25rem; font-weight: 600; }
    </style>

    <x-filament::section wire:poll.5s>
        @if (! $eleccion)
            <p>No hay ninguna elección cargada.</p>
        @else
            @php($estado = $eleccion->fresh()->estado)
            <div class="cv-encabezado">
                <span class="cv-eleccion">{{ $eleccion->nombre }} {{ $eleccion->anio }}</span>
                <x-filament::badge size="xl" :color="$estado->getColor()">{{ $estado->etiqueta() }}</x-filament::badge>
            </div>

            <div class="cv-pasos">
                <div @class(['cv-paso', 'cv-activo' => $estado->value === 'borrador'])>
                    <span class="cv-numero">Paso 1</span>
                    <span class="cv-titulo">Iniciar votación</span>
                    <span class="cv-ayuda">Habilita las PCs registradas para votar.</span>
                    <div class="cv-botones">
                        @if ($this->abrirAction->isVisible())
                            {{ $this->abrirAction->size('xl') }}
                        @else
                            <span class="cv-ayuda">Votación ya iniciada.</span>
                        @endif
                    </div>
                </div>

                <div @class(['cv-paso', 'cv-activo' => $estado->value === 'abierta'])>
                    <span class="cv-numero">Paso 2</span>
                    <span class="cv-titulo">Detener votación</span>
                    <span class="cv-ayuda">Cierra la votación: no se puede votar más.</span>
                    <div class="cv-botones">
                        @if ($this->cerrarAction->isVisible())
                            {{ $this->cerrarAction->size('xl') }}
                        @elseif ($this->reabrirAction->isVisible())
                            <span class="cv-ayuda">Votación detenida.</span>
                            {{ $this->reabrirAction->size('lg')->outlined() }}
                        @else
                            <span class="cv-ayuda">{{ $estado->value === 'borrador' ? 'Disponible al iniciar la votación.' : 'Votación detenida.' }}</span>
                        @endif
                    </div>
                </div>

                <div @class(['cv-paso', 'cv-activo' => $estado->value === 'cerrada'])>
                    <span class="cv-numero">Paso 3</span>
                    <span class="cv-titulo">Acta de escrutinio</span>
                    <span class="cv-ayuda">Conteo oficial para imprimir y firmar.</span>
                    <div class="cv-botones">
                        @if ($this->actaAction->isVisible())
                            {{ $this->actaAction->size('xl') }}
                        @else
                            <span class="cv-ayuda">Disponible al detener la votación.</span>
                        @endif
                    </div>
                </div>

                <div @class(['cv-paso', 'cv-activo' => $estado->value === 'publicada'])>
                    <span class="cv-numero">Paso 4</span>
                    <span class="cv-titulo">Pantalla de resultados</span>
                    <span class="cv-ayuda">Muestra los resultados en /resultados.</span>
                    <div class="cv-botones">
                        @if ($this->publicarAction->isVisible())
                            {{ $this->publicarAction->size('xl') }}
                        @elseif ($this->verAction->isVisible())
                            {{ $this->verAction->size('xl') }}
                        @else
                            <span class="cv-ayuda">Disponible al detener la votación.</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
