# Clonar la base de datos: el dominio, los serializados y el wp-config

Ejercicio de **Migraciones, clase 17 DDBB**.

En clase se exporta la base de datos, se abre el `.sql` en **Notepad++**, se busca el dominio viejo y se pulsa **«Reemplazar todo»** (`sanchezdonate.test` → `donatesanchez.test`, 72 coincidencias), y se importa en phpMyAdmin o HeidiSQL. Y se propone un `wp-config.php` que elige base de datos y depuración según `$_SERVER['HTTP_HOST']`. El ejercicio mide cuándo funciona cada cosa y cuándo no.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`reemplazo.php`](reemplazo.php) | `reemplazo_ingenuo()` (lo que hace «Reemplazar todo»), `reemplazo_seguro()` (deserializa, cambia y vuelve a serializar, como `wp search-replace`) y `contar_dominio()` para un `.sql` o `.sql.gz` (solo cifras, no imprime contenido) |
| [`config-carlos.php`](config-carlos.php) | El `wp-config` condicional de la clase, tal cual, con las credenciales enmascaradas como venían |
| [`config-seguro.php`](config-seguro.php) | Alternativa: el entorno lo fija el servidor con `WP_ENVIRONMENT_TYPE`; si no está, producción |
| [`ver-config.php`](ver-config.php) | Carga una de las dos y devuelve en JSON qué constantes quedaron |
| [`prueba.php`](prueba.php) | Lo prueba todo (puerto 8138; usa `curl.exe` para mandar cabeceras `Host`) |

## Cómo probarlo

Git Bash, con Laragon:

```bash
cd ejercicios/clonar-base-de-datos
/c/laragon/bin/php/php-8.3.26-Win32-vs16-x64/php.exe prueba.php
```

PowerShell: `& C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe prueba.php`

Contar el dominio en tu propio volcado antes de reemplazar nada:

```bash
php -r 'require "reemplazo.php"; echo json_encode(contar_dominio($argv[1], $argv[2])), "\n";' mi-volcado.sql.gz midominio.com
```

Salida real (2026-11-08, PHP 8.3.26; igual en 8.5.7):

```
1. El caso de la clase: sanchezdonate.test -> donatesanchez.test (misma longitud, 18 caracteres)
  [OK] «Reemplazar todo» funciona: el serializado sigue siendo válido  -> a:3:{s:6:"titulo";s:8:"Contacto";s:3:"url";s:36:"https://don…

2. Clonar a un dominio de otra longitud: sanchezdonate.test -> carlos.sanchezdonate.com (24 caracteres)
  [OK] «Reemplazar todo» rompe el serializado (la longitud s:N ya no cuadra)  -> s:36:"https://carlos.sanchezdonate.com/contacto/"
  [OK] el reemplazo que deserializa recalcula la longitud y el dato vuelve  -> s:42:"https://carlos.sanchezdonate.com/contacto/"

3. Qué buscar: la URL entera o solo el dominio
  [OK] buscando «https://dominio» NO se cambia el JSON de Elementor (las barras van escapadas: https:\/\/)  -> [{"settings":{"link":{"url":"https:\/\/sanchezdonate.test\/recursos\/"}}}]
  [OK] buscando solo el dominio, como en clase, SÍ se cambia
  [OK] y el contenido HTML normal se cambia en los dos casos

4. Contar el dominio en un volcado (.sql y .sql.gz)
     {"total":3,"plano":2,"json":1,"serializadas":1,"charsets":{"utf8mb4":1}}
  [OK] cuenta 3 apariciones: 2 URL normales, 1 en JSON escapado, 1 dentro de serializado
  [OK] el .sql.gz da lo mismo que el .sql

5. El wp-config condicional de la clase frente a uno por variable de entorno (php -S, puerto 8138)
     Host producción: {..."DB_NAME":"*******","DB_USER":"****","WP_DEBUG":false,"WP_DEBUG_DISPLAY":false,...}
     Host staging:    {..."DB_NAME":"staging-*****",...,"WP_DEBUG_DISPLAY":true,"WP_DEBUG_LOG":true,...}
     Host inventado:  {"host":"203.0.113.7",...,"DB_NAME":"sanchezdonate","DB_USER":"root","WP_DEBUG":true,"WP_DEBUG_DISPLAY":true,...}
  [OK] Carlos, Host de producción: BD de producción y sin depuración
  [OK] Carlos, Host de staging: muestra errores en pantalla (staging suele ser público)
  [OK] Carlos, Host inventado: rama local (root sin contraseña) y errores en pantalla
  [OK] seguro, mismo Host inventado: producción y sin depuración (el Host no decide)
     CLI (wp-cli, cron): {"host":null,"avisos":["Undefined array key \"HTTP_HOST\""],...,"DB_USER":"root",...}
  [OK] Carlos por línea de comandos: aviso por HTTP_HOST inexistente y BD local
  [OK] seguro con WP_ENVIRONMENT_TYPE=local en el servidor: BD local y depuración

14/14 comprobaciones OK
```

## Qué enseña

1. **El reemplazo de la clase funciona porque los dos dominios miden lo mismo** (18 caracteres). WordPress guarda opciones, widgets y ajustes de plugins **serializados**, con la longitud de cada texto delante (`s:36:"https://…"`). Si el dominio nuevo mide distinto, la longitud ya no cuadra, `unserialize()` falla y WordPress pierde ese dato (lo trata como vacío). Para dominios de distinta longitud: `wp search-replace` o un plugin que deserialice.
2. **Buscar solo el dominio, sin `https://`, es lo correcto.** Elementor y los bloques guardan las URL en JSON con las barras escapadas (`https:\/\/dominio`); buscando la URL entera no se encuentran.
3. **El `wp-config` por `HTTP_HOST` deja decidir al visitante.** La cabecera `Host` la manda quien hace la petición. Si el servidor de producción responde a un Host que no está en la lista (una IP, otro nombre), entra en la rama local: `root` sin contraseña y **errores en pantalla**. Por línea de comandos no hay `HTTP_HOST`: aviso y rama local.
4. **Staging muestra errores en pantalla** con la configuración de la clase. Si staging es público, cualquiera los ve; y `debug.log` va a `wp-content/`, dentro de la carpeta pública.

## Lo que no mide

- No importa nada en MySQL: el root de la MySQL de Laragon tiene contraseña y no la he tocado. El reemplazo se prueba sobre los valores tal como quedan en un volcado.
- Que un Host inventado llegue a la web depende del servidor (host virtual por defecto, CDN delante). El ejercicio mide qué hace la configuración **si** llega.
- `reemplazo_seguro()` es una demostración: para una base real, `wp search-replace` (que además se salta las claves primarias y permite `--dry-run`).
