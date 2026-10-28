# Análisis de datos de GA4 con la plantilla de la clase

Ejercicio de la clase **Analítica 02 · Análisis de datos**. La plantilla de clase pide definir población, muestra, variables, sesgos y limpieza para «usuarios de Colombia en los últimos 12 meses». Aquí se aplica a una exportación de GA4 **ficticia** (mes × país × dispositivo) que lleva a propósito los fallos típicos.

| Fichero | Qué hace |
|---|---|
| `generar_datos.py` | Crea `datos-ejemplo.csv` (73 filas inventadas, semilla fija) con un pico de bots, una fila duplicada, una etiqueta `"Tablet "`, un valor faltante, una fila incoherente y filas de México |
| `analizar.py` | Limpia en 6 pasos con recuento de filas en cada uno y comprobación final esperado/real, y calcula la moda del dispositivo y la tasa de leads por dispositivo con su intervalo de confianza del 95 %, antes y después de limpiar |

## Ejecutar

```bash
python generar_datos.py
python analizar.py                 # o: python analizar.py mi-exportacion.csv
```

Con una exportación real, las columnas deben llamarse `mes, pais, dispositivo, usuarios_totales, sesiones, sesiones_con_interaccion, leads` (y cambiar `PAIS` si no es Colombia).

## Resultado (Python 3.11, 2026-10-27)

```
SIN LIMPIAR (solo filtro Colombia)
  moda del dispositivo: mobile (64.1% de las sesiones)
  mobile      2,433,269   8874  0.365%  [0.357%, 0.372%]
  desktop     1,266,621   8526  0.673%  [0.659%, 0.687%]
  tablet         87,258    442  0.507%  [0.459%, 0.554%]
  Tablet          8,741     42  0.480%  [0.336%, 0.625%]

LIMPIEZA
  1 etiquetas normalizadas      73 ->  73 filas  (1 corregida/s)
  2 filtro país                 73 ->  37 filas  (fuera 36 de otros países)
  3 duplicados                  37 ->  36 filas  (fuera 1)
  4 faltantes imputados         36 ->  36 filas  (1 imputado/s)
  5 coherencia                  36 ->  35 filas  (fuera 1)
  6 atípicos (bots)             35 ->  34 filas  (fuera 1)
  recuento final: esperado 34, real 34 -> OK

LIMPIO
  moda del dispositivo: mobile (71.8% de las sesiones)
  mobile      2,233,152   8935  0.400%  [0.392%, 0.408%]
  desktop       780,182   7120  0.913%  [0.892%, 0.934%]
  tablet         95,999    484  0.504%  [0.459%, 0.549%]
```

## Qué enseña

- **El sesgo pesa mucho más que el error de muestreo.** El mes de bots baja la tasa de escritorio de 0,913 % a 0,673 %. El margen del intervalo de confianza es de ±0,02 puntos: el error de los bots (0,24 puntos) es unas 11 veces mayor y el intervalo no lo avisa, porque solo mide el azar.
- **Una etiqueta mal escrita parte una categoría** (`tablet` y `Tablet `), y con categorías partidas la moda puede salir mal.
- **Los usuarios mensuales no se suman.** El script lo avisa al final: para los usuarios de 12 meses hay que pedir a GA4 el rango completo.
- **Quitar una fila también quita lo bueno.** El mes de bots tenía leads reales; en un análisis de verdad conviene imputar ese mes (por ejemplo, con la tasa de los meses vecinos) en vez de borrarlo, y siempre dejarlo anotado.
- El intervalo usa la aproximación normal de una proporción (`p ± 1,96·√(p(1−p)/n)`, `σp = √(p(1−p)/n)` en el capítulo 11 del manual, que redondea el 1,96 a «±2 veces el error estándar») y supone sesiones independientes, lo que no es del todo cierto (un mismo usuario hace varias).
