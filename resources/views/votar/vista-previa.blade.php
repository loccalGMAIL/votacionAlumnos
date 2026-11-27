<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista previa de la boleta</title>
    <link rel="icon" href="{{ asset('escudo_colegio.ico') }}">
    @vite(['resources/css/app.css'])
</head>
{{-- Misma boleta que el cuarto oscuro, pero sin votante ni urna: elegir una lista solo cambia la pantalla. --}}
<body class="kiosco min-h-screen antialiased" x-data="{ seleccion: null }">
    <div role="status" class="bg-colegio-gold px-5 py-2 text-center text-sm font-semibold text-colegio-navy">
        Vista previa: así ven la boleta los votantes. No se registra ningún voto.
    </div>

    <header class="school-header px-5 py-4 md:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('escudo_colegio.gif') }}" alt="Escudo del colegio" class="h-14 w-11 object-contain mix-blend-multiply">
                <div>
                    <p class="school-eyebrow">Centro de estudiantes</p>
                    <h1 class="mt-1 text-sm font-semibold md:text-base">{{ $eleccion->nombre }}</h1>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold tracking-tight text-colegio-navy">{{ $eleccion->anio }}</p>
                <p class="text-xs text-slate-500">Votación estudiantil</p>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-5 pt-7 pb-8">
        <div class="mb-7 text-center">
            <p class="school-eyebrow">La decisión es tuya</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-colegio-navy">Elegí tu lista</h2>
            <p class="mt-2 text-sm text-slate-500">Conocé a los candidatos, seleccioná una opción y confirmá tu voto.</p>
        </div>

        @if ($listas->isEmpty())
            <p class="school-card mx-auto max-w-lg p-8 text-center text-sm text-slate-500">Todavía no hay listas cargadas en esta elección.</p>
        @else
            <div class="grid items-start gap-5 {{ $listas->count() === 1 ? 'mx-auto max-w-lg' : ($listas->count() === 2 ? 'md:grid-cols-2' : 'md:grid-cols-2 xl:grid-cols-3') }}">
                @foreach ($listas as $lista)
                    <x-boleta.lista :lista="$lista" :cargos="$cargos" previa
                                    x-on:click="seleccion = {{ $lista->id }}"
                                    x-bind:aria-pressed="seleccion === {{ $lista->id }} ? 'true' : 'false'">
                        <x-slot:marca>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border"
                                  x-bind:class="seleccion === {{ $lista->id }} ? 'border-colegio-navy bg-colegio-navy text-white' : 'border-slate-300'">
                                <x-heroicon-m-check class="h-4 w-4" x-show="seleccion === {{ $lista->id }}" style="display: none;" />
                            </span>
                        </x-slot:marca>
                        <x-slot:pie>
                            <div class="border-t border-slate-100 px-5 py-3 text-center text-xs font-semibold"
                                 x-bind:class="seleccion === {{ $lista->id }} ? 'bg-colegio-navy/5 text-colegio-navy' : 'bg-slate-50 text-slate-500'"
                                 x-text="seleccion === {{ $lista->id }} ? 'Lista seleccionada' : 'Seleccionar esta lista'">Seleccionar esta lista</div>
                        </x-slot:pie>
                    </x-boleta.lista>
                @endforeach
            </div>
        @endif
    </main>

    <footer class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4">
            <p class="flex items-center gap-2 text-xs text-slate-500"><x-heroicon-o-lock-closed class="h-4 w-4 text-colegio-gold" /> Tu elección es personal y secreta.</p>
            <div class="flex flex-wrap items-center gap-3">
                @if ($eleccion->permite_voto_en_blanco)
                    <button type="button" x-on:click="seleccion = 0" x-bind:aria-pressed="seleccion === 0 ? 'true' : 'false'"
                            x-bind:class="seleccion === 0 && 'ring-2 ring-colegio-navy ring-offset-2'" class="school-button school-button-secondary">Voto en blanco</button>
                @endif
                <button type="button" disabled title="En la vista previa no se vota" class="school-button school-button-primary">Confirmar mi voto <x-heroicon-o-arrow-right class="h-4 w-4" /></button>
            </div>
        </div>
    </footer>

    {{-- Trae Alpine (incluido en Livewire) para que la selección funcione en esta página sin componentes. --}}
    @livewireScripts
</body>
</html>
