# Cabeceras HTTP personalizadas en nginx

Ejercicio de la clase 18 (NGINX HTTP headers) de Servidores (Avanzado): genera un header personalizado (X-Robots, canonical, hreflang o un mensajito), en WordPress o en código normal.

Hecho sobre WordPress: nginx con `try_files` a `index.php` y un WordPress mínimo detrás (php-cgi de Laragon) que, como el de verdad, manda su propia cabecera `Link`.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | El código de clase, tal cual |
| `conf/ejercicio.conf` | Tres cabeceras con `map`, sin `if` |
| `sitio/index.php` | El WordPress mínimo: 200 en sus páginas, 404 en el resto |
| `prueba.py` | Arranca nginx 1.27.3 y php-cgi de Laragon y mira las cabeceras de cada respuesta |

## Cómo se ejecuta

```bash
python prueba.py            # 22 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y probar: curl -I http://127.0.0.1:8022/servicios-seo/
```

Puertos 8021, 8022 y 9121. No toca el nginx ni el Apache de Laragon.

## El ejercicio

```nginx
map $request_uri $x_robots {
    default "";
    "~^/servicios-seo/?(\?|$)" "noindex";
    "~^/wp-content/themes/sanchezdonate/images/sobre-mi/sobre-carlos\.png(\?|$)" "noindex";
}

map $request_uri $link_canonical {
    default "";
    "~^/descargas/guia-seo\.pdf(\?|$)" '<https://mi-web.test/guia-seo/>; rel="canonical"';
}

server {
    add_header X-Mensaje "SEO revisado por Irmin Corona";
    add_header X-Robots-Tag $x_robots;
    add_header Link $link_canonical;
    # ... location / y location ~ \.php$ sin add_header propias
}
```

| Prueba | Resultado |
|---|---|
| Toda la web | `X-Mensaje` |
| `/servicios-seo/`, sin barra y con `?utm_source=` | `X-Robots-Tag: noindex` y el mensaje |
| `/servicios-seo-locales/` | Sin noindex |
| La foto de sobre-mi (también con `?v=2`) | `noindex` y el mensaje |
| El PDF | `Link: <https://mi-web.test/guia-seo/>; rel="canonical"` |
| El resto | Ni `X-Robots-Tag` ni `Link` vacías: una `add_header` con valor vacío no se envía |

## Lo que enseña el código de clase

| Medido | Por qué |
|---|---|
| `/servicios-seo/` y la foto llevan `noindex`, pero **pierden `X-Asdrubal`** | En nginx las `add_header` de un bloque sustituyen a las del superior. El `if` y la `location` de la foto tienen las suyas |
| **`/servicios-seo/?utm_source=news` sale sin `noindex`** | `$request_uri` incluye los parámetros y `^/servicios-seo/?$` no los admite. Arreglo: `(\?|$)` |
| WordPress sigue respondiendo dentro del `if` | El `if` hereda `fastcgi_pass` de su `location` |
| Un 404 de WordPress sale sin `X-Asdrubal` | Sin `always`, `add_header` solo actúa en 200, 201, 204, 206, 301, 302, 303, 304, 307 y 308 |
| Una `add_header Link` en una página de WordPress | Salen **dos** cabeceras `Link`: la de WordPress y la de nginx. No se pisan |
