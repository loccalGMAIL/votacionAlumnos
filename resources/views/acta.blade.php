<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acta de escrutinio — {{ $eleccion->nombre }} {{ $eleccion->anio }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem 1rem; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #0f172a; background: #f1f5f9; }
        .hoja { max-width: 800px; margin: 0 auto; padding: 2.5rem; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; }
        h1 { margin: 0; font-size: 1.5rem; text-align: center; }
        .sub { margin: .25rem 0 2rem; text-align: center; color: #475569; }
        h2 { margin: 2rem 0 .75rem; font-size: 1rem; text-transform: uppercase; letter-spacing: .05em; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .5rem .75rem; border: 1px solid #cbd5e1; text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 700; background: #f8fafc; }
        .control { margin-top: 1.5rem; padding: .75rem 1rem; border-radius: 6px; font-size: .9rem; }
        .control.ok { background: #dcfce7; color: #166534; }
        .control.error { background: #fee2e2; color: #991b1b; }
        .firmas { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2.5rem 2rem; margin-top: 4rem; }
        .firma { padding-top: .5rem; border-top: 1px solid #0f172a; text-align: center; font-size: .85rem; color: #475569; }
        .acciones { max-width: 800px; margin: 0 auto 1rem; text-align: right; }
        button { padding: .5rem 1.25rem; font: inherit; font-weight: 600; color: #fff; background: #1e3a8a; border: 0; border-radius: 6px; cursor: pointer; }
        @media print {
            body { padding: 0; background: #fff; }
            .hoja { max-width: none; padding: 0; border: 0; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>
    <div class="acciones"><button type="button" onclick="window.print()">Imprimir acta</button></div>

    <main class="hoja">
        <h1>Acta de escrutinio</h1>
        <p class="sub">{{ $eleccion->nombre }} — {{ $eleccion->anio }}</p>

        <h2>Datos de la votación</h2>
        <table>
            <tr><th>Apertura</th><td>{{ $eleccion->abierta_en?->format('d/m/Y H:i') ?? '—' }}</td></tr>
            <tr><th>Cierre</th><td>{{ $eleccion->cerrada_en?->format('d/m/Y H:i') ?? '—' }}</td></tr>
            <tr><th>Estudiantes en el padrón</th><td>{{ $padron }}</td></tr>
            <tr><th>Estudiantes que votaron</th><td>{{ $votaron }}</td></tr>
            <tr><th>Participación</th><td>{{ $porcentaje }}%</td></tr>
        </table>

        <h2>Resultado por lista</h2>
        <table>
            <thead>
                <tr><th>Lista</th><th class="num">Votos</th><th class="num">%</th></tr>
            </thead>
            <tbody>
                @foreach ($res['listas'] as $fila)
                    <tr>
                        <td>Lista {{ $fila['lista']->numero }} — {{ $fila['lista']->nombre }}</td>
                        <td class="num">{{ $fila['votos'] }}</td>
                        <td class="num">{{ $fila['porcentaje'] }}%</td>
                    </tr>
                @endforeach
                @if ($eleccion->permite_voto_en_blanco)
                    <tr>
                        <td>Votos en blanco</td>
                        <td class="num">{{ $res['blancos'] }}</td>
                        <td class="num">{{ $res['total'] ? round($res['blancos'] * 100 / $res['total'], 1) : 0 }}%</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr><td>Total de votos</td><td class="num">{{ $res['total'] }}</td><td class="num">{{ $res['total'] ? '100%' : '0%' }}</td></tr>
            </tfoot>
        </table>

        @if ($consistente)
            <p class="control ok">Control: el total de votos ({{ $res['total'] }}) coincide con los estudiantes que votaron ({{ $votaron }}).</p>
        @else
            <p class="control error">Atención: el total de votos ({{ $res['total'] }}) NO coincide con los estudiantes que votaron ({{ $votaron }}). Revisar antes de firmar.</p>
        @endif

        <p style="margin-top: 1.5rem; font-size: .85rem; color: #64748b;">Emitida el {{ now()->format('d/m/Y H:i') }}.</p>

        <div class="firmas">
            <div class="firma">Directivo</div>
            <div class="firma">Fiscal de lista</div>
            <div class="firma">Fiscal de lista</div>
        </div>
    </main>
</body>
</html>
