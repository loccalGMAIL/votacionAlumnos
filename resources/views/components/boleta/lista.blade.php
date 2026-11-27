{{-- Tarjeta de una lista en la boleta. La usan el cuarto oscuro y la vista previa del admin, así nunca se desfasan.
     "previa" marca los cargos sin candidato para detectar listas incompletas antes de abrir la votación. --}}
@props(['lista', 'cargos', 'activa' => false, 'previa' => false])

<button type="button" {{ $attributes->merge(['class' => 'ballot-card w-full']) }} @unless ($attributes->has('x-bind:aria-pressed')) aria-pressed="{{ $activa ? 'true' : 'false' }}" @endunless>
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
        @isset($marca)
            {{ $marca }}
        @else
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border {{ $activa ? 'border-colegio-navy bg-colegio-navy text-white' : 'border-slate-300' }}">@if ($activa)<x-heroicon-m-check class="h-4 w-4" />@endif</span>
        @endisset
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
            @elseif ($previa)
                <li class="flex items-center gap-3 py-2.5 opacity-60">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 text-slate-300"><x-heroicon-o-user class="h-4 w-4" /></span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-medium tracking-wide text-slate-400 uppercase">{{ $cargo->nombre }}</p>
                        <p class="text-sm italic text-slate-400">Sin candidato</p>
                    </div>
                </li>
            @endif
        @endforeach
    </ul>
    @isset($pie)
        {{ $pie }}
    @else
        <div class="border-t border-slate-100 px-5 py-3 text-center text-xs font-semibold {{ $activa ? 'bg-colegio-navy/5 text-colegio-navy' : 'bg-slate-50 text-slate-500' }}">{{ $activa ? 'Lista seleccionada' : 'Seleccionar esta lista' }}</div>
    @endisset
</button>
