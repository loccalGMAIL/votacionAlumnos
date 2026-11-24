<?php

use App\Enums\EstadoEleccion;
use App\Imports\PadronImport;
use App\Models\Candidato;
use App\Models\Eleccion;
use App\Models\Votante;

/** Elección en preparación con un cargo, una lista, un candidato y un votante. */
function eleccionCompleta(): array
{
    $e = Eleccion::create(['nombre' => 'CE', 'anio' => 2026]);
    $cargo = $e->cargos()->create(['nombre' => 'Presidente', 'orden' => 1]);
    $lista = $e->listas()->create(['numero' => 1, 'nombre' => 'Uno']);
    $candidato = Candidato::create(['lista_id' => $lista->id, 'cargo_id' => $cargo->id, 'apellido' => 'Gomez', 'nombre' => 'Ana']);
    $votante = $e->votantes()->create(['dni' => '40123456', 'apellido' => 'Perez', 'nombre' => 'Juan']);

    return [$e, $cargo, $lista, $candidato, $votante];
}

it('en preparación se puede editar y borrar la boleta', function () {
    [$e, $cargo, $lista, $candidato] = eleccionCompleta();

    $lista->update(['nombre' => 'Uno bis']);
    $cargo->update(['nombre' => 'Presidente/a']);
    $candidato->update(['apellido' => 'Lopez']);
    $candidato->delete();
    $lista->delete();
    $cargo->delete();

    expect($e->listas()->count())->toBe(0)->and($e->cargos()->count())->toBe(0);
});

it('con la votación abierta no se toca la boleta', function (EstadoEleccion $estado) {
    [$e, $cargo, $lista, $candidato] = eleccionCompleta();
    $e->update(['estado' => $estado]);

    expect(fn () => $lista->fresh()->update(['nombre' => 'Otro']))->toThrow(RuntimeException::class)
        ->and(fn () => $lista->fresh()->delete())->toThrow(RuntimeException::class)
        ->and(fn () => $cargo->fresh()->delete())->toThrow(RuntimeException::class)
        ->and(fn () => $candidato->fresh()->update(['apellido' => 'Otro']))->toThrow(RuntimeException::class)
        ->and(fn () => $candidato->fresh()->delete())->toThrow(RuntimeException::class)
        ->and(fn () => $e->listas()->create(['numero' => 2, 'nombre' => 'Nueva']))->toThrow(RuntimeException::class)
        ->and($e->listas()->count())->toBe(1);
})->with([EstadoEleccion::Abierta, EstadoEleccion::Cerrada, EstadoEleccion::Publicada]);

it('no se borra una elección abierta ni se cambia su voto en blanco, pero sí su nombre', function () {
    [$e] = eleccionCompleta();
    $e->update(['estado' => EstadoEleccion::Abierta]);
    $e = $e->fresh();

    expect(fn () => $e->delete())->toThrow(RuntimeException::class)
        ->and(fn () => $e->update(['permite_voto_en_blanco' => false]))->toThrow(RuntimeException::class);

    $e->fresh()->update(['nombre' => 'CE 2026', 'ips_permitidas' => '10.0.0.1']);
    expect($e->fresh()->nombre)->toBe('CE 2026');
});

it('una elección en preparación sí se puede borrar y arrastra su boleta', function () {
    [$e] = eleccionCompleta();

    $e->delete();

    expect(Eleccion::count())->toBe(0);
});

it('quien ya votó no se borra ni cambia de DNI, pero sí puede corregirse su nombre', function () {
    [, , , , $votante] = eleccionCompleta();
    $votante->update(['ya_voto' => true]);
    $votante = $votante->fresh();

    expect(fn () => $votante->delete())->toThrow(RuntimeException::class)
        ->and(fn () => $votante->update(['dni' => '40999999']))->toThrow(RuntimeException::class)
        ->and(fn () => $votante->update(['ya_voto' => false]))->toThrow(RuntimeException::class);

    $votante->fresh()->update(['nombre' => 'Juan Carlos']);
    expect($votante->fresh()->nombre)->toBe('Juan Carlos')->and($votante->fresh()->ya_voto)->toBeTrue();
});

it('quien no votó se puede borrar', function () {
    [, , , , $votante] = eleccionCompleta();

    $votante->delete();

    expect(Votante::count())->toBe(0);
});

it('el padrón admite altas con la votación abierta pero no cerrada', function () {
    [$e] = eleccionCompleta();
    $ruta = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($ruta, "dni,apellido,nombre,curso\n40200300,Gomez,Maria,5° A\n");

    $e->update(['estado' => EstadoEleccion::Abierta]);
    expect((new PadronImport)($e->fresh(), $ruta)['creados'])->toBe(1);

    $e->update(['estado' => EstadoEleccion::Cerrada]);
    file_put_contents($ruta, "dni,apellido,nombre,curso\n40200301,Gomez,Mario,5° A\n");
    expect(fn () => (new PadronImport)($e->fresh(), $ruta))->toThrow(InvalidArgumentException::class, 'ya está cerrada');
    expect($e->votantes()->count())->toBe(2);
});

it('reabrir solo vale desde cerrada y limpia la hora de cierre', function () {
    [$e] = eleccionCompleta();

    expect(fn () => $e->reabrir())->toThrow(RuntimeException::class);

    $e->abrir();
    expect(fn () => $e->fresh()->reabrir())->toThrow(RuntimeException::class);

    $e->fresh()->cerrar();
    expect($e->fresh()->cerrada_en)->not->toBeNull();

    $e->fresh()->reabrir();
    expect($e->fresh()->estado)->toBe(EstadoEleccion::Abierta)->and($e->fresh()->cerrada_en)->toBeNull();
});
