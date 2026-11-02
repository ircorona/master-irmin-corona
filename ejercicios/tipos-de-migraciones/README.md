# Tipos de migraciones: seguir las redirecciones salto a salto

Ejercicio de la clase **Migraciones 03 · Tipos de migraciones**. Las diapositivas enseñan tres migraciones reales con gráficas de visibilidad (omicrono, unCOMO, Bricomart). Aquí se comprueba cómo redirigen hoy esas URLs viejas y se avisa de los tres fallos típicos de una migración.

| Fichero | Qué hace |
|---|---|
| `saltos.py` | Pide cada URL con `HEAD` sin seguir redirecciones, apunta código y `Location` de cada salto (hasta 10) y avisa si hay redirección temporal, cadena o pérdida de la ruta |

Solo librería estándar de Python.

## Ejecutar

```bash
python saltos.py                                   # los casos de la clase
python saltos.py https://auramip.com/es/ http://auramip.com/
```

Sale con código 0 si todas las URLs responden y 1 si alguna falla (DNS, timeout).

## Resultado (Python 3.11, 2026-11-02)

```
== http://omicrono.com/
   301  http://omicrono.com/
        -> https://www.elespanol.com/omicrono/
   200  https://www.elespanol.com/omicrono/
   OK: un salto, permanente, ruta conservada
== https://omicrono.elespanol.com/
   301  https://omicrono.elespanol.com/
        -> https://www.elespanol.com/omicrono/
   200  https://www.elespanol.com/omicrono/
   OK: un salto, permanente, ruta conservada
== https://www.mundodeportivo.com/uncomo/salud/
   301  https://www.mundodeportivo.com/uncomo/salud/
        -> https://www.uncomo.com/salud/
   200  https://www.uncomo.com/salud/
   OK: un salto, permanente, ruta conservada
== https://www.bricomart.es/herramientas.html
   302  https://www.bricomart.es/herramientas.html
        -> https://www.obramat.es/herramientas.html
   301  https://www.obramat.es/herramientas.html
        -> https://www.obramat.es/herramientas/
   503  https://www.obramat.es/herramientas/
   ! redirección temporal en un cambio permanente (usar 301/308)
   ! cadena de 2 redirecciones (dejar una sola)

4/4 URLs revisadas
```

## Qué enseña

- **omicrono** pasó por dos migraciones (`omicrono.com` → `omicrono.elespanol.com` → `www.elespanol.com/omicrono/`) y aun así no hay cadena: los dos dominios viejos saltan **directamente** al destino final con un 301. Es lo correcto: al hacer la segunda migración se actualizaron también las redirecciones de la primera.
- **unCOMO** hizo el camino de vuelta: `www.mundodeportivo.com/uncomo/` redirige hoy con 301 a `www.uncomo.com`, conservando la ruta. La unión de dominios de 2021 se ha deshecho (fecha no encontrada).
- **Bricomart** pasó a llamarse Obramat (noviembre de 2022, según la prensa del sector) y `bricomart.es` redirige con **302**, temporal, conservando la ruta. Tres años después de un cambio de marca definitivo debería ser un 301. Además, el destino añade un segundo salto a la versión con barra final.

## Límites

- `HEAD` y un *user-agent* que no es un navegador: obramat.es responde 503 (antes, con `curl`, 403) al final de la cadena, probablemente su protección contra bots. Los saltos intermedios sí son fiables; el 503 final no dice que la web esté caída.
- Una redirección por JavaScript o `<meta refresh>` no aparece: el script solo ve cabeceras HTTP.
- Comprueba URLs sueltas. Para una migración real se le pasa la lista entera del mapa de redirecciones (o se usa el modo lista de Screaming Frog).
