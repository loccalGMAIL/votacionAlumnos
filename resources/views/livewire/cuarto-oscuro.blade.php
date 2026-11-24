<div class="min-h-screen flex flex-col"
     x-data="{
        limite: 90,
        aviso: 15,
        restante: 90,
        init() {
            setInterval(() => {
                if (this.$wire.paso === 'gracias') {
                    this.restante = this.limite;
                    return;
                }
                this.restante--;
                if (this.restante <= 0) {
                    this.restante = this.limite;
                    this.vencido();
                }
            }, 1000);
        },
        actividad() { this.restante = this.limite; },
        vencido() {
            const dni = document.getElementById('dni');
            const habiaDni = dni && dni.value !== '';
            if (dni) dni.value = '';
            if (this.$wire.paso !== 'inicio' || habiaDni) this.$wire.reiniciar();
        },
        get avisando() {
            return this.$wire.paso !== 'inicio' && this.$wire.paso !== 'gracias' && this.restante <= this.aviso;
        },
     }"
     x-on:pointerdown.window="actividad()"
     x-on:keydown.window="actividad()">
    <div x-show="avisando" style="display: none;" role="alert" aria-live="assertive"
         class="fixed inset-x-0 top-0 z-50 bg-colegio-gold px-5 py-3 text-center text-sm font-semibold text-colegio-navy shadow-lg">
        ¿Seguís ahí? Por seguridad, la pantalla volverá al inicio en <span x-text="restante"></span> s. Tocá la pantalla para continuar.
    </div>

    <header class="school-header px-5 py-4 md:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('escudo_colegio.gif') }}" alt="Escudo del colegio" class="h-14 w-11 object-contain mix-blend-multiply">
                <div>
                    <p class="school-eyebrow">Centro de estudiantes</p>
                    <h1 class="mt-1 text-sm font-semibold md:text-base">{{ $eleccion?->nombre ?? 'Elecciones estudiantiles' }}</h1>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold tracking-tight text-colegio-navy">{{ $eleccion?->anio ?? date('Y') }}</p>
                <p class="text-xs text-slate-500">Votación estudiantil</p>
            </div>
        </div>
    </header>

    @if ($paso !== 'gracias')
        <nav aria-label="Pasos de la votación" class="px-5 pt-7 pb-2">
            <ol class="mx-auto flex max-w-md items-center justify-center gap-3 text-xs md:gap-5">
                @foreach (['inicio' => 'Identificación', 'confirmar' => 'Identidad', 'boleta' => 'Tu voto'] as $etapa => $etiqueta)
                    <li wire:key="paso-{{ $etapa }}" class="flex items-center gap-2 {{ $paso === $etapa ? 'font-semibold text-colegio-navy' : 'text-slate-400' }}" @if ($paso === $etapa) aria-current="step" @endif>
                        <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $paso === $etapa ? 'bg-colegio-navy text-white' : 'bg-slate-200/70' }}">{{ $loop->iteration }}</span>
                        {{ $etiqueta }}
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    @if ($paso === 'inicio')
        <main class="flex flex-1 items-center justify-center px-5 py-8 md:py-12">
            <div class="grid w-full max-w-4xl items-center gap-10 md:grid-cols-2 md:gap-16">
                <section class="text-center md:text-left">
                    <p class="school-eyebrow">Tu voz, nuestra comunidad</p>
                    <h2 class="mt-4 text-4xl font-semibold leading-tight tracking-tight text-colegio-navy lg:text-5xl">El próximo capítulo<br>lo elegís vos<span class="text-colegio-gold">.</span></h2>
                    <p class="mx-auto mt-5 max-w-sm text-sm leading-7 text-slate-500 md:mx-0">Participá en la elección del centro de estudiantes. Un momento para hacer escuchar tu voz y construir juntos lo que viene.</p>
                    <div class="mt-7 inline-flex items-center gap-2 rounded-full border border-colegio-navy/10 bg-white/70 px-4 py-2 text-xs text-colegio-navy">
                        <x-heroicon-o-lock-closed class="h-4 w-4" /> Tu voto es secreto
                    </div>
                </section>
                <form wire:submit="buscar" class="school-card p-7 md:p-8">
                    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-colegio-blue/10 text-colegio-navy"><x-heroicon-o-identification class="h-6 w-6" /></div>
                    <h2 class="text-xl font-semibold tracking-tight text-colegio-navy">Empecemos por tu DNI</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Lo usamos para encontrarte en el padrón.</p>
                    <label for="dni" class="mt-6 mb-2 block text-xs font-semibold">Número de documento</label>
                    <input id="dni" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="9" autocomplete="off" autofocus wire:model="dni"
                           x-on:keydown="if ($event.key.length === 1 && ! $event.ctrlKey && ! $event.metaKey && ! /[0-9]/.test($event.key)) $event.preventDefault()"
                           x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                           aria-describedby="dni-ayuda{{ $error ? ' voto-error' : '' }}" @if ($error) aria-invalid="true" @endif
                           class="h-13 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-xl tracking-widest placeholder:text-base placeholder:tracking-normal placeholder:text-slate-400 focus:border-colegio-blue focus:bg-white focus:outline-none"
                           placeholder="Ingresá tu DNI">
                    <p id="dni-ayuda" class="mt-2 text-xs text-slate-400">Solo números, sin puntos ni espacios.</p>
                    @if ($error)
                        <p id="voto-error" role="alert" class="school-error mt-4">{{ $error }}</p>
                    @endif
                    <button type="submit" wire:loading.attr="disabled" class="school-button school-button-primary mt-6 w-full">
                        <span wire:loading.remove wire:target="buscar">Continuar</span><span wire:loading wire:target="buscar">Buscando…</span>
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </button>
                    <p class="mt-5 text-center text-xs leading-5 text-slate-400">Si necesitás ayuda, consultá a la mesa de votación.</p>
                </form>
            </div>
        </main>
    @endif

    @if ($paso === 'confirmar' && $votante)
        <main class="flex flex-1 items-center justify-center px-5 py-10">
            <section class="school-card w-full max-w-lg p-7 md:p-9">
                <p class="school-eyebrow">Un paso antes de elegir</p>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-colegio-navy">¿Estos son tus datos?</h2>
                <p class="mt-2 text-sm text-slate-500">Verificá tu identidad para continuar.</p>
                <div class="my-7 flex items-center gap-4 rounded-xl border border-colegio-blue/15 bg-colegio-blue/5 p-5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-colegio-navy"><x-heroicon-o-user class="h-6 w-6" /></div>
                    <div class="min-w-0">
                        <p class="text-lg font-semibold break-words text-colegio-navy">{{ $votante->nombreCompleto() }}</p>
                        @if ($votante->curso)<p class="mt-1 text-sm text-slate-500">{{ $votante->curso }}</p>@endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="noSoyYo" wire:loading.attr="disabled" class="school-button school-button-secondary flex-1">No, volver</button>
                    <button wire:click="esMiIdentidad" wire:loading.attr="disabled" class="school-button school-button-primary flex-1">Sí, soy yo <x-heroicon-o-arrow-right class="h-4 w-4" /></button>
                </div>
            </section>
        </main>
    @endif

    @if ($paso === 'boleta')
        <main class="mx-auto w-full max-w-6xl flex-1 px-5 pt-7 pb-8">
            <div class="mb-7 text-center">
                <p class="school-eyebrow">La decisión es tuya</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-colegio-navy">Elegí tu lista</h2>
                <p class="mt-2 text-sm text-slate-500">Conocé a los candidatos, seleccioná una opción y confirmá tu voto.</p>
            </div>
            <div class="grid items-start gap-5 {{ $listas->count() === 1 ? 'mx-auto max-w-lg' : ($listas->count() === 2 ? 'md:grid-cols-2' : 'md:grid-cols-2 xl:grid-cols-3') }}">
                @foreach ($listas as $lista)
                    @php($activa = $seleccion === $lista->id)
                    <button type="button" wire:click="elegir({{ $lista->id }})" wire:key="lista-{{ $lista->id }}" aria-pressed="{{ $activa ? 'true' : 'false' }}" class="ballot-card w-full">
                        <div class="h-1.5" style="background: {{ $lista->color }}"></div>
                        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                            @if ($lista->logo)
                                <img src="{{ asset('storage/'.$lista->logo) }}" alt="" class="h-11 w-11 rounded-xl border border-slate-100 bg-white object-contain p-1">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-lg font-bold" style="color: {{ $lista->color }}">{{ $lista->numero }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">Lista {{ $lista->numero }}</p>
                                <h3 class="mt-0.5 text-lg font-semibold leading-snug text-colegio-navy">{{ $lista->nombre }}</h3>
                            </div>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border {{ $activa ? 'border-colegio-navy bg-colegio-navy text-white' : 'border-slate-300' }}">@if ($activa)<x-heroicon-m-check class="h-4 w-4" />@endif</span>
                        </div>
                        <ul class="divide-y divide-slate-100 px-5">
                            @foreach ($cargos as $cargo)
                                @php($c = $lista->candidatos->firstWhere('cargo_id', $cargo->id))
                                @if ($c)
                                    <li wire:key="candidato-{{ $lista->id }}-{{ $cargo->id }}" class="flex items-center gap-3 py-2.5">
                                        @if ($c->foto)
                                            <img src="{{ asset('storage/'.$c->foto) }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-heroicon-o-user class="h-4 w-4" /></span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-medium tracking-wide text-slate-400 uppercase">{{ $cargo->nombre }}</p>
                                            <p class="text-sm font-medium break-words">{{ $c->nombreCompleto() }}</p>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <div class="border-t border-slate-100 px-5 py-3 text-center text-xs font-semibold {{ $activa ? 'bg-colegio-navy/5 text-colegio-navy' : 'bg-slate-50 text-slate-500' }}">{{ $activa ? 'Lista seleccionada' : 'Seleccionar esta lista' }}</div>
                    </button>
                @endforeach
            </div>
        </main>
        <footer class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-md">
            <div class="mx-auto max-w-6xl">
                @if ($error)<p role="alert" class="school-error mb-3">{{ $error }}</p>@endif
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="flex items-center gap-2 text-xs text-slate-500"><x-heroicon-o-lock-closed class="h-4 w-4 text-colegio-gold" /> Tu elección es personal y secreta.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($eleccion->permite_voto_en_blanco)
                            <button type="button" wire:click="elegir(0)" aria-pressed="{{ $seleccion === 0 ? 'true' : 'false' }}" class="school-button school-button-secondary {{ $seleccion === 0 ? 'ring-2 ring-colegio-navy ring-offset-2' : '' }}">@if ($seleccion === 0)<x-heroicon-m-check class="h-4 w-4" />@endif Voto en blanco</button>
                        @endif
                        <button type="button" wire:click="votar" wire:loading.attr="disabled" @disabled($seleccion === null) class="school-button school-button-primary">
                            <span wire:loading.remove wire:target="votar">Confirmar mi voto</span><span wire:loading wire:target="votar">Registrando…</span><x-heroicon-o-arrow-right class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </footer>
    @endif

    @if ($paso === 'gracias')
        <main class="flex flex-1 items-center justify-center px-5 py-12" x-data x-init="setTimeout(() => $wire.reiniciar(), 6000)">
            <section role="status" class="school-card w-full max-w-lg p-10 text-center">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-colegio-blue/10 text-colegio-navy"><x-heroicon-o-check-badge class="h-9 w-9" /></div>
                <p class="school-eyebrow">Tu voz ya es parte</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-colegio-navy">¡Gracias por participar!</h2>
                <p class="mt-4 text-sm leading-7 text-slate-500">Tu voto fue registrado correctamente.<br>Ya podés retirarte y dar lugar al próximo estudiante.</p>
                <div class="mx-auto mt-7 h-1 w-12 rounded-full bg-colegio-gold/50"></div>
                <p class="mt-5 text-xs text-slate-400">La pantalla volverá al inicio automáticamente.</p>
            </section>
        </main>
    @endif

    @if ($paso !== 'boleta')
        <footer class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 px-5 py-5 text-[11px] text-slate-400">
            <span>Elecciones del centro de estudiantes</span><span aria-hidden="true" class="text-colegio-gold">✦</span><span>Participar también es construir comunidad</span>
        </footer>
    @endif
</div>
