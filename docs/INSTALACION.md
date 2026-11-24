# Instalación y uso — Elecciones del Centro de Estudiantes

## Cómo funciona
- Una app Laravel con base **SQLite** (`database/database.sqlite`) publicada en un hosting.
- Las PCs de la sala **no instalan nada**: abren Edge en modo kiosco.
- El voto es secreto: la tabla `conteo_votos` guarda solo totales por lista (sin votante ni hora).
  El padrón guarda *que* alguien votó y desde qué puesto, nunca *qué* votó.

## 1. Publicar en el hosting
Requisitos: PHP 8.3+, extensiones `pdo_sqlite`, `mbstring`, `intl`, `fileinfo`, `zip`; HTTPS.

1. Subir el proyecto **incluyendo `public/build`** (los assets ya compilados; no hace falta Node en el hosting).
   Se compila localmente con `npm run build`.
2. Apuntar el dominio/subdominio a la carpeta **`public/`** (document root).
3. Crear el `.env` a partir de `.env.example` y definir:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://elecciones.tucolegio.edu.ar
   ADMIN_EMAIL=...         ADMIN_PASSWORD=...        # contraseñas propias, largas
   DIRECTIVO_EMAIL=...     DIRECTIVO_PASSWORD=...
   ```
   Estas cuatro son obligatorias en producción: `db:seed` se detiene con un error si falta alguna o si la contraseña es la de ejemplo (`cambiar-esta-clave`).
4. Con SSH (o desde el panel del hosting si ofrece terminal):
   ```
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate --force
   php artisan db:seed --force        # crea los usuarios admin y directivo
   php artisan storage:link
   php artisan filament:optimize
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   Sin SSH: ejecutar esos pasos en tu PC, y subir por FTP `vendor/`, `database/database.sqlite`, `public/build`, `public/storage` (o `storage:link` si el hosting lo permite).
5. Dar permisos de escritura a `storage/`, `bootstrap/cache/` y `database/` (incluida la carpeta, SQLite crea archivos `-wal`/`-shm`).
6. Si el hosting está detrás de un proxy/CDN (Cloudflare), configurar los proxies de confianza; de lo contrario `request()->ip()` no será la IP real y la restricción por IP no funcionará.

> **Seguridad:** `database/` y `.env` nunca deben quedar accesibles por web. Con el document root en `public/` no lo están.
> Hacer un **respaldo** del archivo `database/database.sqlite` antes y después de la votación.

## 2. Preparar la elección (admin → `/admin`)
1. **Elecciones**: crear la del año (o "Duplicar para nuevo año" desde la del año anterior: copia cargos y configuración).
2. En la elección, pestaña **Cargos**: definir y ordenar los cargos de la boleta.
3. **Listas y candidatos**: crear cada lista (número, nombre, color, logo) y cargar un candidato por cargo con su foto.
4. **Padrón**: importar el CSV (`dni, apellido, nombre, curso`; si es Excel, "Guardar como CSV"). Se puede reimportar sin duplicar.
5. **Puestos (PCs)**: crear "PC 1" … "PC 8". En cada PC abrir **una vez** el enlace de registro que aparece en la tabla.
6. Opcional: en la elección, "Seguridad", cargar la IP pública del colegio (o un rango, ej. `192.168.1.0/24`) para que solo se vote desde ahí.

> **Bloqueo al abrir:** una vez abierta la votación ya no se pueden agregar, editar ni borrar listas, cargos ni candidatos, ni borrar la elección ni cambiar el voto en blanco. Revisar todo antes de abrir. El padrón sí admite altas (alumno que falta) hasta cerrar la votación; quien ya votó no se puede borrar ni cambiarle el DNI.

## 3. Acceso directo en cada PC de la sala
**Forma rápida:** en **Puestos (PCs)** usar el botón **Acceso directo** de cada puesto. Descarga un instalador (`.bat`); se lo lleva a esa PC y se hace doble clic. Crea en el Escritorio un acceso directo con el escudo del colegio que abre Edge en modo kiosco con el enlace de ese puesto (así también registra la PC). Requiere internet en la PC y Edge instalado.
El acceso directo lleva las MAC de las placas de red de esa PC (cable y Wi-Fi): la **primera vez que se abre vincula el puesto a esa PC**, y desde ahí el enlace no funciona en ninguna otra (ni copiándolo). En **Puestos** se ve la MAC vinculada; si hay que reemplazar una PC o una placa, usar **Desvincular**. También se puede cargar la MAC a mano en el puesto (`ipconfig /all`) para que el enlace exija esa PC desde el primer uso. Importante: abrir el acceso directo en la PC correcta antes de compartir o dejar circulando el enlace, porque hasta esa primera vez cualquiera podría vincularlo. Si Windows muestra el aviso de SmartScreen: *Más información → Ejecutar de todas formas*.

**Forma manual:** propiedades de un acceso directo de Edge → Destino:
```
"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk https://elecciones.tucolegio.edu.ar/votar --edge-kiosk-type=fullscreen
```
Salir del modo kiosco: `Alt+F4`.

Red de seguridad: si una PC se abre sin kiosco, o alguien sale de la pantalla completa (Esc / F11) o recarga la página, aparece una capa "Tocá la pantalla para comenzar". El navegador no permite activar la pantalla completa sin un gesto del usuario, por eso hace falta ese toque; al darlo, vuelve a pantalla completa.
Además, si un alumno se aleja a mitad de la votación, tras 90 segundos sin tocar la pantalla (con aviso en los últimos 15) se vuelve al inicio y se descarta lo que había elegido.

## 4. El día de la votación
1. El **directivo** entra a `/admin` → **Mesa de votación** → *Abrir votación*. Ve la participación en tiempo real, total y por curso. Las acciones de la Mesa son solo del directivo; el admin la ve en modo lectura.
2. Los alumnos votan: DNI (solo números) → confirmar identidad → elegir lista (o blanco) → VOTAR.
3. Al terminar: *Cerrar votación*. Si se cerró por error: *Reabrir votación* (pide la contraseña del directivo).
4. Con la votación cerrada, *Acta de escrutinio* muestra el acta lista para imprimir y firmar (votos por lista, blancos, total y control contra los que votaron).
5. Con los candidatos reunidos: *Habilitar resultados* (pide la contraseña del directivo) → *Abrir pantalla de resultados* y proyectar `/resultados`.
   Se avanza con la **barra espaciadora**: cuenta regresiva → barras → ganador con confeti.

## 5. Para el año siguiente
Respaldar el `.sqlite`, y en **Elecciones** usar *Duplicar para nuevo año*. Cargar listas, candidatos y padrón nuevos.

## Ensayo recomendado
Antes del día real: `php artisan db:seed --class=DemoSeeder` (elección de ejemplo con 30 alumnos), probar con las 8 PCs en simultáneo y luego borrar `database/database.sqlite`, volver a correr `migrate` y `db:seed`.
