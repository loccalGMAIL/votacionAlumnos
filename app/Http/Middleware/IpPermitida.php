<?php

namespace App\Http\Middleware;

use App\Models\Eleccion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Si la elección define IPs permitidas, solo se vota desde ellas (opcional). */
class IpPermitida
{
    public function handle(Request $request, Closure $next): Response
    {
        $eleccion = Eleccion::actual();

        if ($eleccion && ! $eleccion->ipPermitida($request->ip())) {
            return response()->view('votar.no-registrado', ['motivo' => 'ip'], 403);
        }

        return $next($request);
    }
}
