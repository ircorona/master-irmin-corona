# Bloqueos desde `.htaccess`: IP, contraseña y user-agent

Ejercicio de **Servidores (Avanzado), clase 08 Bloqueos**.

## El enunciado

1. Genera un bloqueo por medio de IP.
2. Genera un bloqueo por contraseña.
3. Genera un bloqueo por medio de user agent.

## La solución

En [`sitio/.htaccess`](sitio/.htaccess), con la sintaxis de Apache 2.4:

```apache
ErrorDocument 403 /errores/403.html
ErrorDocument 401 /errores/401.html

# 1. IP: el sitemap de idiomas solo para una IP
<Files "sitemap-lang.xml">
    Require ip 203.0.113.10
</Files>

# 2. Contraseña: toda la web, solo en el host de staging
<If "%{HTTP_HOST} =~ m#^staging\.#">
    AuthType Basic
    AuthName "Staging"
    AuthUserFile "${PASSFILE}"
    Require valid-user
</If>

# 3. User-agent
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (SemrushBot-BA|SemrushBot-SI|ChatGPT-User|Screaming\ Frog\ SEO\ Spider) [NC]
RewriteRule ^ - [F]

RewriteCond %{HTTP_USER_AGENT} (Googlebot|Bingbot) [NC]
RewriteRule ^seo-avanzado(/|$) - [F]
</IfModule>
```

| Detalle | Por qué |
|---|---|
| `Require ip` y no `Order/Deny/Allow` | `Order` es Apache 2.2. En 2.4 solo funciona si está cargado `mod_access_compat`; si no, **500** (medido). Y mezclar las dos sintaxis da resultados imprevisibles, avisa la documentación |
| `203.0.113.10` | IP de documentación (RFC 5737). En un sitio real, la IP fija de quien deba entrar. Detrás de Cloudflare, Apache ve la IP de Cloudflare: hace falta `mod_remoteip` |
| `<If "%{HTTP_HOST} =~ m#^staging\.#">` | La idea de Carlos: el mismo `.htaccess` pide contraseña en staging y no en producción. Con regex en vez de `==`, también casa si el host lleva puerto (`staging.x:8095`) |
| `.htpasswd` fuera de la web | Lo pide la documentación de Apache. Aquí `/.htpasswd` da 404: no existe en la carpeta pública |
| **Sin** `<IfModule>` en la contraseña | Si falta `mod_authn_file`, un `<IfModule>` se salta el bloque entero y la web queda **abierta** (medido). Sin él, da 500: mejor caída que expuesta |
| `[F]` sin `L` | `F` ya corta el procesado. `^` casa con cualquier ruta; `^.*$` y `^.*(…).*$` hacen lo mismo con más trabajo |
| `Screaming\ Frog\ SEO\ Spider` | El espacio escapado es exacto; `.*` entre palabras también casaría con textos que no son ese UA |

## Las demos de clase

[`demos/`](demos/) tiene el código de clase tal cual, una carpeta por bloqueo:

| Demo | Resultado medido |
|---|---|
| `ip/`: `order deny,allow` / `deny from all` / `allow from` | 403 con `mod_access_compat`; **500** sin él |
| `password/`: `<If HTTP_HOST>` + `<IfModule mod_authn_file.c>` | 401 con el módulo; **200, web abierta**, sin él |
| `password/`: `AuthName "Iniciar sesión requerido"` | La tilde viaja como bytes UTF-8 en `WWW-Authenticate`; las cabeceras deberían ser ASCII |
| `password/`: `ERRORDOCUMENT 401 '<!DOCTYPE …'` | Apache acepta las comillas simples y sirve el texto. `&AACUTES` no es una entidad HTML: sale tal cual |
| `ua/`: todo en MAYÚSCULAS | Apache acepta directivas y variables en mayúsculas, y `[NC]` hace el resto: 403 a Screaming Frog e `inventado/1.0` |

El `ERRORDOCUMENT 401` de clase no actúa nunca en el bloqueo por user-agent: `[F]` devuelve **403**, no 401.

## Cómo probarlo

```bash
python prueba.py
```

Genera un `.htpasswd` temporal con el `htpasswd.exe` de Laragon (bcrypt, `-B`), fuera de la carpeta pública, y levanta un Apache de usar y tirar en los puertos 8095 (sitio) y 8094 (demos). Lo arranca **dos veces**: con todos los módulos, y sin `mod_access_compat` ni `mod_authn_file`, para medir qué hace cada versión cuando falta un módulo. No se guarda ninguna contraseña en el repo.

Resultado: **24/24 comprobaciones OK, exit 0**.

En un sitio real:

```bash
curl -sI https://dominio.com/sitemap-lang.xml                       # 403
curl -sI -A "Screaming Frog SEO Spider/21.0" https://dominio.com/   # 403
curl -sI https://staging.dominio.com/                               # 401
curl -sI -u usuario:clave https://staging.dominio.com/              # 200
```
