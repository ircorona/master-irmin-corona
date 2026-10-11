# Evitar el hotlinking (y el enlace insertado de Google Imágenes)

Ejercicio opcional de la clase 11 de Servidores (Avanzado): **prueba a prevenir el hotlinking para ver sus efectos**.

*Hotlinking* es que otra web muestre tus imágenes cargándolas desde tu servidor: tú pagas el ancho de banda y ella se queda la visita. Google Imágenes hace algo parecido al enseñar la imagen a tamaño completo en su vista previa. El servidor distingue los casos por la cabecera **`Referer`**, que dice desde qué página se pidió la imagen.

## Qué hay

| Ruta | Qué es |
|---|---|
| `sitio/.htaccess` | El ejercicio: 204 a Google Imágenes y 403 a las demás webs |
| `sitio/index.html` | Página propia con la imagen: se ve |
| `demos/otra-web.html` | Página de "otra web" que inserta la imagen: sale rota |
| `demos/clase/.htaccess` | El código de clase, tal cual |
| `demos/articulo-hotlinking/img/.htaccess` | El «Prevenir Hotlinking» del artículo de Carlos, tal cual |
| `prueba.py` | Arranca un Apache propio y prueba cada caso cambiando el `Referer` |

## Cómo se ejecuta

```bash
python prueba.py            # 24 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y en el navegador:
                            #   http://127.0.0.1:8081/            -> la imagen se ve
                            #   http://127.0.0.1:8082/otra-web.html -> la imagen sale rota (403)
```

Usa el Apache 2.4.62 de Laragon con una configuración propia en una carpeta temporal. Puertos 8081 y 8082.

## El ejercicio

```apache
<IfModule mod_rewrite.c>
RewriteEngine On

# 1. Google Imágenes: 204 sin contenido (opt-out documentado por Google)
RewriteCond %{HTTP_REFERER} ^https?://([^/]+\.)?google\.(com|[a-z]{2,3}|com?\.[a-z]{2})(/|:|$) [NC]
RewriteRule \.(jpe?g|png|gif|webp|avif)$ - [R=204,NC]

# 2. El resto de webs: 403, salvo Referer vacío, la propia web y otros buscadores
RewriteCond %{HTTP_REFERER} !^$
RewriteCond %{HTTP_REFERER}@@%{HTTP_HOST} !^https?://([^/]+)(/.*)?@@\1$ [NC]
RewriteCond %{HTTP_REFERER} !^https?://([^/]+\.)?(bing\.com|duckduckgo\.com|yandex\.[a-z]+)(/|$) [NC]
RewriteRule \.(jpe?g|png|gif|webp|avif)$ - [F,NC]
</IfModule>
```

| `Referer` | Resultado |
|---|---|
| `https://www.google.com/`, `.es`, `.co.uk`, `images.google.com` | **204**, 0 bytes: Google enseña su miniatura |
| Ninguno (visita directa, Googlebot-Image) | 200, la imagen entera |
| La propia web, por `http` o `https` | 200 |
| `https://otra-web.com/` | **403** |
| `https://mi-web.test.otra-web.com/` | 403: empezar igual no basta |
| `https://google.otraweb.com/` | 403: no es Google |
| Bing, DuckDuckGo | 200 |

El truco de la línea `%{HTTP_REFERER}@@%{HTTP_HOST}`: junta las dos cabeceras y comprueba con `\1` que el dominio del `Referer` es el mismo que el `Host` de la petición. Así el dominio no se escribe a mano, y la regla sigue funcionando si la web cambia de dominio.

## Lo que enseñan las demos

| Demo | Medido |
|---|---|
| Código de clase | Funciona con `www.google.*`, pero `images.google.com` **se escapa**: `(www\.)?` solo cubre `www`. Las demás webs no se tocan (no es una protección contra el hotlinking general) |
| Artículo de Carlos, «Prevenir Hotlinking» | Sin `Referer` da **204**: también a Googlebot-Image, que no envía `Referer`, así que **Google deja de ver las imágenes**. Además el dominio está escrito a mano (`carlos.sanchezdonate.com`): desde su dominio actual, `sanchezdonate.net`, sus propias imágenes darían 204. Y el punto sin escapar deja pasar a `carlosXsanchezdonate.com` |
| `ErrorDocument 204` con HTML | Apache no envía ningún cuerpo con un 204: la línea no hace nada |
