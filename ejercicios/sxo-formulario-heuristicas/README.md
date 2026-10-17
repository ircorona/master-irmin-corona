# Formulario «Crear cuenta» con las heurísticas de Nielsen

Ejercicio de **SXO, clase 03 Principios heurísticos**.

El punto de partida es la diapositiva de la heurística 9: el «Crear cuenta» de Amazon con `carlos@sanchezdonate,com` (coma en vez de punto). Amazon responde «La dirección de correo electrónico o el número de teléfono móvil faltan o son inválidos. Corríjalo e inténtelo de nuevo». Marca el campo, pero no dice **qué** está mal. Este formulario sí.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`validar.php`](validar.php) | `validar_registro()` devuelve `[campo => mensaje]`. `mensaje_email()` diagnostica el fallo concreto del correo y propone la corrección |
| [`index.php`](index.php) | El formulario. Si hay errores, resumen arriba con enlaces a cada campo, error junto al campo y lo escrito conservado |
| [`test.php`](test.php) | 11 casos, incluido el de la diapositiva. Sale con 1 si alguno falla |

## Qué heurística cumple cada detalle

| Heurística | En el formulario |
|---|---|
| **1 Visibilidad del estado** | Tras enviar: o el resumen de errores (`role="alert"`) o «Cuenta creada» con el correo al que se ha mandado la verificación (`role="status"`) |
| **3 Control y libertad** | Al fallar no se vacía nada salvo las contraseñas. Tras el alta, enlace para volver a empezar |
| **5 Prevención de errores** | `type="email"` + `inputmode="email"` (teclado con @ en móvil), ayuda «Mínimo 8 caracteres» visible **antes** de fallar |
| **6 Reconocimiento antes que recuerdo** | `autocomplete="name"`, `"email"`, `"new-password"`: el navegador ofrece lo que ya conoce y el gestor de contraseñas propone una nueva |
| **9 Diagnosticar y recuperarse** | Un mensaje por causa: coma en vez de punto, falta la @, dos @, espacios, dominio sin `.com`. Si se puede deducir el correo bueno, lo propone |
| **4 Consistencia** | Botón `<button type="submit">` real: **Enter envía**, como en cualquier formulario (el ejemplo de W3Schools de clase usaba `type="button"` + JavaScript y Enter no hacía nada) |

Accesibilidad: cada campo con error lleva `aria-invalid="true"` y `aria-describedby` apuntando a su mensaje, para que el lector de pantalla lo lea al entrar en el campo. Todo lo que se repinta pasa por `htmlspecialchars`.

## Cómo probarlo

Con el PHP de Laragon (cualquier versión 8.x):

```bash
cd ejercicios/sxo-formulario-heuristicas
php test.php
```

Salida esperada:

```
OK     todo correcto
OK     coma en vez de punto
...
OK     caso de la diapositiva

11/11 casos correctos
```

En el navegador:

```bash
php -S 127.0.0.1:8099 -t .
```

Abre `http://127.0.0.1:8099/`, escribe `carlos@sanchezdonate,com` y dos contraseñas distintas. Respuesta:

> **Revisa 2 campos:**
> - Correo electrónico: Has escrito una coma en lugar de un punto. ¿Querías decir carlos@sanchezdonate.com?
> - Confirma tu contraseña: Las contraseñas no coinciden. Vuelve a escribir la confirmación.

## Lo que salió al probarlo

- **`mbstring` no está cargado en el PHP de Laragon por línea de comandos** (no hay `php.ini`), así que `mb_strlen()` da *fatal error*. La longitud de la contraseña se cuenta con `preg_match_all('/./su', …)`, que es del núcleo y cuenta «ñ» como un carácter.
- **`curl.exe` en Windows recodifica los argumentos** al juego de caracteres ANSI: `--data-urlencode "pass=contraseña1"` llega como Latin-1, no UTF-8. Con UTF-8 inválido `preg_match_all` devuelve `false` y el mensaje decía «ahora tiene 0». Ahora cae a contar bytes. Para probar con acentos, manda el cuerpo desde un fichero (`--data-binary @fichero`), como en las pruebas de esta clase. Un navegador manda UTF-8 siempre que la página lo declare (`<meta charset="utf-8">`).
- Lo que **no** hace: guardar nada. Es una demo de UX, no un registro real.
