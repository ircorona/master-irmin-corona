# Análisis del usuario: contraste WCAG y test A/B

Ejercicio de **SXO, clase 04 Análisis del usuario**. Son los dos laboratorios de la explicación «Caminos del deseo» (artefacto: https://claude.ai/artifact/GW4YSU1gGi6WAT2PB7EBQu).

La clase dice que un diseño UX se analiza mirando lo que hacen los usuarios, que **la clave es la accesibilidad** y que el *user research* incluye **A/B testing**. Estos dos scripts miden las dos cosas sin opiniones: un contraste pasa WCAG o no, y un test A/B es significativo o no.

Solo librería estándar de Python 3.

## 1. `contraste.py`: ¿el texto pasa WCAG 2.2?

WCAG pide **4,5:1** para texto normal y **3:1** para texto grande y componentes de interfaz (iconos, bordes de botones).

```bash
python contraste.py
```

```
#777777 sobre #ffffff: 4.48:1  ->  solo texto grande
#767676 sobre #ffffff: 4.54:1  ->  AA ok
#ffffff sobre #f7ca00: 1.57:1  ->  NO pasa
#111111 sobre #f7ca00: 12.05:1  ->  AA ok
```

Con tus colores (texto, fondo):

```bash
python contraste.py "#dfae4f" "#ffffff"
```

```
#dfae4f sobre #ffffff: 2.04:1  ->  NO pasa
```

| Lo que enseña | |
|---|---|
| `#777777` | El gris «suave» de tantas plantillas falla por 0,02. `#767676` es el gris más claro que pasa sobre blanco |
| `#f7ca00` | El amarillo del botón del ejercicio de la clase 03: ilegible con texto blanco, sobrado con texto casi negro |
| Dorados y amarillos | Los colores claros de marca casi nunca sirven para texto sobre blanco. Hace falta una variante oscura para texto |

La fórmula es la de luminancia relativa de WCAG (sRGB linealizado, pesos 0,2126 / 0,7152 / 0,0722) y `(L1 + 0,05) / (L2 + 0,05)`.

## 2. `ab_test.py`: ¿la versión B ha ganado de verdad?

Test z de dos proporciones, bilateral, al 95 %.

```bash
python ab_test.py
```

```
A: 3.00%  B: 3.60%  mejora: +20.0%
z = 2.38  p = 0.0175  -> B gana (95 %)
A: 3.00%  B: 3.60%  mejora: +20.0%
z = 0.75  p = 0.4526  -> no concluyente: sigue el test
```

Con tus datos (`visitas_a conv_a visitas_b conv_b`):

```bash
python ab_test.py 500 20 500 25
```

```
A: 4.00%  B: 5.00%  mejora: +25.0%
z = 0.76  p = 0.4456  -> no concluyente: sigue el test
```

**La misma mejora del 20 %** es real con 10.000 visitas por versión y ruido con 1.000. Con 500 visitas, ni un +25 % significa nada. Una web con poco tráfico no puede decidir por A/B: necesita evaluación heurística (clase 03) y tests con usuarios.

Y si se hace un A/B, las reglas de Google para no romper el SEO: sin *cloaking*, `rel="canonical"` de la variante a la original, redirección **302** y quitarlo al acabar.

## Probado

2026-10-17, Python 3 en Windows: las cuatro ejecuciones de arriba, salida copiada tal cual, código de salida 0.
