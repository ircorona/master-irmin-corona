# Redirecciones y códigos forzados en nginx

Ejercicios de la clase 14 de Servidores (Avanzado):

1. Genera distintos tipos de redirecciones con éxito.
2. Genera un código de respuesta forzado en una URL.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/ejercicio.conf` | El ejercicio: 301, 302, directorio a directorio, a otro dominio, quitar `.php`, 410 y 503 |
| `conf/clase-*.conf` | Cada bloque de código de clase en su propio `server`, tal cual |
| `conf/comun/php.inc` | La `location` de PHP común a todos |
| `sitio/` | Una web de prueba con PHP: portada, `contacto.php`, un formulario `enviar.php`, un blog |
| `prueba.py` | Arranca el nginx y el `php-cgi.exe` de Laragon con configuración propia y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py            # 36 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # deja nginx arrancado
```

Puertos 8051 a 8056 y 9151. No toca el nginx ni el Apache de Laragon. En los `Location` de la prueba nginx añade el puerto (`mi-web.test:8056`) porque no es el 80; en producción no aparece.

## El ejercicio

| URL | Respuesta | Tipo |
|---|---|---|
| `/curiosidades/` | 301 → `/datos-curiosos/` | Página a página, relativa |
| `/curiosidades/un-dato/?utm=x` | 301 → `/datos-curiosos/un-dato/?utm=x` | Directorio a directorio, con estructura y parámetros |
| `/rebajas/` | 302 → `/datos-curiosos/` | Temporal |
| `/viejo-blog/post-1/?ref=a` | 301 → `https://otro-dominio.test/blog/post-1/?ref=a` | A otro dominio |
| `/contacto.php?id=7` | 301 → `/contacto?id=7` | Quitar la extensión, conservando parámetros |
| `/index.php`, `/blog/index.php` | 301 → `/`, `/blog/` | `index.php` a su carpeta |
| `POST /enviar.php` | **200**, sin redirigir | Los formularios siguen funcionando |
| `/contacto-antiguo/` | **410** | Código forzado: retirado |
| `/mantenimiento/` | **503** + `Retry-After: 3600` | Código forzado: temporal |

## Lo que enseña el código de clase

| Bloque | Medido |
|---|---|
| Las dos líneas de `rewrite` | La primera manda **todo** `/curiosidades/` a la portada y corta: la segunda nunca se ejecuta |
| `location` + `return 301` | Funciona. Pierde los parámetros (`return` no los añade; `rewrite` sí) |
| Variante «escalable» con `$scheme://$host` | Redirige a **`//datos-curiosos/`**, con doble barra |
| Las dos variantes en el mismo `server` | `nginx -t`: **«duplicate location»**, nginx no arranca |
| `return 503` | Sin `Retry-After` |
| «Si todo va al index» | **La portada da 404**; `/contacto.php` sigue dando 200 (duplicado) |
| «Convertir de .php a no .php» | Quita la extensión, pero pierde los parámetros, manda `index.php` a `/index` y **redirige los POST de los formularios** |
