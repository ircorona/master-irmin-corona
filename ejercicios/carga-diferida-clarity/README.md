# Cargar Clarity sin sobrecargar la web: las cuatro versiones de clase, medidas

Ejercicio de la clase **Analítica 04 · Carga excesiva**. La clase da cuatro formas de insertar Microsoft Clarity, cada una con un truco más: el script tal cual, esperar 5 segundos, esperar solo en la primera visita («token») y saltarse los bots por user-agent. Aquí se prueban las cuatro en Chrome headless para ver **quién queda medido y quién no**, más una quinta propuesta.

| Fichero | Qué es |
|---|---|
| `versiones/v1-directo.html` | Código de clase: el script de Clarity tal cual |
| `versiones/v2-esperar-5s.html` | Código de clase: `setTimeout` de 5 s |
| `versiones/v3-esperar-token.html` | Código de clase: 5 s solo si no hay bandera en `localStorage` |
| `versiones/v4-esperar-token-ua.html` | Código de clase: lo anterior + no cargar si el user-agent contiene `Google` o `Yandex` |
| `versiones/v5-mejorado.html` | Propuesta: cola de Clarity al principio y el script al terminar la carga (en reposo) o a la primera interacción |
| `prueba.py` | Servidor local + Chrome headless; 15 comprobaciones |

Los HTML de clase están **sin tocar**. `prueba.py` solo cambia al servirlos `https://www.clarity.ms/tag/` por un Clarity falso local que avisa de cuándo se cargó. Chrome corre con tiempo virtual (`--virtual-time-budget`), que hace dos cosas: los 5 s de espera no tardan 5 s reales, y el presupuesto simula **cuánto tiempo se queda el visitante** en la página.

## Ejecutar

```bash
python prueba.py                       # Chrome en la ruta por defecto de Windows
python prueba.py "/ruta/a/chrome"
```

Usa el puerto 8131. Sale con 0 si las 15 comprobaciones pasan.

## Resultado (Chrome estable, Python 3.11, 2026-10-27; 3 ejecuciones seguidas, 15/15 en las tres)

```
OK    v1 directo, Chrome             Clarity cargó a los 17 ms
OK    v1 directo, visita de 2 s      Clarity cargó a los 2859 ms
OK    v2 esperar 5 s, Chrome 8 s     Clarity cargó a los 5019 ms
OK    v2 esperar 5 s, visita de 3 s  Clarity NO cargó
OK    v3 token, 1.ª visita 3 s       Clarity NO cargó
OK    v3 token, 2.ª visita 3 s       Clarity NO cargó
OK    v3 token, 3.ª visita 8 s       Clarity cargó a los 5019 ms
OK    v3 token, 4.ª visita 3 s       Clarity cargó a los 25 ms
OK    v4 UA, Googlebot               Clarity NO cargó
OK    v4 UA, Bingbot                 Clarity cargó a los 6411 ms
OK    v4 UA, Lighthouse (PSI)        Clarity cargó a los 6144 ms
OK    v4 UA, Chrome 1.ª visita 8 s   Clarity cargó a los 5158 ms
OK    v5 mejorado, Chrome            Clarity cargó a los 507 ms
OK    v5 mejorado, visita de 2 s     Clarity cargó a los 258 ms
OK    v5 mejorado, Googlebot         Clarity cargó a los 25 ms

15/15 comprobaciones OK
```

Los milisegundos **varían entre ejecuciones** (tiempo virtual, arranque en frío de Chrome, el momento de reposo del navegador): en otras pasadas v2 cargó entre 5128 y 7334 ms y v5 entre 25 y 1826 ms. Lo que no varía es **si carga o no**, que es lo que comprueban los casos.

## Qué enseña

- **v2 pierde a quien se va antes de 5 s.** Una visita de 3 s no queda registrada. Son justo los rebotes: el mapa de calor y las grabaciones solo enseñan a los usuarios que se quedaron, el sesgo de omisión de la clase 02. Y `setTimeout(…, 5000)` es «como mínimo 5 s»: aquí llegó a 7,3.
- **v3 tiene un fallo: la bandera se guarda después de cargar.** Si alguien entra dos veces 3 s, ninguna de las dos se mide (casos 1.ª y 2.ª visita). Solo a partir de una visita larga la bandera existe y las siguientes cargan al momento. Resultado: Clarity sobrerrepresenta a los usuarios recurrentes y comprometidos. Además no es un «token»: es una bandera en `localStorage`.
- **v4 no hace lo que dice su comentario.** El comentario habla de «Google/Bing bots», pero la expresión es `/Google|Yandex/`: Bingbot **sí** carga Clarity. Y Lighthouse (lo que corre PageSpeed Insights) tampoco contiene «Google» en su user-agent, así que también lo carga: el filtro no cambia la nota de laboratorio.
- **v5 carga pronto y no pierde visitas cortas**, sin espera fija, sin `localStorage` y sin mirar el user-agent. El script pesa lo mismo; solo cambia el momento, que queda después de que la página haya pintado y quedado libre.
- Lo que este ejercicio **no** mide: el coste real del script de Clarity en el hilo principal (necesita un ID real) ni el consentimiento, que en el EEE, Reino Unido y Suiza Clarity exige desde el 31-10-2025.
