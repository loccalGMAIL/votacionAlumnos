<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('escudo_colegio.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="kiosco min-h-screen antialiased" oncontextmenu="return false">
    {{ $slot }}

    {{-- Red de seguridad del kiosco: si la PC no está en pantalla completa (se salió con Esc o F11, o se recargó),
         una capa pide un toque; el navegador solo permite entrar en pantalla completa tras un gesto del usuario. --}}
    <div id="kiosco-pantalla-completa" role="button" tabindex="0" style="display: none;"
         class="fixed inset-0 z-[100] flex cursor-pointer flex-col items-center justify-center gap-3 bg-colegio-navy px-6 text-center text-white">
        <p class="text-3xl font-semibold tracking-tight">Tocá la pantalla para comenzar</p>
        <p class="text-sm opacity-70">Se abrirá en pantalla completa.</p>
    </div>
    <script>
        (function () {
            var capa = document.getElementById('kiosco-pantalla-completa');
            if (!capa || !document.documentElement.requestFullscreen) { return; }

            // Edge en modo --kiosk ocupa toda la pantalla sin activar la API de fullscreen: también cuenta como completa.
            function enPantallaCompleta() {
                return !!document.fullscreenElement
                    || (window.innerWidth >= screen.width - 2 && window.innerHeight >= screen.height - 2);
            }
            function actualizar() { capa.style.display = enPantallaCompleta() ? 'none' : 'flex'; }
            function entrar() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(function () {});
                }
            }

            capa.addEventListener('click', entrar);
            document.addEventListener('keydown', function (e) { if (e.key !== 'Escape') { entrar(); } });
            document.addEventListener('fullscreenchange', actualizar);
            window.addEventListener('resize', actualizar);
            actualizar();
        })();
    </script>
</body>
</html>
