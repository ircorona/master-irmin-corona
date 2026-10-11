# Bloqueo por user-agent en nginx

Ejercicios de la clase 17 (NGINX user agent) de Servidores (Avanzado):

1. Prohíbe el acceso al user-agent de Yandex.
2. Opcional: prohíbe un user-agent para una URL específica.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | El código de clase, tal cual |
| `conf/ejercicio.conf` | Yandex fuera de la web (salvo el `robots.txt`) y Screaming Frog fuera de `/sobre-mi/` |
| `conf/coco-location.conf` | El opcional hecho con un `if` dentro de la `location`, para ver por dónde se cuela |
| `prueba.py` | Arranca el nginx 1.27.3 de Laragon y prueba cada caso cambiando el user-agent |

## Cómo se ejecuta

```bash
python prueba.py            # 28 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y probar: curl -A "YandexBot/3.0" http://127.0.0.1:8012/
```

Puertos 8011, 8012 y 8013. No toca el nginx ni el Apache de Laragon.

## El ejercicio

```nginx
map "$http_user_agent|$uri" $bloquear_ua {
    default 0;
    "~*yandex.*\|/robots\.txt$" 0;      # el robots.txt sí se deja leer
    "~*yandex" 1;
}

map "$http_user_agent|$uri" $bloquear_ua_url {
    default 0;
    "~*screaming frog seo spider.*\|/sobre-mi(/|$)" 1;
}

server {
    # ...
    if ($bloquear_ua)     { return 403; }
    if ($bloquear_ua_url) { return 403; }
}
```

Los `map` van en `http {}`, fuera del `server`. Juntan el user-agent y la URL en un texto (`YandexBot/3.0|/blog/`) y una regex decide. Así se hace un «Y» que el `if` de nginx no permite.

| Prueba | Resultado |
|---|---|
| YandexBot, YandexImages, YandexMobileBot | 403 en toda la web |
| Yandex pidiendo `/robots.txt` | 200: lee su `Disallow: /` |
| YaBrowser (el navegador de Yandex) | 200: es una persona |
| Screaming Frog en `/sobre-mi/`, `/sobre-mi` y `/sobre-mi/index.html` | 403 |
| Screaming Frog en `/sobre-mi-equipo/` y `/blog/` | 200 |
| Googlebot y un navegador | 200 |

## Lo que enseñan las demos

| Demo | Medido |
|---|---|
| Código de clase | Bloquea lo que dice, también en mayúsculas (`~*`). Pero `SemrushBot` (el rastreador principal de Semrush) pasa: la lista solo tiene `-BA` y `-SI`. `Screaming.*Frog` casa con «Screaming at the Frog». Y el `robots.txt` queda bloqueado también |
| La regex con espacios sin comillas | `nginx -t`: «invalid condition», nginx no arranca |
| `if` dentro de `location ~ ^/sobre-mi/?$` | `/sobre-mi/` da 403, pero **`/sobre-mi/index.html` se cuela** con 200 |
