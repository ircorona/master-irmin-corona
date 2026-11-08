# Versiones de servidor: la copia arranca o no según el PHP

Ejercicio de **Migraciones, clase 16 Versiones servidor**.

En clase, una copia de una web antigua hecha con Elementor no arranca en el entorno de pruebas («en construcción», todo roto). La causa es la versión de PHP: el entorno tenía PHP 8.2.3 y la web era antigua; con PHP 7.1 arranca. El ejercicio da tres herramientas para no descubrirlo tarde y reproduce los dos tipos de fallo, el que se ve y el que no.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`foto-entorno.php`](foto-entorno.php) | Foto del entorno en JSON (versión, SAPI, `php.ini`, extensiones, ajustes clave). Con `?html=1` por web, `phpinfo()` **solo** con `INFO_GENERAL`, `INFO_CONFIGURATION` e `INFO_MODULES`: sin variables de entorno ni de la petición, donde puede haber contraseñas |
| [`comparar.php`](comparar.php) | Compara la foto del servidor viejo con la del nuevo. Sale con 1 si hay salto de versión mayor o faltan extensiones |
| [`escanear.php`](escanear.php) | Busca en un proyecto lo que PHP 7.0 y 8.0 eliminaron (`each()`, `create_function()`, `mysql_*`, `ereg`, llaves `$s{0}`…) con el tokenizador, sin contar comentarios ni cadenas |
| [`viejo/plantilla-antigua.php7`](viejo/plantilla-antigua.php7) | Código que corría en PHP 7 y no arranca en 8. Extensión `.php7` para que el lint del repositorio no lo bloquee |
| [`viejo/comparacion-silenciosa.php`](viejo/comparacion-silenciosa.php) | Un cambio de PHP 8 que **no** da error: `0 == "todas"` |
| [`prueba.php`](prueba.php) | Lo prueba todo con PHP 8.3 y 8.5 de Laragon |

## Cómo probarlo

Git Bash, con Laragon:

```bash
cd ejercicios/versiones-servidor
/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe prueba.php
```

PowerShell: `& C:\laragon\bin\php\php-8.5.7-nts-Win32-vs17-x64\php.exe prueba.php`

Usarlo en una migración real:

```bash
# en el servidor viejo, por web (sube el fichero, descárgalo y BÓRRALO del servidor)
curl -s https://servidor-viejo/foto-entorno.php > viejo.json
curl -s "https://servidor-viejo/foto-entorno.php?html=1" > phpinfo-viejo.html   # imprimir a PDF desde el navegador
# en el nuevo, igual, y luego
php comparar.php viejo.json nuevo.json
php escanear.php ruta/al/proyecto/
```

Salida real (2026-11-07):

```
1. Fotos del entorno por CLI (8.3 = 'servidor viejo', 8.5 = 'servidor nuevo')
  [OK] las dos fotos son JSON válido  -> 8.3.26 / 8.5.7
     PHP: 8.3.26 (cli) -> 8.5.7 (cli)
       · Cambio de versión menor (8.3 -> 8.5): lee la guía de migración de esa versión
       ! Extensiones que había y ya no: curl, exif, fileinfo, gd, intl, mbstring, mysqli, openssl, pdo_mysql, pdo_sqlite, sodium, sqlite3, xsl
       · Extensiones nuevas: lexbor, uri, zend opcache
       · ini memory_limit: '512M' -> '128M'
       · ini upload_max_filesize: '2G' -> '2M'
       · ini post_max_size: '2G' -> '8M'
       · ini error_reporting: '32767' -> '30719'
     RIESGO: revisar antes de migrar
  [OK] comparar.php detecta las diferencias  -> exit 1

2. phpinfo() seguro por web (php -S, puerto 8137)
  [OK] por web la SAPI es otra (cli-server), la foto la marca  -> cli-server
  [OK] el phpinfo() web trae la configuración y los módulos
  [OK] y NO trae variables de entorno ni de petición

3. La plantilla antigua en PHP 8.3 y 8.5
     8.3: Fatal error: Array and string offset access syntax with curly braces is no longer supported in …plantilla-antigua.php7 on line 10
  [OK] 8.3: no llega a ejecutarse, y el mensaje nombra la causa (llaves)  -> exit 255
     8.5: PHP Parse error:  syntax error, unexpected token "{", expecting ")" in …plantilla-antigua.php7 on line 10
  [OK] 8.5: tampoco se ejecuta, pero el mensaje es un error de sintaxis genérico  -> exit 255

4. Escáner
     …plantilla-antigua.php7:10  $texto{...}  acceso con llaves eliminado en PHP 8.0 (usa [])
     …plantilla-antigua.php7:13  each()  eliminada en PHP 8.0
     …plantilla-antigua.php7:17  create_function()  eliminada en PHP 8.0
     1 ficheros PHP revisados, 3 hallazgos
  [OK] encuentra each(), create_function() y las llaves
  [OK] no da falsos positivos con los nombres escritos como texto (su propio código)  -> 1 ficheros PHP revisados, 0 hallazgos

5. El cambio silencioso
     PHP 8.5.7 | 0 == "todas" -> false | se muestra: página todas
  [OK] en PHP 8, 0 == "todas" es false y la plantilla cambia de rama sin dar error

10/10 comprobaciones OK
```

## Qué enseña

1. **Dos PHP de la misma máquina no son el mismo entorno.** El 8.5.7 de Laragon no tiene `mysqli`, `curl`, `mbstring`, `openssl` ni `gd` activados, y tiene 128 MB de memoria frente a 512. Una copia de WordPress no arrancaría en él, y no por la versión sino por las extensiones. Es el fallo de la clase, en tu propio equipo.
2. **Lo que se rompe a la vista**: `each()`, `create_function()` y las llaves no existen en PHP 8. El código no llega a ejecutarse.
3. **El mensaje depende de la versión**: PHP 8.3 dice exactamente qué pasa («curly braces is no longer supported»); PHP 8.5 da un error de sintaxis genérico. Con el más nuevo cuesta más encontrar la causa.
4. **Lo que se rompe sin avisar**: en PHP 7, `0 == "todas"` era `true`; en PHP 8 es `false`. La plantilla no da error: muestra otra cosa. Este no lo encuentra el escáner ni un vistazo; solo comparar páginas viejas y nuevas.
5. **`phpinfo()` con cuidado**: entero (`INFO_ALL`) incluye variables de entorno, cookies y cabeceras. Para archivar basta con `13`.

## Lo que no mide

- PHP 7 no está instalado en Laragon (y no lo he descargado): el resultado de PHP 7 en `comparacion-silenciosa.php` es el que documenta php.net, no medido.
- El escáner busca una lista corta de eliminaciones; no sustituye las guías de migración de php.net ni una herramienta como PHPCompatibility.
- No toca la configuración de Laragon.
