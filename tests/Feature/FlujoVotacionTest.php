<?php

use App\Enums\EstadoEleccion;
use App\Http\Middleware\PuestoRegistrado;
use App\Imports\PadronImport;
use App\Livewire\CuartoOscuro;
use App\Models\ConteoVoto;
use App\Models\Eleccion;
use App\Models\Puesto;
use App\Models\User;
use Livewire\Livewire;

function escenario(EstadoEleccion $estado = EstadoEleccion::Abierta): array
{
    // Se arma en preparación y recién al final se pasa al estado pedido (abierta la boleta no se puede tocar).
    $e = Eleccion::create(['nombre' => 'CE', 'anio' => 2026]);
    $e->cargos()->create(['nombre' => 'Presidente', 'orden' => 1]);
    $l1 = $e->listas()->create(['numero' => 1, 'nombre' => 'Uno']);
    $l2 = $e->listas()->create(['numero' => 2, 'nombre' => 'Dos']);
    $e->votantes()->create(['dni' => '40123456', 'apellido' => 'Perez', 'nombre' => 'Ana']);
    $puesto = Puesto::create(['nombre' => 'PC 1']);
    $e->update(['estado' => $estado]);

    return [$e, $l1, $l2, $puesto];
}

it('un navegador sin puesto registrado no puede entrar a /votar', function () {
    escenario();

    $this->get('/votar')->assertForbidden();
});

it('registrar el puesto deja la cookie y habilita /votar', function () {
    [, , , $puesto] = escenario();

    $this->get(route('puesto.registrar', $puesto->token))->assertRedirect('/votar');
    $this->withCookie(PuestoRegistrado::COOKIE, $puesto->token)->get('/votar')->assertOk();
});

it('el primer uso del acceso directo vincula el puesto a la PC y el enlace no sirve desde otra', function () {
    [, , , $puesto] = escenario();

    $this->get(route('puesto.registrar', $puesto->token).'?m=aa:bb:cc:dd:ee:01,AA-BB-CC-DD-EE-02')
        ->assertRedirect('/votar');
    expect($puesto->fresh()->mac)->toBe('AA-BB-CC-DD-EE-01,AA-BB-CC-DD-EE-02');

    // La misma PC por la otra placa (cable o Wi-Fi) sigue entrando.
    $this->get(route('puesto.registrar', $puesto->token).'?m=AA-BB-CC-DD-EE-02')->assertRedirect('/votar');

    // Otra PC, o el enlace sin MAC, queda afuera.
    $this->get(route('puesto.registrar', $puesto->token).'?m=11-22-33-44-55-66')->assertForbidden();
    $this->get(route('puesto.registrar', $puesto->token))->assertForbidden();

    // El primero en vincular no se pisa.
    expect($puesto->fresh()->mac)->toBe('AA-BB-CC-DD-EE-01,AA-BB-CC-DD-EE-02');
});

it('una PC distinta a la vinculada no recibe la cookie de puesto', function () {
    [, , , $puesto] = escenario();
    $puesto->update(['mac' => 'AA-BB-CC-DD-EE-01']);

    $this->get(route('puesto.registrar', $puesto->token).'?m=11-22-33-44-55-66')
        ->assertForbidden()
        ->assertCookieMissing(PuestoRegistrado::COOKIE);
});

it('un puesto sin vincular se registra con el enlace común, y con la MAC cargada a mano exige esa PC', function () {
    [, , , $puesto] = escenario();

    $this->get(route('puesto.registrar', $puesto->token))->assertRedirect('/votar');
    expect($puesto->fresh()->mac)->toBeNull();

    $puesto->update(['mac' => 'aabb.ccdd.eeff']);
    expect($puesto->fresh()->mac)->toBe('AA-BB-CC-DD-EE-FF');
    $this->get(route('puesto.registrar', $puesto->token))->assertForbidden();
    $this->get(route('puesto.registrar', $puesto->token).'?m=AA-BB-CC-DD-EE-FF')->assertRedirect('/votar');
});

