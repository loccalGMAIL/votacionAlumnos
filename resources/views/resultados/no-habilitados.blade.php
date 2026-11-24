<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultados</title>
    <link rel="icon" href="{{ asset('escudo_colegio.ico') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="kiosco min-h-screen flex items-center justify-center p-6 antialiased">
    <main class="school-card w-full max-w-lg p-8 md:p-10 text-center">
        <img src="{{ asset('escudo_colegio.gif') }}" alt="Escudo del colegio" class="mx-auto mb-6 h-20 w-16 object-contain">
        <p class="school-eyebrow mb-3">Cada voz cuenta</p>
        <h1 class="text-2xl font-semibold tracking-tight text-colegio-navy mb-4">Los resultados todavía no están habilitados</h1>
        <p class="text-sm leading-7 text-slate-500">Un directivo debe cerrar la votación y habilitarlos.</p>
    </main>
</body>
</html>
