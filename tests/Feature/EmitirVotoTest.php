<?php

use App\Actions\EmitirVoto;
use App\Enums\EstadoEleccion;
use App\Models\ConteoVoto;
use App\Models\Eleccion;
use App\Models\Votante;
use Illuminate\Support\Facades\Schema;

function eleccionAbierta(array $attrs = []): array
{
    // Las listas se cargan en preparación: con la votación abierta la boleta ya no se puede tocar.
    $e = Eleccion::create(['nombre' => 'Test', 'anio' => 2026] + $attrs);
    $l1 = $e->listas()->create(['numero' => 1, 'nombre' => 'Lista 1']);
    $l2 = $e->listas()->create(['numero' => 2, 'nombre' => 'Lista 2']);
    $v = $e->votantes()->create(['dni' => '40123456', 'apellido' => 'Perez', 'nombre' => 'Ana']);
    $e->update(['estado' => EstadoEleccion::Abierta]);

    return [$e, $l1, $l2, $v];
}

it('suma un voto a la lista y marca al votante', function () {
    [$e, $l1, , $v] = eleccionAbierta();

    (new EmitirVoto)($e, $v, $l1->id);

    expect(ConteoVoto::where('lista_id', $l1->id)->value('cantidad'))->toBe(1)
        ->and($v->fresh()->ya_voto)->toBeTrue()
        ->and($v->fresh()->voto_en)->not->toBeNull();
});

it('registra el voto en blanco con lista null', function () {
    [$e, , , $v] = eleccionAbierta();

    (new EmitirVoto)($e, $v, null);

    expect(ConteoVoto::whereNull('lista_id')->value('cantidad'))->toBe(1);
});

it('rechaza el voto en blanco si la elección no lo permite', function () {
    [$e, , , $v] = eleccionAbierta(['permite_voto_en_blanco' => false]);

    (new EmitirVoto)($e, $v, null);
})->throws(RuntimeException::class, 'Debe elegir una lista.');

it('no permite votar dos veces', function () {
    [$e, $l1, $l2, $v] = eleccionAbierta();

    (new EmitirVoto)($e, $v, $l1->id);

    expect(fn () => (new EmitirVoto)($e, $v->fresh(), $l2->id))
        ->toThrow(RuntimeException::class, 'Ud. ya ha votado.');
    expect(ConteoVoto::sum('cantidad'))->toBe(1);
});

it('no permite votar si la votación no está abierta', function (EstadoEleccion $estado) {
    [$e, $l1, , $v] = eleccionAbierta();
    $e->update(['estado' => $estado]);

    (new EmitirVoto)($e, $v, $l1->id);
})->with([EstadoEleccion::Borrador, EstadoEleccion::Cerrada, EstadoEleccion::Publicada])
    ->throws(RuntimeException::class, 'La votación no está abierta.');

it('rechaza una lista de otra elección', function () {
    [$e, , , $v] = eleccionAbierta();
    $otra = Eleccion::create(['nombre' => 'Otra', 'anio' => 2025]);
    $ajena = $otra->listas()->create(['numero' => 1, 'nombre' => 'Ajena']);

    (new EmitirVoto)($e, $v, $ajena->id);
})->throws(RuntimeException::class, 'La lista elegida no es válida.');

it('no deja rastro parcial si falla: el votante sigue sin votar', function () {
    [$e, , , $v] = eleccionAbierta();

    try {
        (new EmitirVoto)($e, $v, 9999);
    } catch (RuntimeException) {
    }

    expect($v->fresh()->ya_voto)->toBeFalse()->and(ConteoVoto::count())->toBe(0);
});

it('la urna no tiene ninguna columna que identifique al votante ni la hora', function () {
    $columnas = Schema::getColumnListing('conteo_votos');

    expect($columnas)->toEqualCanonicalizing(['id', 'eleccion_id', 'lista_id', 'cantidad']);
});

it('normaliza el DNI', function () {
    expect(Votante::normalizarDni('40.123.456'))->toBe('40123456');
});
