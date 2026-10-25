# Componentes y peso de las librerías

Ejercicio de **Frameworks y Tecnologías, clase 08 Librerías populares y su impacto**.

Dos ideas de la clase, medidas:

1. **Componentes aislados.** El dashboard de clase (estadísticas, últimos posts, últimos comentarios): si el componente de comentarios falla, ¿cae la página entera o solo ese cuadro?
2. **El impacto de cada librería**: cuánto pesa lo que se descarga el usuario.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`dashboard.html`](dashboard.html) | Tres componentes como funciones independientes. El de comentarios revienta (la API devuelve `null`). Con `?aislado=1` cada uno se monta dentro de una «frontera de error», como el *Error Boundary* de React |
| [`prueba.php`](prueba.php) | Carga el dashboard con Chrome headless sin aislar y aislado, y dice qué componentes llegan al DOM |
| [`peso.py`](peso.py) | Descarga de jsDelivr el fichero de producción de cada librería y lo mide sin comprimir y con gzip |

## Cómo probarlo

Git Bash, desde la raíz del repo (Chrome instalado):

```bash
cd ejercicios/componentes-y-librerias
php=/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe
"$php" prueba.php
python peso.py
```

Salida real (2026-10-25):

```
Sin aislar   Aislado    Componente
-            pintado    Estadísticas
-            pintado    Últimos posts
-            -          Comentarios
-            pintado    Aviso de error

OK: sin aislar se pierde todo; aislado solo cae el componente roto
```

```
Librería                    Sin comprimir      gzip
jQuery 3.7.1                      85.5 KB   29.6 KB
React 19.2 + React DOM           540.3 KB   96.8 KB
Vue 3.5                          155.9 KB   57.2 KB
Alpine 3.15                       43.7 KB   15.8 KB
Bootstrap 5.3 (JS + CSS)         305.3 KB   53.3 KB
```

## Qué enseña

- **Sin aislar, un componente roto se lleva la página entera.** Es lo que documenta React: *«if your application throws an error during rendering, React will remove its UI from the screen»*. Para Google, una página que renderiza vacía.
- **Aislado, solo cae su hueco.** Estadísticas y posts se pintan; comentarios muestra un aviso.
- **Las librerías no pesan lo mismo.** React + React DOM ocupa 6 veces lo que Alpine, y antes de escribir una línea de la web. Es solo el núcleo: lo que se construye encima suma.
- **Medido de los ficheros, no de una web real.** Una web con React empaqueta solo lo que usa y lo sirve con Brotli; los números sirven para comparar librerías entre sí.
