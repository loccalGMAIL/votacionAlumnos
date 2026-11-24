<?php

use App\Actions\EmitirVoto;
use App\Actions\GenerarAccesoDirecto;
use App\Filament\Pages\Mesa;
use App\Filament\Resources\Puestos\Pages\ListPuestos;
use App\Models\Eleccion;
use App\Models\Lista;
use App\Models\Puesto;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Actions\Testing\TestAction;

it('el botón de copiar conserva el enlace completo y su código dentro del atributo HTML', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $puesto = Puesto::create(['nombre' => 'PC de prueba']);

    $response = $this->actingAs($admin)->get('/admin/puestos');

    $response->assertOk()->assertSee('Copiar enlace');
    $documento = new DOMDocument;
    $estadoAnterior = libxml_use_internal_errors(true);
    try {
        $documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($estadoAnterior);
    }
    $xpath = new DOMXPath($documento);
    $botones = $xpath->query('//button[contains(., "Copiar enlace")]');

    expect($botones->length)->toBe(1);
    $handler = $botones->item(0)->getAttribute('x-on:click');
    expect($handler)
        ->toContain($puesto->token)
        ->toContain('document.execCommand("copy")')
        ->toContain('.title("Enlace completo copiado")')
        ->toContain('})()');
});

it('el instalador del acceso directo lleva el enlace del puesto, el escudo y Edge en kiosco', function () {
    $puesto = Puesto::create(['nombre' => 'PC Ñandú 1']);

    $script = (new GenerarAccesoDirecto)($puesto);

    expect($script)
        ->toContain('set "NOMBRE=Votar - PC Nandu 1"')
        ->toContain('set "URL_VOTAR='.route('puesto.registrar', $puesto->token).'"')
        ->toContain('set "URL_ICONO='.asset('escudo_colegio.ico').'"')
        ->toContain('--kiosk')
        ->toContain('--edge-kiosk-type=fullscreen')
        ->toContain('IconLocation')
        ->toContain('Get-NetAdapter -Physical')
        ->toContain("'?m=' + \$macs")
        ->toContain("\r\n")
        ->and(preg_match('/[^\x00-\x7F]/', $script))->toBe(0);
});

it('el instalador no se rompe con comillas ni símbolos en el nombre del puesto', function () {
    $puesto = Puesto::create(['nombre' => 'Sala "A" & 100%']);

    $script = (new GenerarAccesoDirecto)($puesto);

    expect($script)->toContain('set "NOMBRE=Votar - Sala A  100"')
        ->and($script)->toContain('title Instalar acceso directo - Sala A & 100%%');
});

it('el admin descarga el instalador del acceso directo desde Puestos', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $puesto = Puesto::create(['nombre' => 'PC 1']);

    Livewire\Livewire::actingAs($admin)->test(ListPuestos::class)
        ->callAction(TestAction::make('accesoDirecto')->table($puesto))
        ->assertFileDownloaded('Instalar acceso directo - Votar - PC 1.bat');
});

it('el admin puede desvincular un puesto y el siguiente acceso directo lo vincula de nuevo', function () {
    $admin = User::factory()->create(['rol' => 'admin']);
    $puesto = Puesto::create(['nombre' => 'PC 1', 'mac' => 'AA-BB-CC-DD-EE-01']);

    Livewire\Livewire::actingAs($admin)->test(ListPuestos::class)
        ->assertTableActionVisible('desvincular', $puesto)
        ->callAction(TestAction::make('desvincular')->table($puesto));
    expect($puesto->fresh()->mac)->toBeNull();

    $this->get(route('puesto.registrar', $puesto->token).'?m=11-22-33-44-55-66')->assertRedirect('/votar');
    expect($puesto->fresh()->mac)->toBe('11-22-33-44-55-66');
});

