<?php

namespace App\Http\Middleware;

use App\Models\Puesto;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Solo las PCs registradas por el administrador (cookie con token) pueden votar. */
class PuestoRegistrado
{
    public const COOKIE = 'puesto_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(self::COOKIE);
        $puesto = $token ? Puesto::where('token', $token)->where('activo', true)->first() : null;

        if (! $puesto) {
            return response()->view('votar.no-registrado', status: 403);
        }

        $request->attributes->set('puesto', $puesto);

        return $next($request);
    }
}
