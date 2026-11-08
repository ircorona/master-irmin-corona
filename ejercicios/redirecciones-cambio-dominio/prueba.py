"""Prueba tres montajes de un cambio de dominio A -> B en un Apache temporal con dos hosts.

Uso: python prueba.py [--httpd RUTA_A_httpd.exe] [--puerto 8140]

  ingenuo-sin-legado   A manda todo a la misma ruta de B; B ha perdido las redirecciones antiguas
  ingenuo-con-legado   A manda todo a la misma ruta de B; B conserva las antiguas (sin aplanar)
  aplanado             lo que genera aplanar.py: A va directo al destino final; B conserva las antiguas

curl sigue las redirecciones con --resolve (a.test y b.test apuntan a 127.0.0.1) y cuenta saltos.
Usa el httpd.exe de Laragon con su propio httpd.conf en una carpeta temporal; no toca Laragon.
Sale con 0 si todos los casos dan lo esperado.
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
sys.path.insert(0, str(AQUI))
from aplanar import aplanar, generar, leer  # noqa: E402

CONF = """ServerRoot "{raiz}"
Listen 127.0.0.1:{puerto}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule rewrite_module modules/mod_rewrite.so
PidFile "{tmp}/httpd.pid"
ErrorLog "{tmp}/error.log"
DirectoryIndex index.html
<Directory "{tmp}">
    AllowOverride All
    Require all granted
</Directory>
<VirtualHost *:{puerto}>
    ServerName a.test
    DocumentRoot "{tmp}/a"
</VirtualHost>
<VirtualHost *:{puerto}>
    ServerName b.test
    DocumentRoot "{tmp}/b"
</VirtualHost>
"""
# página pedida, (saltos, código final, ruta final) por montaje
ESPERADO = {
    "ingenuo-sin-legado": {
        "a.test/contadores/": (1, 404, "b.test/contadores/"),
        "a.test/contables/": (1, 404, "b.test/contables/"),
        "a.test/otra-pagina/": (1, 200, "b.test/otra-pagina/"),
        "b.test/contadores/": (0, 404, "b.test/contadores/"),
    },
    "ingenuo-con-legado": {
        "a.test/contadores/": (2, 200, "b.test/cuentas/"),
        "a.test/contables/": (3, 200, "b.test/cuentas/"),
        "a.test/otra-pagina/": (1, 200, "b.test/otra-pagina/"),
        "b.test/contadores/": (1, 200, "b.test/cuentas/"),
    },
    "aplanado": {
        "a.test/contadores/": (1, 200, "b.test/cuentas/"),
        "a.test/contables/": (1, 200, "b.test/cuentas/"),
        "a.test/otra-pagina/": (1, 200, "b.test/otra-pagina/"),
        "b.test/contadores/": (1, 200, "b.test/cuentas/"),
    },
}


def pedir(puerto, url):
    host = url.split("/")[0]
    salida = subprocess.run(
        ["curl", "-s", "-o", "NUL", "-L", "--max-time", "5", "--noproxy", "*",
         "--resolve", f"a.test:{puerto}:127.0.0.1", "--resolve", f"b.test:{puerto}:127.0.0.1",
         "-w", "%{num_redirects} %{http_code} %{url_effective}",
         f"http://{host}:{puerto}/{url.split('/', 1)[1]}"],
        capture_output=True, text=True,
    ).stdout.split()
    saltos, codigo, final = int(salida[0]), int(salida[1]), salida[2]
    return saltos, codigo, final.replace("http://", "").replace(f":{puerto}", "")


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--httpd", default=(glob.glob("C:/laragon/bin/apache/*/bin/httpd.exe") or [""])[-1])
    p.add_argument("--puerto", type=int, default=8140)
    a = p.parse_args()
    if not a.httpd:
        raise SystemExit("No encuentro httpd.exe; indícalo con --httpd")
    raiz = Path(a.httpd).parent.parent.as_posix()

    # Comprobación del aplanado sin servidor: un CSV con bucle debe fallar.
    try:
        aplanar({"/x/": "/y/", "/y/": "/x/"})
        bucle_ok = False
    except ValueError:
        bucle_ok = True
    print(f"Bucle /x/ -> /y/ -> /x/ detectado: {'sí' if bucle_ok else 'NO'}\n")

    nuevo = f"http://b.test:{a.puerto}"
    final, _ = aplanar(leer(AQUI / "redirecciones-antiguas.csv"))
    plano_a, plano_b = generar(final, nuevo)
    ingenuo_a = f"RewriteEngine On\nRewriteRule ^(.*)$ {nuevo}/$1 [R=301,L]\n"
    sin_aplanar_b = "RewriteEngine On\n" + "".join(
        f"RewriteRule ^{o.strip('/')}/?$ {d} [R=301,L]\n" for o, d in leer(AQUI / "redirecciones-antiguas.csv").items())
    montajes = {
        "ingenuo-sin-legado": (ingenuo_a, ""),
        "ingenuo-con-legado": (ingenuo_a, sin_aplanar_b),
        "aplanado": (plano_a, plano_b),
    }

    bien = total = 0
    with tempfile.TemporaryDirectory() as t:
        tmp = Path(t)
        for pagina in ("cuentas", "servicios", "otra-pagina"):
            (tmp / "b" / pagina).mkdir(parents=True)
            (tmp / "b" / pagina / "index.html").write_text(f"<title>{pagina}</title>", encoding="utf-8")
        (tmp / "a").mkdir()
        conf = tmp / "httpd.conf"
        conf.write_text(CONF.format(raiz=raiz, puerto=a.puerto, tmp=tmp.as_posix()), encoding="utf-8")

        print(f"{'Montaje':<21}{'Pedida':<22}{'Saltos':<8}{'Código':<8}{'Termina en':<22}Resultado")
        for nombre, (ht_a, ht_b) in montajes.items():
            (tmp / "a" / ".htaccess").write_text(ht_a, encoding="utf-8")
            (tmp / "b" / ".htaccess").write_text(ht_b, encoding="utf-8")
            proceso = subprocess.Popen([a.httpd, "-f", str(conf)], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
            try:
                for _ in range(50):
                    if subprocess.run(["curl", "-s", "-o", "NUL", "--noproxy", "*", f"http://127.0.0.1:{a.puerto}/"],
                                      capture_output=True).returncode == 0:
                        break
                    time.sleep(0.1)
                for url, esperado in ESPERADO[nombre].items():
                    obtenido = pedir(a.puerto, url)
                    ok = obtenido == esperado
                    bien += ok
                    total += 1
                    print(f"{nombre:<21}{url:<22}{obtenido[0]:<8}{obtenido[1]:<8}{obtenido[2]:<22}"
                          f"{'correcto' if ok else 'FALLO'}")
            finally:
                proceso.terminate()
                proceso.wait()

    print(f"\nCasos correctos: {bien} de {total}")
    return 0 if bien == total and bucle_ok else 1


if __name__ == "__main__":
    sys.exit(main())
