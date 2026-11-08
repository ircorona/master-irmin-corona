# Redirecciones de un cambio de dominio sin perder las antiguas

Ejercicio de la clase **Migraciones 22 · Teoría de migraciones**. La idea de clase: al pasar del dominio A al B, A redirige entero a B, **pero B tiene que seguir haciendo las redirecciones que ya tenía la web** (si antes `/contadores/` iba a `/cuentas/`, eso se mantiene). Aquí se prueba en Apache qué pasa si se pierden y qué pasa si se mantienen sin aplanar, frente a la versión que va directa al destino final.

| Fichero | Qué es |
|---|---|
| `redirecciones-antiguas.csv` | Las redirecciones que ya tenía la web, con una cadena (`/contables/` → `/contadores/` → `/cuentas/`) |
| `aplanar.py` | Resuelve las cadenas, avisa de bucles y genera `a.htaccess` (dominio viejo) y `b.htaccess` (dominio nuevo) |
| `prueba.py` | Apache temporal con dos hosts (`a.test`, `b.test`), tres montajes, cuenta saltos con `curl -L` |

## Ejecutar

```bash
python aplanar.py redirecciones-antiguas.csv https://dominio-nuevo.com --salida salida
python prueba.py
```

`prueba.py` usa el `httpd.exe` de Laragon con su propio `httpd.conf` en una carpeta temporal; no toca Laragon. Sale con 0 si los 12 casos dan lo esperado y si detecta un bucle de ejemplo.

## Resultado (Apache 2.4.62, Python 3.11.9, 2026-11-08)

```
Bucle /x/ -> /y/ -> /x/ detectado: sí

Montaje              Pedida                Saltos  Código  Termina en            Resultado
ingenuo-sin-legado   a.test/contadores/    1       404     b.test/contadores/    correcto
ingenuo-sin-legado   a.test/contables/     1       404     b.test/contables/     correcto
ingenuo-sin-legado   a.test/otra-pagina/   1       200     b.test/otra-pagina/   correcto
ingenuo-sin-legado   b.test/contadores/    0       404     b.test/contadores/    correcto
ingenuo-con-legado   a.test/contadores/    2       200     b.test/cuentas/       correcto
ingenuo-con-legado   a.test/contables/     3       200     b.test/cuentas/       correcto
ingenuo-con-legado   a.test/otra-pagina/   1       200     b.test/otra-pagina/   correcto
ingenuo-con-legado   b.test/contadores/    1       200     b.test/cuentas/       correcto
aplanado             a.test/contadores/    1       200     b.test/cuentas/       correcto
aplanado             a.test/contables/     1       200     b.test/cuentas/       correcto
aplanado             a.test/otra-pagina/   1       200     b.test/otra-pagina/   correcto
aplanado             b.test/contadores/    1       200     b.test/cuentas/       correcto

Casos correctos: 12 de 12
```

Lo que se ve:

1. **Sin las antiguas en B** (lo que avisa Carlos): las URLs viejas con redirección acaban en **404** en el dominio nuevo.
2. **Con las antiguas pero sin aplanar**: llegan, pero con **2 y 3 saltos**. Google sigue hasta 10, pero recomienda ir directo al destino final.
3. **Aplanado**: todo en 1 salto, desde A y desde B.

## Cómo usarlo

1. Exportar las redirecciones que tenga la web hoy (`.htaccess`, plugin de redirecciones, servidor) a un CSV `origen,destino`.
2. `python aplanar.py fichero.csv https://dominio-nuevo.com`.
3. `a.htaccess` va al dominio viejo; `b.htaccess`, **arriba** del `.htaccess` del dominio nuevo.
4. Comprobar con `curl -s -o /dev/null -L -w "%{num_redirects} %{http_code} %{url_effective}\n" URL` una muestra de URLs antiguas.

Solo trata rutas exactas (con o sin barra final). Redirecciones con expresiones regulares o parámetros hay que revisarlas a mano.
