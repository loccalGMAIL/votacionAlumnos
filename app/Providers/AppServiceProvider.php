<?php

namespace App\Providers;

use App\Http\Middleware\IpPermitida;
use App\Http\Middleware\PuestoRegistrado;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Las acciones del cuarto oscuro viajan por /livewire/update: sin esto, el control
        // de puesto/IP solo se aplicaría al cargar la página y no al emitir el voto.
        Livewire::addPersistentMiddleware([IpPermitida::class, PuestoRegistrado::class]);
    }
}
