# Verificar Search Console: el registro TXT del dominio

Ejercicio de la clase **Migraciones 06-03 · Maquetación**. Antes de lanzar, la propiedad de Search Console del dominio nuevo tiene que estar verificada. La clase lo enseña con `host -t txt amazon.com`: entre los TXT del dominio aparece el `google-site-verification=…` que verifica la propiedad de dominio. Este script hace lo mismo desde cualquier sistema.

| Fichero | Qué hace |
|---|---|
| `txt.py` | Pide los TXT por DNS sobre HTTPS (`dns.google`), marca los de Google, SPF y otros servicios, y mira si la portada lleva `<meta name="google-site-verification">` |

Solo librería estándar de Python. No necesita `host` ni `dig` (en Windows no vienen).

## Ejecutar

```bash
python txt.py                     # amazon.com y auramip.com
python txt.py ejemplo.com         # cualquier dominio, sin https://
```

Equivalentes en consola, para comparar:

```bash
host -t txt auramip.com                 # Linux / macOS
nslookup -type=txt auramip.com          # Windows y Git Bash
```

Sale con código 0 si todos los dominios responden y 1 si alguno falla.

## Resultado (Python 3.11, 2026-11-02)

```
== amazon.com
   GOOGLE  google-site-verification=D0RwRb_QApkpApKTFaFlRwbm_yrkey0uokKw0wQUIdk
   GOOGLE  google-site-verification=NV91qEfNgqDZOPzwlhXE-KtDUfCBSNgAsdxaFebyh80
   GOOGLE  google-site-verification=14WGW2MdNMxchG8PlinF7LgqqE0OwwHqOq0HKhb7rDQ
   GOOGLE  google-site-verification=G_-mXb0ZYjjGkQVGjpOOB2deSOaVdxVj4i4vozJTREs
   verif.  autodesk-domain-verification=dmryiygGOGBJFJFVo5Bl
   …  (unos 70 TXT: Stripe, Apple, Uber, Atlassian, Facebook, Microsoft MS=, Cisco, Wrike…)
   spf     v=spf1 include:spf1.amazon.com include:spf2.amazon.com include:amazonses.com -all
   -> TXT de Google: 4  (vale para propiedad de dominio)
   -> sin meta de verificación en la portada
== auramip.com
   GOOGLE  google-site-verification=le6of-766ijjxbqZflGatJYV7xHAYJgQIAOeDImJcM8
   spf     v=spf1 include:spf.protection.outlook.com include:_spf.mail.hostinger.com ~all
   -> TXT de Google: 1  (vale para propiedad de dominio)
   -> sin meta de verificación en la portada

2/2 dominios revisados
```

## Qué enseña

- **El token de la diapositiva sigue ahí**: `14WGW2MdNMxchG8PlinF7LgqqE0OwwHqOq0HKhb7rDQ` es uno de los cuatro TXT de Google de amazon.com. Hay cuatro porque **cada propietario verificado deja su propio token**; Google dice que varias personas pueden verificar la misma propiedad y que hay que tener cuidado de no borrar los tokens de los demás.
- **Un TXT así es público**: cualquiera puede ver con qué servicios trabaja un dominio (Stripe, Atlassian, Zoom…). No es un secreto, el token solo demuestra control del DNS.
- **auramip.com tiene su TXT de Google**: la propiedad de dominio (todas las variantes `http`/`https` y subdominios) es la que hay que usar para seguir la migración. No lleva meta en la portada, así que no depende de que la plantilla de Astro conserve la etiqueta: el método DNS sobrevive a cualquier cambio de web. El SPF muestra correo por Outlook y Hostinger.

## Límites

- Depende de `dns.google`; si está bloqueado en la red, usar `nslookup -type=txt`.
- No comprueba que el token siga validado en Search Console (eso solo lo ve el propietario), solo que el registro existe.
- La meta se busca solo en la portada y sin JavaScript.
