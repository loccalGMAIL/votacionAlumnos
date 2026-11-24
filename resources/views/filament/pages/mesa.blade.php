<x-filament-panels::page wire:poll.5s>
    @if (! $eleccion)
        <x-filament::section>No hay ninguna elección cargada. Cree una desde "Elecciones".</x-filament::section>
    @else
        @php($p = $this->participacion())
        @unless ($this->puedeOperar())
            <x-filament::section>Solo el directivo puede operar la mesa (abrir, cerrar, reabrir y habilitar resultados). Aquí solo se muestra la participación.</x-filament::section>
        @endunless
        <x-filament::section>
            <x-slot name="heading">{{ $eleccion->nombre }} {{ $eleccion->anio }}</x-slot>
            <div class="flex flex-wrap items-center gap-6">
                <x-filament::badge size="xl" :color="$eleccion->fresh()->estado->getColor()">
                    {{ $eleccion->fresh()->estado->etiqueta() }}
                </x-filament::badge>
                <div>
                    <div class="text-4xl font-bold">{{ $p['votaron'] }} <span class="text-lg font-normal text-gray-500">de {{ $p['padron'] }} votaron</span></div>
                    <div class="mt-2 h-3 w-72 max-w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full bg-primary-600" style="width: {{ $p['porcentaje'] }}%"></div>
                    </div>
                    <div class="mt-1 text-sm text-gray-500">{{ $p['porcentaje'] }}% de participación (no se muestra a quién votaron)</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Participación por curso">
            <div class="space-y-3">
                @forelse ($this->participacionPorCurso() as $fila)
                    <div wire:key="curso-{{ $loop->index }}" class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <div class="w-32 shrink-0 text-sm font-medium">{{ $fila['curso'] }}</div>
                        <div class="h-2.5 min-w-32 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-primary-600" style="width: {{ $fila['porcentaje'] }}%"></div>
                        </div>
                        <div class="w-40 shrink-0 text-right text-sm text-gray-500">{{ $fila['votaron'] }} de {{ $fila['padron'] }} ({{ $fila['porcentaje'] }}%)</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">El padrón está vacío.</p>
                @endforelse
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
