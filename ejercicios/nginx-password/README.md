# Contraseña en nginx (`auth_basic`)

Ejercicio de la clase 16 (NGINX contraseña) de Servidores (Avanzado): **genera tu propio acceso por contraseña**.

## Qué hay

| Ruta | Qué es |
|---|---|
| `sitio/pass/index.php` | El PHP de clase que imprime la ruta del `.htpasswd` |
| `sitio/pass/.htpasswd` | Lo genera `prueba.py` en cada ejecución y lo borra al acabar (no está en git) |
| `sitio/privado/` | La zona a proteger: un `index.php` y un `informe.html` |
| `conf/clase-server.conf` | El código de clase tal cual: `auth_basic` en el `server`, ruta de Windows con `\` |
| `conf/clase-server-barras.conf` | El mismo con `/` en la ruta |
| `conf/clase-location.conf` | La versión del artículo de Carlos: `location /privado { … }` |
| `conf/ejercicio.conf` | El ejercicio |
| `prueba.py` | Arranca el nginx y el PHP de Laragon y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py            # 27 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y abrir http://127.0.0.1:8034/privado/ (usuario irmin / clave-apr1)
```

Las contraseñas se crean con el `htpasswd.exe` de Apache que trae Laragon. Puertos 8031 a 8034 y 9131.

## El ejercicio

```nginx
location ~ /\. {
    return 404;                              # .htpasswd, .env, .git...
}

location ^~ /privado/ {
    auth_basic "Zona privada";
    auth_basic_user_file "C:/ruta/fuera/de/la/web/.htpasswd";

    location ~ \.php$ {                      # el PHP DENTRO: hereda la contraseña
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param REMOTE_USER $remote_user;
        fastcgi_pass php_upstream;
    }
}
location = /privado {
    return 301 /privado/;
}
```

Y el usuario, en formato apr1 (el que entiende nginx en cualquier sistema):

```bash
htpasswd -c -m "C:/ruta/fuera/de/la/web/.htpasswd" irmin
```

## Lo que se midió

| Prueba | Resultado |
|---|---|
| Código de clase con la ruta `C:\...\nginx-password\...` | **500**: nginx lee `\n` como un salto de línea y la ruta deja de existir |
| Usuario con bcrypt (`htpasswd -B`) en el nginx de Windows | **401 con la clave buena** («password mismatch»): no lo entiende |
| apr1 (`-m`) y `{SHA}` (`-s`) | Entran |
| `location /privado` del artículo, en un server con PHP | `/privado/index.php` **sin contraseña** (la regex de PHP gana) |
| Sin `location ~ /\.ht` | `/pass/.htpasswd` **se descarga** con los hashes |
| Ejercicio | Todo `/privado/` pide contraseña, también el PHP; la portada sigue pública; ficheros ocultos 404 |
