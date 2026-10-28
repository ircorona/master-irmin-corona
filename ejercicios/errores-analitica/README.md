# Errores en la analítica: muestras pequeñas y UTM

Ejercicio de la clase **Analítica 03 · Errores**. Dos de los nueve errores de la clase llevan una herramienta enlazada, y aquí se reproducen con la librería estándar de Python.

| Fichero | Error de la clase | Qué hace |
|---|---|---|
| `muestra.py` | Extrapolación de datos de muestras pequeñas | La fórmula de las calculadoras de SurveyMonkey, Raosoft y Qualtrics (tamaño de muestra con corrección por población finita) y el margen de error de una tasa medida |
| `utm.py` | Desconocimiento del *customer journey* (UTM para ver procedencia) | Construye URLs como el Campaign URL Builder de Google y revisa las reglas de la ayuda de GA4: los tres parámetros obligatorios, minúsculas, sin espacios y sin UTM en enlaces internos |

## Ejecutar

```bash
python muestra.py                       # comprueba los ejemplos de SurveyMonkey + casos SEO
python muestra.py tamano 2579496 95 5   # -> 385
python muestra.py margen 3 40           # -> 7.50% ± 8.16% (95 %)
python utm.py                           # 7 comprobaciones
python utm.py crear "https://auramip.com/" Google cpc "Marcas 2026"
python utm.py revisar "https://ejemplo.com/?utm_source=Meta&utm_medium=social"
```

Los dos salen con código 0 si todo cuadra y 1 si algo falla, así que sirven en un hook o en CI.

## Resultado (Python 3.11, 2026-10-27)

```
Ejemplos de SurveyMonkey
  N=500,000  95%  ±5%  ->  384  (esperado 384)  OK
  N=    300  80%  ±10%  ->   37  (esperado 37)  OK

La población casi no importa a partir de unos miles (95 %, ±5 %)
  N=      100 -> n=  80
  N=    1,000 -> n= 278
  N=   10,000 -> n= 370
  N=  100,000 -> n= 383
  N=2,579,496 -> n= 385

Extrapolar de muestras pequeñas: la misma tasa del 7,5 % con distinto volumen
      3 leads /     40 sesiones = 7.5% ± 8.2%  -> entre 0.0% y 15.7%
     30 leads /    400 sesiones = 7.5% ± 2.6%  -> entre 4.9% y 10.1%
    300 leads /  4,000 sesiones = 7.5% ± 0.8%  -> entre 6.7% y 8.3%
   3000 leads / 40,000 sesiones = 7.5% ± 0.3%  -> entre 7.2% y 7.8%

2/2 comprobaciones OK
```

```
OK    ...?utm_source=Newsletter&utm_medium=email&utm_campaign=octubre-2026
        - utm_source='Newsletter' tiene mayúsculas -> GA4 lo separa de 'newsletter'
OK    ...?utm_source=linkedin&utm_campaign=octubre-2026
        - falta utm_medium -> saldrá '(not set)'
OK    https://climbthesearches.com/contacto/?utm_source=home&utm_medium=banner&utm_campaign=cta  [enlace interno]
        - UTM en un enlace interno -> sobrescribe la procedencia real de la visita
OK    crear() normaliza: https://climbthesearches.com/blog/?page=2&utm_source=linkedin&utm_medium=social&utm_campaign=black-friday-2026

7/7 comprobaciones OK
```

(«OK» quiere decir que el revisor encontró exactamente los problemas esperados en cada URL.)

## Qué enseña

- **A partir de unos miles, el tamaño de la población casi no cuenta.** Para ±5 % al 95 % hacen falta 370 respuestas con 10.000 personas y 385 con 2,5 millones. Lo que manda es el margen que se acepta.
- **Una tasa con pocos casos no dice casi nada.** 3 leads de 40 sesiones es un 7,5 % que puede ser cualquier cosa entre 0 y 15,7 %. Con 40.000 sesiones, el mismo 7,5 % se mueve ±0,3.
- **Con muestras tan pequeñas la fórmula se queda corta.** El límite inferior se recorta en 0 porque la aproximación normal falla con pocos éxitos; el intervalo de Wilson es mejor en ese caso. Para decidir con 40 sesiones lo honesto es esperar a tener más datos.
- **`crear()` deja intactos los parámetros que no son UTM** (`page=2`) y normaliza a minúsculas con guiones: `Black Friday 2026` → `black-friday-2026`.
- `DOMINIO_PROPIO` en `utm.py` es el dominio que cuenta como interno; se cambia para otra web.
