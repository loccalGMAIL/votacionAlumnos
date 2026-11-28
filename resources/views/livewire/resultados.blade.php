<div
    x-data="{
        etapa: 'espera', // espera → cuenta → barras → ganador
        n: 3,
        avanzar() {
            if (this.etapa === 'espera') { this.etapa = 'cuenta'; this.n = 3; this.contar(); }
            else if (this.etapa === 'barras') { this.etapa = 'ganador'; this.reveladoEn = Date.now(); this.festejar(); }
            else if (this.etapa === 'ganador' && Date.now() - this.reveladoEn > 1500) { window.location.href = '{{ route('filament.admin.pages.dashboard') }}'; }
        },
        reveladoEn: 0,
        contar() {
            const t = setInterval(() => {
                this.n--;
                if (this.n === 0) { clearInterval(t); this.etapa = 'barras'; }
            }, 1000);
        },
        festejar() {
            @if ($ganador)
                const colores = ['{{ $ganador['lista']->color }}', '#ffffff', '#fde047'];
                const fin = Date.now() + 8000;
                (function lluvia() {
                    window.confetti({ particleCount: 6, angle: 60, spread: 70, origin: { x: 0, y: 0.8 }, colors: colores });
                    window.confetti({ particleCount: 6, angle: 120, spread: 70, origin: { x: 1, y: 0.8 }, colors: colores });
                    if (Date.now() < fin) requestAnimationFrame(lluvia);
                })();
            @endif
        },
    }"
    x-on:keydown.window.space.prevent="avanzar()"
    x-on:click="avanzar()"
    class="min-h-screen flex flex-col items-center justify-center px-6 py-10 md:p-12 cursor-pointer select-none overflow-hidden"
>
    {{-- ESPERA --}}
    <div x-show="etapa === 'espera'" class="text-center max-w-3xl">
        <img src="{{ asset('escudo_colegio.gif') }}" alt="Escudo del colegio" class="mx-auto mb-7 h-28 w-24 object-contain mix-blend-multiply">
        <p class="school-eyebrow">La comunidad ya eligió · {{ $eleccion->anio }}</p>
        <h1 class="mt-4 text-4xl md:text-5xl font-semibold tracking-tight text-colegio-navy">{{ $eleccion->nombre }}</h1>
        <p class="mt-6 text-lg text-slate-500">
            Votaron {{ $res['total'] }} de {{ $padron }} estudiantes
        </p>
        <p class="mt-10 inline-flex rounded-full border border-colegio-navy/15 bg-white px-5 py-3 text-sm text-colegio-navy">Presioná la barra espaciadora o hacé clic para ver los resultados</p>
    </div>

    {{-- CUENTA REGRESIVA --}}
    <div x-show="etapa === 'cuenta'" x-cloak class="text-center">
        <p class="school-eyebrow mb-6">Un nuevo capítulo comienza</p>
        <div class="font-semibold leading-none tracking-tighter text-colegio-navy" style="font-size: clamp(8rem, 35vw, 22rem)" x-text="n"></div>
    </div>

    {{-- BARRAS --}}
    <div x-show="etapa === 'barras' || etapa === 'ganador'" x-cloak class="school-card w-full max-w-4xl space-y-7 p-6 md:p-10">
        <div class="text-center" x-show="etapa === 'barras'">
            <p class="school-eyebrow">{{ $eleccion->nombre }} · {{ $eleccion->anio }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-colegio-navy">Así votó nuestra comunidad</h2>
        </div>

        @foreach ($res['listas'] as $r)
            <div wire:key="resultado-{{ $r['lista']->id }}" x-data="{ ancho: 0 }" x-effect="if (etapa === 'barras' || etapa === 'ganador') setTimeout(() => ancho = {{ $r['porcentaje'] }}, 300)" class="space-y-3">
                <div class="flex items-end justify-between gap-4">
                    <div class="flex items-center gap-4">
                        @if ($r['lista']->logo)
                            <img src="{{ asset('storage/'.$r['lista']->logo) }}" class="h-16 w-16 rounded-full bg-white object-contain p-1" alt="">
                        @endif
                        <div>
                            <div class="text-sm uppercase tracking-wider text-slate-400">Lista {{ $r['lista']->numero }}</div>
                            <div class="text-lg md:text-2xl font-semibold text-colegio-navy">{{ $r['lista']->nombre }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl md:text-4xl font-semibold tracking-tight text-colegio-navy">{{ number_format($r['porcentaje'], 1, ',', '.') }}%</div>
                        <div class="text-lg text-slate-400">{{ $r['votos'] }} {{ $r['votos'] === 1 ? 'voto' : 'votos' }}</div>
                    </div>
                </div>
                <div class="h-5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all ease-out" style="background: {{ $r['lista']->color }}; transition-duration: 3500ms" :style="`width: ${ancho}%`"></div>
                </div>
            </div>
        @endforeach

        <p class="border-t border-slate-100 pt-6 text-center text-sm text-slate-500">
            Votos en blanco: {{ $res['blancos'] }} · Total emitidos: {{ $res['total'] }}
        </p>
        <p class="text-center text-xs text-colegio-navy" x-show="etapa === 'barras'">Espacio o clic para revelar al ganador</p>
    </div>

    {{-- GANADOR --}}
    <div x-show="etapa === 'ganador'" x-cloak x-transition.opacity.duration.700ms
         class="fixed inset-0 z-10 flex flex-col items-center justify-center text-center p-8 text-white"
         style="background: radial-gradient(ellipse at top, #3493e44d, transparent 65%), #2f4c70">
        @if ($ganador)
            <img src="{{ asset('escudo_colegio.gif') }}" alt="Escudo del colegio" class="mb-7 h-16 w-14 rounded-lg bg-white object-contain p-1">
            <p class="text-sm uppercase tracking-[0.3em] text-colegio-cream mb-6">Nuestra voz, nuestro futuro</p>
            <p class="text-xl text-white/70 mb-5">La lista elegida es</p>
            @if ($ganador['lista']->logo)
                <img src="{{ asset('storage/'.$ganador['lista']->logo) }}" class="h-56 w-56 rounded-full bg-white object-contain p-3 shadow-2xl mb-8" alt="">
            @endif
            <h2 class="max-w-5xl text-4xl md:text-6xl font-semibold tracking-tight text-white">{{ $ganador['lista']->nombre }}</h2>
            <p class="mt-5 text-xl text-white/80">Lista {{ $ganador['lista']->numero }} · {{ number_format($ganador['porcentaje'], 1, ',', '.') }}%</p>
            <p class="mt-10 text-sm text-colegio-cream">¡Felicitaciones y gracias a todos por participar!</p>
        @else
            <h2 class="text-6xl font-black">Empate</h2>
            <p class="mt-4 text-2xl text-slate-300">No hay una lista ganadora: corresponde definir según el reglamento.</p>
        @endif
        <p class="absolute bottom-6 text-xs text-white/50">Clic o espacio para volver al panel</p>
    </div>
</div>
