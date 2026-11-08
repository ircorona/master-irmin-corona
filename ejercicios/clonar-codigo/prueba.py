"""Prueba los .htaccess de la clase 18 en un Apache temporal (el binario de Laragon, sin tocar su configuración).

Uso: python prueba.py [--httpd RUTA_A_httpd.exe] [--puerto 8139]

Una sola carpeta web sirve cualquier Host: es el caso de un hosting donde el dominio viejo y el
nuevo apuntan a la misma carpeta. Sale con 0 si todos los casos dan lo esperado.
"""
import argparse
import glob
import subprocess
import sys
import tempfile
import time
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")
AQUI = Path(__file__).parent
VIEJO, NUEVO, STAGING = "carlos.sanchezdonate.com", "migracion.test", "test-carlos.sanchezdonate.com"

CONF = """ServerRoot "{raiz}"
Listen 127.0.0.1:{puerto}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_user_module modules/mod_authz_user.so
LoadModule authn_core_module modules/mod_authn_core.so
LoadModule authn_file_module modules/mod_authn_file.so
LoadModule auth_basic_module modules/mod_auth_basic.so
LoadModule alias_module modules/mod_alias.so
LoadModule dir_module modules/mod_dir.so
LoadModule rewrite_module modules/mod_rewrite.so
PidFile "{tmp}/httpd.pid"
ErrorLog "{tmp}/error.log"
DocumentRoot "{web}"
DirectoryIndex index.html
<Directory "{web}">
    AllowOverride All
    Require all granted
</Directory>
"""

# (título, variante, Host, ruta, credenciales, código esperado, Location esperada o None)
CASOS = [
    ("Clase: RedirectMatch ^(.*)$ desde el dominio viejo", "clase-redirectmatch", VIEJO, "/blog/", None, 301, f"https://{NUEVO}//blog/"),
    ("Clase: el dominio nuevo en la misma carpeta se redirige a sí mismo", "clase-redirectmatch", NUEVO, "/blog/", None, 301, f"https://{NUEVO}//blog/"),
    ("Redirect 301 / por prefijo desde el viejo", "redirect-prefijo", VIEJO, "/blog/", None, 301, f"https://{NUEVO}/blog/"),
    ("Redirect 301 / en la misma carpeta: también bucle", "redirect-prefijo", NUEVO, "/blog/", None, 301, f"https://{NUEVO}/blog/"),
    ("RewriteCond por Host desde el viejo", "rewrite-con-host", VIEJO, "/blog/", None, 301, f"https://{NUEVO}/blog/"),
    ("RewriteCond por Host: el nuevo se sirve", "rewrite-con-host", NUEVO, "/blog/", None, 200, None),
    ("La URL con doble barra también da 200 (duplicada)", "rewrite-con-host", NUEVO, "//blog/", None, 200, None),
    ("Clase: <If> staging sin contraseña", "staging-if-auth", STAGING, "/", None, 401, None),
    ("Clase: <If> staging con contraseña", "staging-if-auth", STAGING, "/", "editor:prueba", 200, None),
    ("Clase: <If> otro Host no pide contraseña", "staging-if-auth", NUEVO, "/", None, 200, None),
    ("Ruta con el dominio, original, con contraseña", "auth-ruta-con-dominio", NUEVO, "/privado.html", "editor:prueba", 200, None),
    ("Tras «Reemplazar todo», sin contraseña: parece que va", "auth-ruta-con-dominio@reemplazado", NUEVO, "/privado.html", None, 401, None),
    ("Tras «Reemplazar todo», al entrar: error", "auth-ruta-con-dominio@reemplazado", NUEVO, "/privado.html", "editor:prueba", 500, None),
    ("Plugin: php_value sin mod_php", "plugin-php-value", NUEVO, "/", None, 500, None),
    ("Apache 2.2 Order/Deny sin mod_access_compat", "apache22-order", NUEVO, "/", None, 500, None),
]


