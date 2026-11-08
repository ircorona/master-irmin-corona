# Clonar el código: el .htaccess y lo que escriben los plugins

Ejercicio de **Migraciones, clase 18 Código**.

En clase se copia el proyecto a una carpeta nueva, se reemplaza el dominio en todos los ficheros con Visual Studio Code (`carlos.sanchezdonate.com` → `donatesanchez.test`, «20000 results in 46 files») y se redirige el dominio viejo al nuevo con una línea de `.htaccess`. Este ejercicio prueba esos `.htaccess` en un **Apache 2.4.62 real** (el de Laragon, con una configuración temporal propia; la de Laragon no se toca).

## Ficheros

| Fichero | Qué es |
|---|---|
| [`variantes/clase-redirectmatch.htaccess`](variantes/clase-redirectmatch.htaccess) | Línea 1 de la clase: `RedirectMatch 301 ^(.*)$ https://migracion.test/$1` |
| [`variantes/redirect-prefijo.htaccess`](variantes/redirect-prefijo.htaccess) | `Redirect 301 / https://migracion.test/` |
| [`variantes/rewrite-con-host.htaccess`](variantes/rewrite-con-host.htaccess) | `RewriteCond %{HTTP_HOST} !^migracion\.test$` + `RewriteRule` |
| [`variantes/staging-if-auth.htaccess`](variantes/staging-if-auth.htaccess) | Bloque `<If "%{HTTP_HOST} == 'test-carlos…'">` con contraseña de la clase |
| [`variantes/auth-ruta-con-dominio.htaccess`](variantes/auth-ruta-con-dominio.htaccess) | `AuthUserFile` con el dominio dentro de la ruta del disco, como en los hostings con carpeta por dominio |
| [`variantes/plugin-php-value.htaccess`](variantes/plugin-php-value.htaccess) | `php_value auto_prepend_file` con ruta absoluta, como escriben algunos plugins de seguridad |
| [`variantes/apache22-order.htaccess`](variantes/apache22-order.htaccess) | `Order Deny,Allow` / `Deny from all` (Apache 2.2) |
| [`prueba.py`](prueba.py) | Arranca Apache una vez por caso (puerto 8139), manda cada `Host` con `curl` y comprueba código y `Location` |

## Cómo probarlo

Git Bash, con Laragon:

```bash
cd ejercicios/clonar-codigo
python prueba.py
```

PowerShell: `cd ejercicios\clonar-codigo; python prueba.py`

Salida real (2026-11-08):

```
Caso                                                              Host                            Código  Location
Clase: RedirectMatch ^(.*)$ desde el dominio viejo                carlos.sanchezdonate.com        301     https://migracion.test//blog/  OK
Clase: el dominio nuevo en la misma carpeta se redirige a sí mismomigracion.test                  301     https://migracion.test//blog/  (BUCLE: redirige a su propio Host)  OK
Redirect 301 / por prefijo desde el viejo                         carlos.sanchezdonate.com        301     https://migracion.test/blog/  OK
Redirect 301 / en la misma carpeta: también bucle                 migracion.test                  301     https://migracion.test/blog/  (BUCLE: redirige a su propio Host)  OK
RewriteCond por Host desde el viejo                               carlos.sanchezdonate.com        301     https://migracion.test/blog/  OK
RewriteCond por Host: el nuevo se sirve                           migracion.test                  200     -  OK
La URL con doble barra también da 200 (duplicada)                 migracion.test                  200     -  OK
Clase: <If> staging sin contraseña                                test-carlos.sanchezdonate.com   401     -  OK
Clase: <If> staging con contraseña                                test-carlos.sanchezdonate.com   200     -  OK
Clase: <If> otro Host no pide contraseña                          migracion.test                  200     -  OK
Ruta con el dominio, original, con contraseña                     migracion.test                  200     -  OK
Tras «Reemplazar todo», sin contraseña: parece que va             migracion.test                  401     -  OK
Tras «Reemplazar todo», al entrar: error                          migracion.test                  500     -  OK
Plugin: php_value sin mod_php                                     migracion.test                  500     -  OK
Apache 2.2 Order/Deny sin mod_access_compat                       migracion.test                  500     -  OK
  error.log: …AH01620: Could not open password file: …/donatesanchez.test/.htpasswd
  error.log: …/web/.htaccess: Invalid command 'php_value', perhaps misspelled or defined by a module not included in th…
  error.log: …/web/.htaccess: Invalid command 'Order', perhaps misspelled or defined by a module not included in the se…

Casos correctos: 15 de 15
```

## Qué enseña

1. **`RedirectMatch 301 ^(.*)$ https://nuevo/$1` duplica la barra.** En Apache la ruta empieza por `/`, así que `$1` ya la lleva: `/blog/` → `https://migracion.test//blog/`. Y esa URL con doble barra da 200 en el destino: una URL duplicada en vez de la canónica. Corrección: `^/(.*)$` o `Redirect 301 / https://nuevo/`.
2. **Si los dos dominios apuntan a la misma carpeta, la regla sin condición hace bucle**: el dominio nuevo se redirige a sí mismo. La clase dice que hay que mantener el dominio viejo y apuntarlo en el hosting al nuevo; si se hace con la misma carpeta, la regla necesita la condición de Host (`rewrite-con-host`).
3. **El `<If>` de staging de la clase funciona**: pide contraseña solo con ese Host.
4. **Reemplazar el dominio en todo el código rompe rutas del disco.** En los hostings con una carpeta por dominio, el dominio está en rutas como `AuthUserFile`. Tras «Reemplazar todo», la página **sigue pidiendo contraseña (401)** y parece que va; el error (500, `Could not open password file`) solo sale **al meter la contraseña**.
5. **Lo que escriben los plugins puede tumbar la web entera**: un `php_value` en un servidor sin `mod_php` (PHP-FPM, LiteSpeed con otra configuración) da 500 en todas las páginas.
6. **Un `.htaccess` viejo en un Apache nuevo**: `Order`/`Deny` necesitan `mod_access_compat`; sin él, 500.

## Lo que no mide

- Apache 2.4.62 de Laragon con un `httpd.conf` mínimo. LiteSpeed (Hostinger) lee `.htaccess` con su propia implementación: los resultados pueden variar; no lo he probado.
- No hay WordPress ni PHP detrás: se miden las respuestas de Apache.
- El límite de 20.000 resultados de VS Code se ha comprobado en el código de VS Code 1.145.0 instalado (`search.maxResults`, por defecto `2e4`), no reproduciendo un reemplazo.
