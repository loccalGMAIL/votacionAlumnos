<?php

namespace App\Actions;

use App\Models\Puesto;
use Illuminate\Support\Str;

/**
 * Genera el instalador (.bat) del acceso directo de una PC de votación.
 *
 * Un .lnk suelto no puede llevar el escudo (el ícono debe existir en la PC), así que el .bat descarga el
 * escudo desde el servidor y crea en el Escritorio un acceso directo a Edge en modo kiosco que abre el
 * enlace personalizado del puesto (registra la PC y redirige a /votar).
 *
 * El enlace lleva las MAC de las placas físicas de la PC (?m=...): el primer uso vincula el puesto a esa PC
 * y, una vez vinculado, el enlace no sirve desde otra.
 */
class GenerarAccesoDirecto
{
    /** Nombre del acceso directo y del archivo: solo ASCII, el .bat no tiene codificación declarada. */
    public function nombre(Puesto $puesto): string
    {
        $limpio = trim(preg_replace('/[^A-Za-z0-9 _-]/', '', Str::ascii($puesto->nombre)));

        return 'Votar - '.($limpio !== '' ? $limpio : 'PC '.$puesto->id);
    }

    public function __invoke(Puesto $puesto): string
    {
        $powershell = implode(' ', [
            '$ErrorActionPreference = \'Stop\';',
            '$dir = Join-Path $env:LOCALAPPDATA \'EleccionesCE\';',
            'New-Item -ItemType Directory -Force $dir | Out-Null;',
            '$ico = Join-Path $dir \'escudo.ico\';',
            'Invoke-WebRequest -UseBasicParsing $env:URL_ICONO -OutFile $ico;',
            '$macs = (Get-NetAdapter -Physical | Where-Object { $_.InterfaceDescription -notmatch \'Bluetooth\' } | ForEach-Object { $_.MacAddress }) -join \',\';',
            'if (-not $macs) { throw \'No se encontro ninguna placa de red en esta PC.\' };',
            '$url = $env:URL_VOTAR + \'?m=\' + $macs;',
            '$edge = @(\'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe\', \'C:\Program Files\Microsoft\Edge\Application\msedge.exe\') | Where-Object { Test-Path $_ } | Select-Object -First 1;',
            'if (-not $edge) { throw \'No se encontro Microsoft Edge en esta PC.\' };',
            '$lnk = Join-Path ([Environment]::GetFolderPath(\'Desktop\')) ($env:NOMBRE + \'.lnk\');',
            '$acceso = (New-Object -ComObject WScript.Shell).CreateShortcut($lnk);',
            '$acceso.TargetPath = $edge;',
            '$acceso.Arguments = \'--kiosk \' + $url + \' --edge-kiosk-type=fullscreen\';',
            '$acceso.IconLocation = $ico;',
            '$acceso.Description = $env:NOMBRE;',
            '$acceso.Save();',
        ]);

        $lineas = [
            '@echo off',
            'setlocal',
            'title Instalar acceso directo - '.$this->paraBat($puesto->nombre),
            'set "NOMBRE='.$this->paraBat($this->nombre($puesto)).'"',
            'set "URL_VOTAR='.$this->paraBat(route('puesto.registrar', $puesto->token)).'"',
            'set "URL_ICONO='.$this->paraBat(asset('escudo_colegio.ico')).'"',
            'echo Creando el acceso directo "%NOMBRE%" en el Escritorio...',
            'powershell -NoProfile -ExecutionPolicy Bypass -Command "'.$powershell.'"',
            'if errorlevel 1 (',
            '    echo.',
            '    echo ERROR: no se pudo crear el acceso directo. Verifique la conexion a internet y que Edge este instalado.',
            ') else (',
            '    echo.',
            '    echo Listo. Use el acceso directo "%NOMBRE%" del Escritorio para abrir la votacion.',
            ')',
            'echo.',
            'pause',
        ];

        return implode("\r\n", $lineas)."\r\n";
    }

    /** Evita que un valor rompa el .bat (comillas, saltos de línea) y duplica % para que cmd no lo expanda. */
    private function paraBat(string $valor): string
    {
        return str_replace('%', '%%', preg_replace('/["\r\n]/', '', Str::ascii($valor)));
    }
}
