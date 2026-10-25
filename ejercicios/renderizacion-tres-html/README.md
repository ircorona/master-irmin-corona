# Tres HTML de una misma página

Ejercicio de **Renderización, clase 02 Qué es la renderización**.

La diapositiva dice: *«El HTML que hay en el servidor no es el mismo que llega al navegador, y no es el mismo que termina viendo el usuario.»* En clase se demostró con un `renderizacion.php` que saluda según la hora: en el editor hay un `if` de PHP; en «Ver código fuente» solo queda `¡Buenos días!`. Este ejercicio rehace esa demo y le añade la tercera versión, la del JavaScript, medida.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`index.php`](index.php) | La demo de clase corregida. `#servidor` lo rellena PHP (circuito 1); `#test` llega vacío y lo rellena un `<script>` en el navegador (circuito 2) |
| [`comparar.php`](comparar.php) | Saca el párrafo `#servidor` y el `#test` de las tres versiones (fichero PHP, HTML por HTTP, DOM de Chrome headless con `--dump-dom`) y hace 4 comprobaciones. Sale con 1 si alguna falla |

## Qué se corrigió del código de clase

| En clase | Problema | Aquí |
|---|---|---|
| `$t = date("H"); if ($t < "5")` | Compara cadenas (PHP 8 las compara como números si las dos son numéricas, así que funciona, pero por casualidad) | `(int) date('G')` |
| `< "5"` → «Buenos días», si no «Buenas tardes» | Da «Buenos días» solo de 00:00 a 04:59 y «Buenas tardes» a las 9 de la mañana | Tres franjas: < 12, < 20, resto |
| Sin zona horaria | `date()` usa la de `php.ini`. En el CLI de Laragon es **UTC** | `date_default_timezone_set('Europe/Madrid')` |

## Cómo probarlo

Con el PHP de Laragon (8.x) y Chrome instalado:

```bash
cd ejercicios/renderizacion-tres-html
php -S 127.0.0.1:8090          # en una terminal
php comparar.php               # en otra
```

Salida real (2026-10-25, 03 h en Madrid, 20 h en México):

```
1. servidor (index.php)  #servidor: <?php if ($hora < 12) { echo '¡Buenos d #test: (vacío)
2. HTML recibido         #servidor: ¡Buenos días!                          #test: (vacío)
3. DOM renderizado       #servidor: ¡Buenos días!                          #test: JS: son las 20 h en tu dispositivo

OK    el HTML recibido no contiene código PHP
OK    el saludo del servidor ya viene en el HTML
OK    #test llega vacío en el HTML
OK    #test solo tiene texto en el DOM renderizado

4/4 comprobaciones correctas
```

Si Chrome no está en la ruta por defecto: `php comparar.php http://127.0.0.1:8090/ "C:/ruta/a/chrome.exe"` (Edge también vale).

## Qué enseña

- **PHP no llega nunca al navegador.** Se ejecuta en el servidor y lo que viaja es su resultado. Para un bot, `#servidor` es texto normal.
- **`#test` está vacío para cualquiera que no ejecute JS** (curl, rastreadores de IA, Screaming Frog sin renderizado). Solo existe en el DOM. Para Google, existe si su renderizado lo captura antes de la *snapshot*.
- **Cada circuito tiene su reloj.** El saludo usa la hora del servidor; el JS, la del dispositivo. La salida de arriba lo enseña: «Buenos días» y «son las 20 h» en la misma página. Googlebot rastrea sobre todo desde EE. UU.: lo que dependa de la hora o del país del servidor puede no ser lo que vea el usuario. Y si hay caché de página, el saludo queda congelado en la hora en que se generó.