it('todas las pantallas del panel renderizan para el admin', function (string $ruta) {
    $this->seed(DemoSeeder::class);
    $admin = User::where('rol', 'admin')->first();
    $lista = Lista::first();
    $reemplazos = ['{eleccion}' => Eleccion::first()->id, '{lista}' => $lista->id, '{puesto}' => Puesto::first()->id];

    $this->actingAs($admin)->get('/admin/'.strtr($ruta, $reemplazos))->assertOk();
})->with([
    '', 'mesa', 'elecciones', 'elecciones/create', 'elecciones/{eleccion}/edit',
    'listas', 'listas/create', 'listas/{lista}/edit',
    'votantes', 'votantes/create', 'puestos', 'puestos/create', 'puestos/{puesto}/edit',
]);

it('el admin ve la mesa en modo lectura: no puede operarla', function () {
    $this->seed(DemoSeeder::class);
    $admin = User::where('rol', 'admin')->first();

    Livewire\Livewire::actingAs($admin)->test(Mesa::class)
        ->assertSee('Solo el directivo puede operar la mesa')
        ->assertActionHidden('abrir');

    expect(Eleccion::first()->estado->value)->toBe('borrador');
});

it('la mesa reabre una votación cerrada solo con la contraseña del directivo', function () {
    $this->seed(DemoSeeder::class);
    $directivo = User::where('rol', 'directivo')->first();
    $e = Eleccion::first();
    $e->abrir();
    $e->fresh()->cerrar();

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('reabrir', ['password' => 'incorrecta']);
    expect($e->fresh()->estado->value)->toBe('cerrada');

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('reabrir', ['password' => 'cambiar-esta-clave']);
    expect($e->fresh()->estado->value)->toBe('abierta')->and($e->fresh()->cerrada_en)->toBeNull();
});

it('la mesa muestra la participación por curso', function () {
    $this->seed(DemoSeeder::class);
    $admin = User::where('rol', 'admin')->first();
    $e = Eleccion::first();
    $e->abrir();
    (new EmitirVoto)($e->fresh(), $e->votantes()->where('curso', '5° A')->first(), null);

    Livewire\Livewire::actingAs($admin)->test(Mesa::class)
        ->assertSee('Participación por curso')
        ->assertSee('1 de 15')
        ->assertSee('0 de 15');
});

it('el acta solo la ve el directivo y recién con la votación cerrada', function () {
    $this->seed(DemoSeeder::class);
    $admin = User::where('rol', 'admin')->first();
    $directivo = User::where('rol', 'directivo')->first();
    $e = Eleccion::first();
    $lista = $e->listas()->first();

    $this->get(route('acta'))->assertRedirect(route('filament.admin.auth.login'));

    $e->abrir();
    $this->actingAs($directivo)->get(route('acta'))->assertForbidden();

    foreach ($e->votantes()->take(3)->get() as $i => $votante) {
        (new EmitirVoto)($e->fresh(), $votante, $i < 2 ? $lista->id : null);
    }
    $e->fresh()->cerrar();

    $this->actingAs($admin)->get(route('acta'))->assertForbidden();
    $this->actingAs($directivo)->get(route('acta'))->assertOk()
        ->assertSee('Acta de escrutinio')
        ->assertSee('Votos en blanco')
        ->assertSee('coincide con los estudiantes que votaron');
});

it('la mesa del directivo abre, cierra y habilita los resultados con contraseña', function () {
    $this->seed(DemoSeeder::class);
    $directivo = User::where('rol', 'directivo')->first();
    $e = Eleccion::first();

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('abrir');
    expect($e->fresh()->estado->value)->toBe('abierta');

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('cerrar');
    expect($e->fresh()->estado->value)->toBe('cerrada');

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('publicar', ['password' => 'incorrecta']);
    expect($e->fresh()->estado->value)->toBe('cerrada');

    Livewire\Livewire::actingAs($directivo)->test(Mesa::class)
        ->callAction('publicar', ['password' => 'cambiar-esta-clave']);
    expect($e->fresh()->estado->value)->toBe('publicada');
});
