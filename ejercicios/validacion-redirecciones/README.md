# Validación final de redirecciones en pre

Ejercicio de la clase **Migraciones 06-05 · Validaciones**. La clase valida el documento de redirecciones en una hoja de cálculo (URL final prevista frente a URL final, con «¿Es correcto?» y un gráfico de Sí/No), apuntando el dominio real al servidor de pre con el archivo `hosts`, y da el OK solo al 100 %. Este ejercicio hace lo mismo desde la línea de comandos y enseña lo que la fórmula no ve.

| Fichero | Qué hace |
|---|---|
| `validar.py` | Sigue cada URL salto a salto con `curl --connect-to`, compara con la prevista, revisa `robots.txt` y dice si se puede dar el OK |
| `servidor.py` | Entorno de pre simulado en `127.0.0.1:8134` que responde según la cabecera `Host`, con dos estados y dos órdenes de reglas |
| `prueba.py` | Levanta el servidor en tres escenarios y comprueba que `validar.py` da lo esperado |
| `datos/` | Documento con fallos, incluida la contradicción de la diapositiva (ver abajo) |
| `datos-arreglados/` | El mismo documento corregido |

Solo librería estándar de Python y `curl`. Los datos son inventados, con los nombres de la clase. Van en `http` porque el servidor local no tiene TLS.

## Ejecutar

```bash
python prueba.py
```

Sale con 0 si los tres escenarios dan lo esperado. A mano:

```bash
python servidor.py --estado roto --orden transversal      # en una terminal
python validar.py datos --servidor 127.0.0.1:8134         # en otra
```

Con un pre real, sin tocar `hosts` y sin depender del DNS:

```bash
python validar.py mis-datos --servidor 203.0.113.5 --inseguro
```

`validar.py` sale con 0 solo si el resultado es 100 % y sin avisos. Sin servidor o sin respuesta, cuenta 0 comprobadas y sale con 1.

## Por qué `--connect-to` y no `hosts`

Editar `hosts` afecta a todo el equipo, pide permisos de administrador y, si se olvida quitar la línea, se sigue viendo pre después del lanzamiento. `curl --connect-to ::IP:` manda la petición a esa IP conservando la URL y el `Host`: el servidor no nota la diferencia, pero solo afecta a ese proceso. Para mirar la web en el navegador sí hace falta `hosts`:

```bash
# Git Bash abierto como administrador
echo "203.0.113.5 dominio.tld www.dominio.tld" >> /c/Windows/System32/drivers/etc/hosts
ipconfig //flushdns
```

```powershell
# PowerShell como administrador
Add-Content C:\Windows\System32\drivers\etc\hosts "203.0.113.5 dominio.tld www.dominio.tld"
ipconfig /flushdns
```

Hay que poner **todos** los hosts del documento y borrar la línea al terminar. Este ejercicio no modifica `hosts`.

## Resultado (Python 3.11.9, curl 8.16.0, 2026-11-04)

| Escenario | Hoja | Script | Avisos | robots.txt | ¿OK? |
|---|---|---|---|---|---|
| Roto, regla transversal primero | 6 de 11 (54,5 %) | 4 de 11 (36,4 %) | 3 | 1 origen bloqueado | No |
| Arreglado, regla transversal primero | 11 de 11 (100 %) | 11 de 11 (100 %) | 2 cadenas | Libre | No |
| Arreglado, reglas concretas primero | 11 de 11 (100 %) | 11 de 11 (100 %) | 0 | Libre | **Sí** |

```
Escenarios correctos: 3 de 3
```

## Qué enseña

Lo que la hoja da por bueno y no lo es (escenario roto):

| Caso | Hoja | Realidad |
|---|---|---|
| `/caso` termina en `/Nuevo-Caso`, prevista `/nuevo-caso` | Sí | No: son URLs distintas; la comparación de Sheets no distingue mayúsculas |
| `/roto` termina en `/destino-roto`, tal como está previsto | Sí | No: el destino da 404. La hoja compara texto, no el código |
| `/ruta-temporal` llega a la URL prevista con un 302 | Sí | Llega, pero es temporal: aviso |
| `/olvidada` da 404 y `/vivo` da 200 sin redirigir | No | La fórmula solo dice «No existe redirección» si la celda es un error; el script lo distingue |

Además:

- **La contradicción de la diapositiva.** La fila 1 dice «sin www. a con www.», pero las filas siguientes prevén `https://dominio.tld/lololo`, sin `www`. Con la fila 1 aplicada, esa URL nunca puede ser la final. `datos/` la copia tal cual y sale «No»; `datos-arreglados/` corrige la prevista. Probablemente sea un ejemplo sin más, pero en un documento real es el fallo que hay que cazar.
- **El orden de las reglas.** El documento va de lo más transversal a lo menos, y es buena forma de leerlo. Implementado en ese orden, `dominio.tld/lelele` da dos saltos (a `www` y luego a `lololo`). Las concretas primero van directas. La hoja da 100 % en los dos casos.
- **robots.txt.** Con `Disallow: /` en el origen, Googlebot no pide esas URLs y no ve las redirecciones, aunque funcionen.

## Límites

- `Hoja` imita la comparación de la fórmula; no ejecuta Google Sheets. Que `=` no distingue mayúsculas se comprueba en Sheets con `="A"="a"`.
- Decidir que cualquier aviso bloquea el OK es un criterio de este script; la clase solo pide el 100 %. Una cadena no es un fallo grave, pero se arregla antes de lanzar.
- No hay prueba con TLS: `--inseguro` existe para pre reales y aquí no se ha ejercitado.
- No normaliza URLs (barra final, parámetros): compara texto exacto.
- `robots.txt` se evalúa con `urllib.robotparser`, no con el analizador de Google.