it('normaliza las MAC y descarta lo que no lo es', function () {
    expect(Puesto::normalizarMacs("aa:bb:cc:dd:ee:ff; basura\n001122334455,AA-BB-CC-DD-EE-FF"))
        ->toBe(['AA-BB-CC-DD-EE-FF', '00-11-22-33-44-55'])
        ->and(Puesto::normalizarMacs(null))->toBe([]);
});

it('un puesto desactivado no puede votar', function () {
    [, , , $puesto] = escenario();
    $puesto->update(['activo' => false]);

    $this->withCookie(PuestoRegistrado::COOKIE, $puesto->token)->get('/votar')->assertForbidden();
});

it('respeta la lista de IPs permitidas', function () {
    [$e, , , $puesto] = escenario();
    $e->update(['ips_permitidas' => "200.1.1.1\n200.1.1.2"]);

    $this->withCookie(PuestoRegistrado::COOKIE, $puesto->token)->get('/votar')->assertForbidden();

    $this->withCookie(PuestoRegistrado::COOKIE, $puesto->token)
        ->withServerVariables(['REMOTE_ADDR' => '200.1.1.2'])->get('/votar')->assertOk();
});

it('el filtro de IPs acepta rangos CIDR', function () {
    [$e] = escenario();
    $e->update(['ips_permitidas' => "10.0.0.5\n192.168.1.0/24"]);
    $e = $e->fresh();

    expect($e->ipPermitida('10.0.0.5'))->toBeTrue()
        ->and($e->ipPermitida('192.168.1.200'))->toBeTrue()
        ->and($e->ipPermitida('192.168.2.1'))->toBeFalse()
        ->and($e->ipPermitida('10.0.0.6'))->toBeFalse()
        ->and($e->ipPermitida(null))->toBeFalse();
});

it('el campo DNI del cuarto oscuro solo admite números', function () {
    [, , , $puesto] = escenario();
    Livewire::withCookie(PuestoRegistrado::COOKIE, $puesto->token);

    Livewire::test(CuartoOscuro::class)
        ->assertSeeHtml('inputmode="numeric"')
        ->assertSeeHtml('pattern="[0-9]*"')
        ->assertSeeHtml('maxlength="9"')
        ->assertSeeHtml('replace(/\D/g');
});

it('flujo completo del alumno: DNI, identidad, elegir, votar', function () {
    [$e, $l1, , $puesto] = escenario();
    Livewire::withCookie(PuestoRegistrado::COOKIE, $puesto->token);

    Livewire::test(CuartoOscuro::class)
        ->set('dni', '40.123.456')->call('buscar')->assertSet('paso', 'confirmar')
        ->call('esMiIdentidad')->assertSet('paso', 'boleta')
        ->call('votar')->assertSet('error', 'Debe elegir una opción.')
        ->call('elegir', $l1->id)->call('votar')
        ->assertSet('paso', 'gracias')->assertSet('votanteId', null);

    expect(ConteoVoto::where('lista_id', $l1->id)->value('cantidad'))->toBe(1)
        ->and($e->votantes()->first()->puesto_id)->toBe($puesto->id);
});

it('informa DNI inexistente, repetido y votación cerrada', function () {
    [$e, $l1, , $puesto] = escenario();
    Livewire::withCookie(PuestoRegistrado::COOKIE, $puesto->token);

    Livewire::test(CuartoOscuro::class)->set('dni', '11111111')->call('buscar')
        ->assertSet('error', 'Su DNI no se encuentra en el padrón.');

    Livewire::test(CuartoOscuro::class)->set('dni', '40123456')->call('buscar')->call('esMiIdentidad')
        ->call('elegir', $l1->id)->call('votar');
    Livewire::test(CuartoOscuro::class)->set('dni', '40123456')->call('buscar')->assertSet('error', 'Ud. ya ha votado.');

    $e->update(['estado' => EstadoEleccion::Cerrada]);
    Livewire::test(CuartoOscuro::class)->set('dni', '40123456')->call('buscar')
        ->assertSet('error', 'La votación no está abierta en este momento.');
});

it('no se puede alterar el votante de la sesión desde el navegador', function () {
    [, , , $puesto] = escenario();
    Livewire::withCookie(PuestoRegistrado::COOKIE, $puesto->token);

    Livewire::test(CuartoOscuro::class)->set('votanteId', 1);
})->throws(Exception::class);

