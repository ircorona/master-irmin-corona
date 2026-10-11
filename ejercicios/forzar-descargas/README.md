# Forzar descargas con `Content-Disposition`

Ejercicio de la clase 12 (opcional) de Servidores (Avanzado). La clase no trae un enunciado, así que el ejercicio es aplicar el código de clase y medir qué hace con cada fichero.

La cabecera `Content-Disposition: attachment` le dice al navegador que **descargue** el fichero en lugar de mostrarlo. `inline`, el valor por defecto, lo muestra.

## Qué hay

| Ruta | Qué es |
|---|---|
| `sitio/.htaccess` | El ejercicio: los `.txt` se descargan salvo los públicos, y un PDF con nombre propio |
| `demos/clase/.htaccess` | El código de clase, tal cual |
| `demos/orden-invertido/.htaccess` | Los mismos dos bloques, al revés |
| `prueba.py` | Arranca Apache dos veces (con y sin `mod_headers`) y comprueba la cabecera de cada fichero |

## Cómo se ejecuta

```bash
python prueba.py            # 16 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y abrir http://127.0.0.1:8071/notas.txt en el navegador
```

Usa el Apache 2.4.62 de Laragon con una configuración propia en una carpeta temporal. Puertos 8071 y 8072.

## El ejercicio

```apache
<IfModule mod_headers.c>
<FilesMatch "(?i)^(?!(robots|llms|ads|sitemap)\.txt$).+\.txt$">
    Header set Content-Disposition "attachment"
</FilesMatch>

<Files "catalogo-2026.pdf">
    Header set Content-Disposition "attachment; filename=\"Catalogo 2026.pdf\""
</Files>
</IfModule>
```

| Fichero | `Content-Disposition` |
|---|---|
| `notas.txt`, `LEEME.TXT` | `attachment`: se descarga |
| `robots.txt`, `llms.txt`, `ads.txt`, `sitemap.txt` | Ninguna: se ve en el navegador |
| `catalogo-2026.pdf` | `attachment; filename="Catalogo 2026.pdf"` |

La excepción va **dentro** de la regex (`(?!...)` es «que no sea»), en un solo bloque. Así no depende del orden de dos bloques.

## Lo que enseñan las demos

| Demo | Medido |
|---|---|
| Código de clase | `robots.txt` se ve, pero **`sitemap.txt`, `llms.txt` y `ads.txt` se descargan**: son `.txt` públicos que también se abren en el navegador |
| Orden invertido | `robots.txt` **se descarga**: con dos `Header set` sobre el mismo fichero gana el último bloque |
| Sin `mod_headers` | El código de clase no lleva `<IfModule>`: **500 en toda la carpeta**. El ejercicio se sirve igual, sin la cabecera |
| `LEEME.TXT` | En Laragon el código de clase también lo descarga: Apache en Windows no distingue mayúsculas en `<FilesMatch>`. En un servidor Linux, `\.txt$` no casa con `.TXT`; el `(?i)` del ejercicio lo cubre en los dos |
