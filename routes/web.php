<?php

use App\Enums\EstadoEleccion;
use App\Http\Middleware\IpPermitida;
use App\Http\Middleware\PuestoRegistrado;
use App\Livewire\CuartoOscuro;
use App\Livewire\Resultados;
use App\Models\Eleccion;
use App\Models\Puesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/votar');

// Registrar esta PC como puesto de votación (el admin copia el enlace desde "Puestos (PCs)").
// El acceso directo agrega ?m=MAC1,MAC2 (placas de la PC): el primer uso vincula el puesto a esa PC y,
// una vez vinculado, el enlace solo funciona desde ella.
Route::get('/puesto/{token}', function (Request $request, string $token) {
    $puesto = Puesto::where('token', $token)->where('activo', true)->firstOrFail();
    $macs = Puesto::normalizarMacs($request->query('m'));

    if (! $puesto->aceptaDispositivo($macs)) {
        return response()->view('votar.no-registrado', ['motivo' => 'dispositivo'], 403);
    }
    $puesto->vincular($macs);

    Cookie::queue(PuestoRegistrado::COOKIE, $puesto->token, 60 * 24 * 365 * 3);

    return redirect('/votar');
})->middleware('throttle:20,1')->name('puesto.registrar');

// Cuarto oscuro: solo PCs registradas (y, si se configuró, solo desde las IPs permitidas).
Route::livewire('/votar', CuartoOscuro::class)
    ->middleware([IpPermitida::class, PuestoRegistrado::class])
    ->name('votar');

// Acta de escrutinio: solo el directivo, y recién con la votación cerrada.
Route::get('/acta', function () {
    abort_unless(auth()->user()->esDirectivo() && auth()->user()->activo, 403);

    $eleccion = Eleccion::actual();
    abort_unless(
        $eleccion && in_array($eleccion->estado, [EstadoEleccion::Cerrada, EstadoEleccion::Publicada], true),
        403,
        'El acta está disponible una vez cerrada la votación.',
    );

    $padron = $eleccion->votantes()->count();
    $votaron = $eleccion->votantes()->where('ya_voto', true)->count();
    $resultados = $eleccion->resultados();

    return view('acta', [
        'eleccion' => $eleccion,
        'res' => $resultados,
        'padron' => $padron,
        'votaron' => $votaron,
        'porcentaje' => $padron ? round($votaron * 100 / $padron, 1) : 0,
        'consistente' => $resultados['total'] === $votaron,
    ]);
})->middleware('auth')->name('acta');

// Vista previa de la boleta para el admin: la misma tarjeta del cuarto oscuro, en cualquier estado y sin votar.
Route::get('/vista-previa/{eleccion}', function (Eleccion $eleccion) {
    abort_unless(auth()->user()->esAdmin() && auth()->user()->activo, 403);

    return view('votar.vista-previa', [
        'eleccion' => $eleccion,
        'listas' => $eleccion->listas()->with('candidatos.cargo')->get(),
        'cargos' => $eleccion->cargos,
    ]);
})->middleware('auth')->name('boleta.vista-previa');

// Resultados / festejo: el componente se niega a mostrar nada hasta que el directivo los habilite.
Route::livewire('/resultados', Resultados::class)->name('resultados');