it('los resultados están bloqueados hasta que se publican', function (EstadoEleccion $estado) {
    escenario($estado);

    $this->get('/resultados')->assertForbidden();
})->with([EstadoEleccion::Borrador, EstadoEleccion::Abierta, EstadoEleccion::Cerrada]);

it('los resultados se muestran una vez publicados', function () {
    [$e, $l1] = escenario(EstadoEleccion::Publicada);
    ConteoVoto::create(['eleccion_id' => $e->id, 'lista_id' => $l1->id, 'cantidad' => 3]);

    $this->get('/resultados')->assertOk()->assertSee('Uno');
});

it('transiciones de estado: abrir → cerrar → publicar, con sus reglas', function () {
    [$e] = escenario(EstadoEleccion::Borrador);

    expect(fn () => $e->cerrar())->toThrow(RuntimeException::class);
    expect(fn () => $e->publicar())->toThrow(RuntimeException::class);

    $e->abrir();
    expect($e->fresh()->estado)->toBe(EstadoEleccion::Abierta);
    expect(fn () => $e->fresh()->abrir())->toThrow(RuntimeException::class);

    $e->fresh()->cerrar();
    $e->fresh()->publicar();
    expect($e->fresh()->estado)->toBe(EstadoEleccion::Publicada)->and($e->fresh()->publicada_en)->not->toBeNull();
});

it('no se abre una elección sin listas o sin padrón', function () {
    $e = Eleccion::create(['nombre' => 'Vacía', 'anio' => 2026]);
    expect(fn () => $e->abrir())->toThrow(RuntimeException::class, 'al menos una lista');

    $e->listas()->create(['numero' => 1, 'nombre' => 'A']);
    expect(fn () => $e->abrir())->toThrow(RuntimeException::class, 'padrón está vacío');
});

it('calcula resultados con blancos y porcentajes', function () {
    [$e, $l1, $l2] = escenario();
    ConteoVoto::create(['eleccion_id' => $e->id, 'lista_id' => $l1->id, 'cantidad' => 6]);
    ConteoVoto::create(['eleccion_id' => $e->id, 'lista_id' => $l2->id, 'cantidad' => 3]);
    ConteoVoto::create(['eleccion_id' => $e->id, 'lista_id' => null, 'cantidad' => 1]);

    $r = $e->fresh()->resultados();

    expect($r['total'])->toBe(10)->and($r['blancos'])->toBe(1)
        ->and($r['listas']->first()['lista']->id)->toBe($l1->id)
        ->and($r['listas']->first()['porcentaje'])->toBe(60.0);
});

it('importa el padrón desde CSV con ; y tildes, y reporta errores', function () {
    [$e] = escenario();
    $ruta = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($ruta, "DNI;Apellido;Nombre;Curso\n40.200.300;Gómez;María;5° A\n40123456;Perez;Ana Actualizada;5° B\n;SinDni;Juan;5° A\n");

    $res = (new PadronImport)($e, $ruta);

    expect($res['creados'])->toBe(1)->and($res['actualizados'])->toBe(1)->and($res['errores'])->toHaveCount(1);
    expect($e->votantes()->where('dni', '40200300')->first()->apellido)->toBe('Gómez');
});

it('la importación no pisa a quien ya votó', function () {
    [$e] = escenario();
    $e->votantes()->where('dni', '40123456')->update(['ya_voto' => true]);
    $ruta = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($ruta, "dni,apellido,nombre,curso\n40123456,Otro,Nombre,6° A\n");

    $res = (new PadronImport)($e, $ruta);

    expect($res['omitidos'])->toBe(1)->and($e->votantes()->first()->apellido)->toBe('Perez');
});

it('solo admin y directivo entran al panel; el directivo no ve el padrón', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $directivo = User::factory()->create(['rol' => 'directivo']);

    $this->actingAs($admin)->get('/admin/votantes')->assertOk();
    $this->actingAs($directivo)->get('/admin/mesa')->assertOk();
    $this->actingAs($directivo)->get('/admin/votantes')->assertForbidden();
});
