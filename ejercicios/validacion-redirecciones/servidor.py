"""Servidor local que hace de entorno de pre de una migración (ejemplo inventado).

Uso:
  python servidor.py [--puerto 8134] [--estado roto|arreglado] [--orden transversal|especifico]

Atiende dominio.tld y www.dominio.tld según la cabecera Host, igual que un servidor real.
  --estado roto       redirecciones con los fallos típicos, y robots.txt con Disallow: /
  --estado arreglado  todo el mapa implementado, y robots.txt en blanco en dominio.tld
  --orden transversal primero la regla sin www a con www, luego las concretas
  --orden especifico  primero las concretas, que van directas al destino final
"""
import argparse
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

CON_WWW = "www.dominio.tld"

MAPA = {
    "roto": {
        "/lelele": (301, "/lololo"),
        "/jkdkdkdkdkd": (301, "/jejejeje"),
        "/kdkdkdki": (301, "/oubuib3ibfi"),
        "/ruta-temporal": (302, "/ruta-nueva"),
        "/caso": (301, "/Nuevo-Caso"),
        "/a-portada": (301, "/"),
        "/roto": (301, "/destino-roto"),
    },
    "arreglado": {
        "/lelele": (301, "/lololo"),
        "/jkdkdkdkdkd": (301, "/jejejeje"),
        "/kdkdkdki": (301, "/oubuib3ibfi"),
        "/ruta-temporal": (301, "/ruta-nueva"),
        "/caso": (301, "/nuevo-caso"),
        "/a-portada": (301, "/producto"),
        "/roto": (301, "/destino-bueno"),
        "/olvidada": (301, "/nueva-olvidada"),
        "/vivo": (301, "/nuevo-vivo"),
    },
}
PAGINAS = {
    "/", "/lololo", "/jejejeje", "/oubuib3ibfi", "/ruta-nueva", "/producto", "/vivo",
    "/Nuevo-Caso", "/nuevo-caso", "/destino-bueno", "/nueva-olvidada", "/nuevo-vivo",
}


def responder(estado, orden, host, ruta):
    """Devuelve (código, Location o None, cuerpo)."""
    if ruta == "/robots.txt":
        cierra = estado == "roto" and host != CON_WWW
        return 200, None, "User-agent: *\nDisallow: /\n" if cierra else ""

    mapa = MAPA[estado]
    if orden == "especifico" and ruta in mapa:
        codigo, destino = mapa[ruta]
        return codigo, f"http://{CON_WWW}{destino}", ""
    if host != CON_WWW:
        return 301, f"http://{CON_WWW}{ruta}", ""
    if ruta in mapa:
        codigo, destino = mapa[ruta]
        return codigo, f"http://{CON_WWW}{destino}", ""
    if ruta in PAGINAS:
        return 200, None, "<!doctype html><title>página</title>"
    return 404, None, "no existe"


def crear(estado, orden):
    class Manejador(BaseHTTPRequestHandler):
        def do_GET(self):
            host = self.headers.get("Host", "").split(":")[0]
            ruta = self.path.split("?")[0]
            codigo, destino, cuerpo = responder(estado, orden, host, ruta)
            datos = cuerpo.encode("utf-8")
            self.send_response(codigo)
            if destino:
                self.send_header("Location", destino)
            self.send_header("Content-Type", "text/plain; charset=utf-8")
            self.send_header("Content-Length", str(len(datos)))
            self.end_headers()
            self.wfile.write(datos)

        def log_message(self, *args):
            pass

    return Manejador


if __name__ == "__main__":
    p = argparse.ArgumentParser()
    p.add_argument("--puerto", type=int, default=8134)
    p.add_argument("--estado", choices=["roto", "arreglado"], default="roto")
    p.add_argument("--orden", choices=["transversal", "especifico"], default="transversal")
    a = p.parse_args()
    print(f"pre en 127.0.0.1:{a.puerto} (estado={a.estado}, orden={a.orden})", flush=True)
    ThreadingHTTPServer(("127.0.0.1", a.puerto), crear(a.estado, a.orden)).serve_forever()
