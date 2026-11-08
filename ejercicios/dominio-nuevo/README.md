# El condicional de base de datos al cambiar de dominio

Ejercicio de la clase **Migraciones 21 · Dominio nuevo**. La clase da un «código de ayuda» para `wp-config.php`: si el dominio es el de producción, credenciales de producción; si no, las de local (`root` sin contraseña). Aquí se prueba qué base de datos elige según el `Host` de la petición, frente a una versión que normaliza el dominio.

| Fichero | Qué es |
|---|---|
| `config-clase.php` | El fragmento de clase tal cual, más la línea `$dominio_actual = $_SERVER['HTTP_HOST'];` que le falta |
| `config-mejorada.php` | Minúsculas, sin puerto, sin punto final, sin `www.`, y una lista con el dominio viejo y el nuevo |
| `ver.php` | `php ver.php <config> [host]`: dice qué base de datos elige |
| `prueba.php` | Los 7 casos, cada uno en un proceso aparte |

## Ejecutar

```bash
php prueba.php
php ver.php config-clase.php www.dominioproduccion.com
```

Sale con 0 si los 7 casos dan lo esperado.

## Resultado (PHP 8.3.26 y 8.5.7 de Laragon, 2026-11-08, mismo resultado en los dos)

```
Host                         Clase        Mejorada     Resultado
dominioproduccion.com        producción   producción   correcto  (producción, como en clase)
www.dominioproduccion.com    local        producción   correcto  (producción con www)
DominioProduccion.com        local        producción   correcto  (mayúsculas (un script, no un navegador))
dominioproduccion.com:8443   local        producción   correcto  (puerto no estándar)
dominionuevo.com             local        producción   correcto  (el dominio nuevo, sin añadir a la clase)
localhost                    local        local        correcto  (local)
(sin Host)                   local*       local        correcto  (línea de comandos (WP-CLI, cron))

* = la clase da un aviso de PHP (Undefined array key "HTTP_HOST")
Casos correctos: 7 de 7
```

Lo que se ve: **con el condicional de clase, cualquier variante del dominio que no sea exactamente la escrita cae en local**. En el servidor eso es `root` sin contraseña, y WordPress muestra el error de conexión con la base de datos. El caso que más importa en esta clase es el quinto: **al pasar al dominio nuevo, si no cambias la condición, la web nueva no conecta**.

## Lo que no arregla

La versión mejorada sigue decidiendo por la cabecera `Host`, que la manda el visitante. Un `Host: localhost` contra producción elige local. La solución de fondo es que el entorno lo fije el servidor (`WP_ENVIRONMENT_TYPE`): ver `config-seguro.php` en [../clonar-base-de-datos/](../clonar-base-de-datos/).

Y si el `wp-config.php` va a GitHub, **la contraseña de producción no puede estar escrita en él**: queda en el historial para siempre. En la versión mejorada sale de una variable de entorno o de un fichero fuera del repositorio.