def peticion(puerto, host, ruta, cred=None):
    cmd = ["curl", "-s", "-o", "NUL", "--noproxy", "*", "--max-time", "5", "-D", "-", "-H", f"Host: {host}"]
    if cred:
        cmd += ["-u", cred]
    salida = subprocess.run(cmd + [f"http://127.0.0.1:{puerto}{ruta}"], capture_output=True, text=True).stdout
    lineas = salida.splitlines()
    codigo = int(lineas[0].split()[1]) if lineas else 0
    loc = next((l.split(":", 1)[1].strip() for l in lineas if l.lower().startswith("location:")), None)
    return codigo, loc


def arrancar(httpd, conf, puerto):
    proc = subprocess.Popen([httpd, "-f", conf], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    for _ in range(50):
        if peticion(puerto, "localhost", "/")[0]:
            return proc
        time.sleep(0.1)
    proc.terminate()
    raise SystemExit("Apache no arranca: " + proc.stderr.read().decode(errors="replace"))


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--httpd", default=(glob.glob("C:/laragon/bin/apache/*/bin/httpd.exe") or [""])[-1])
    p.add_argument("--puerto", type=int, default=8139)
    a = p.parse_args()
    if not a.httpd:
        raise SystemExit("No encuentro httpd.exe; indícalo con --httpd")
    raiz_apache = Path(a.httpd).parent.parent.as_posix()
    htpasswd_exe = str(Path(a.httpd).parent / "htpasswd.exe")

    bien = 0
    with tempfile.TemporaryDirectory() as t:
        tmp = Path(t)
        web = tmp / "web"
        (web / "blog").mkdir(parents=True)
        for f in (web / "index.html", web / "blog" / "index.html", web / "privado.html"):
            f.write_text("<!doctype html><title>ok</title>", encoding="utf-8")
        # contraseñas: una para staging y otra en la carpeta que lleva el nombre del dominio
        (tmp / VIEJO).mkdir()
        for destino in (tmp / ".htpasswd-staging", tmp / VIEJO / ".htpasswd"):
            subprocess.run([htpasswd_exe, "-bcB", str(destino), "editor", "prueba"], check=True, capture_output=True)
        conf = tmp / "httpd.conf"
        conf.write_text(CONF.format(raiz=raiz_apache, puerto=a.puerto, tmp=tmp.as_posix(), web=web.as_posix()), encoding="utf-8")

        print(f"{'Caso':<66}{'Host':<32}{'Código':<8}Location")
        for titulo, variante, host, ruta, cred, codigo_esp, loc_esp in CASOS:
            nombre, _, modo = variante.partition("@")
            regla = (AQUI / "variantes" / f"{nombre}.htaccess").read_text(encoding="utf-8")
            regla = regla.replace("{HTPASSWD}", (tmp / ".htpasswd-staging").as_posix()).replace("{RAIZ}", tmp.as_posix())
            if modo == "reemplazado":   # lo que hace «Replace All» de VS Code con el dominio
                regla = regla.replace(VIEJO, "donatesanchez.test")
            (web / ".htaccess").write_text(regla, encoding="utf-8")
            proc = arrancar(a.httpd, str(conf), a.puerto)
            try:
                codigo, loc = peticion(a.puerto, host, ruta, cred)
            finally:
                proc.terminate()
                proc.wait()
            ok = codigo == codigo_esp and loc == loc_esp
            bucle = loc is not None and loc.split("/")[2] == host
            bien += ok
            nota = "  (BUCLE: redirige a su propio Host)" if bucle else ""
            print(f"{titulo:<66}{host:<32}{codigo:<8}{loc or '-'}{nota}  {'OK' if ok else 'FALLO'}")
        errores = (tmp / "error.log").read_text(encoding="utf-8", errors="replace")
        for clave in ("Could not open password file", "php_value", "Order"):
            linea = next((l for l in errores.splitlines() if clave in l), None)
            if linea:
                print(f"  error.log: …{linea[linea.find(']', linea.find('[client')) + 2:][:150]}")

    print(f"\nCasos correctos: {bien} de {len(CASOS)}")
    return 0 if bien == len(CASOS) else 1


if __name__ == "__main__":
    sys.exit(main())
