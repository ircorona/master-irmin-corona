"""Valida el documento de redirecciones contra el servidor de pre, como si hubiera hosts editado.

Uso:
  python validar.py CARPETA --servidor IP[:PUERTO] [--inseguro]
  python validar.py CARPETA                        (sin --servidor: contra el DNS real)

CARPETA contiene:
  reglas.csv      patron,origen,prevista   (lo más transversal arriba)
  url-a-url.csv   origen,prevista

--servidor usa curl --connect-to: cada petición va a esa IP con el Host y la URL reales.
Es lo mismo que una línea en el archivo hosts, pero solo para este proceso y sin tocar el
sistema. --inseguro añade -k, para el certificado provisional de pre.

Para cada URL calcula dos veredictos:
  Hoja    lo que da la fórmula de la clase: final = prevista sin distinguir mayúsculas
  Script  lo mismo con comparación exacta y exigiendo que el destino responda 200
Avisa además de 302/303/307, cadenas y robots.txt que bloquea el origen.

Sale con 0 solo si el resultado es 100 % y sin avisos (se puede dar el OK); si no, con 1.
"""
import argparse
import csv
import os
import subprocess
import sys
import urllib.robotparser
from pathlib import Path
from urllib.parse import urljoin, urlsplit

sys.stdout.reconfigure(encoding="utf-8")

MAX_SALTOS = 10
SI, NO, NO_EXISTE = "Sí", "No", "No existe redirección"


def curl(url, opciones, cuerpo=False):
    orden = ["curl", "-s", "--noproxy", "*", "--max-redirs", "0", "--max-time", "20"]
    if opciones.servidor:
        ip, _, puerto = opciones.servidor.partition(":")
        orden += ["--connect-to", f"::{ip}:{puerto}"]
    if opciones.inseguro:
        orden.append("-k")
    if not cuerpo:
        orden += ["-o", os.devnull, "-w", "%{http_code} %{redirect_url}"]
    r = subprocess.run(orden + [url], capture_output=True, text=True, encoding="utf-8")
    if r.returncode:
        return None
    return r.stdout


def seguir(url, opciones):
    """Lista de saltos (código, url, destino). Lista vacía si no hay respuesta."""
    cadena = []
    while len(cadena) < MAX_SALTOS:
        salida = curl(url, opciones)
        if salida is None:
            break
        codigo, _, destino = salida.partition(" ")
        cadena.append((int(codigo), url, urljoin(url, destino) if destino else None))
        if not destino:
            break
        url = cadena[-1][2]
    return cadena


def veredictos(prevista, cadena):
    """(veredicto de la hoja, veredicto del script, URL final, código final)."""
    if not cadena:
        return NO_EXISTE, NO_EXISTE, "", 0
    codigo, final, destino = cadena[-1]
    final = destino or final
    hoja = SI if final.casefold() == prevista.casefold() else NO
    if not any(s[2] for s in cadena) and final != prevista:
        return hoja, NO_EXISTE, final, codigo
    if len(cadena) == 1 and codigo == 404 and final != prevista:
        return hoja, NO_EXISTE, final, codigo
    return hoja, SI if final == prevista and codigo == 200 else NO, final, codigo


def avisos_de(cadena):
    redirecciones = [s for s in cadena if s[2]]
    lista = []
    if any(c in (302, 303, 307) for c, _, _ in redirecciones):
        lista.append("redirección temporal")
    if len(redirecciones) > 1:
        lista.append(f"cadena de {len(redirecciones)} saltos")
    if len(cadena) == MAX_SALTOS and cadena[-1][2]:
        lista.append("posible bucle")
    return lista


def leer(ruta):
    with open(ruta, encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f))


def robots_bloqueados(filas, opciones):
    bloqueados = []
    for raiz in sorted({"{0.scheme}://{0.netloc}".format(urlsplit(f["origen"])) for f in filas}):
        lector = urllib.robotparser.RobotFileParser()
        lector.parse((curl(raiz + "/robots.txt", opciones, cuerpo=True) or "").splitlines())
        impedidas = [f["origen"] for f in filas
                     if f["origen"].startswith(raiz) and not lector.can_fetch("Googlebot", f["origen"])]
        print(f"   {raiz}/robots.txt: {len(impedidas)} URLs de origen bloqueadas para Googlebot")
        if impedidas:
            bloqueados.append(raiz)
    return bloqueados


def porcentaje(parte, total):
    return f"{100 * parte / total:.1f} %".replace(".", ",")


def main():
    p = argparse.ArgumentParser()
    p.add_argument("carpeta", type=Path)
    p.add_argument("--servidor")
    p.add_argument("--inseguro", action="store_true")
    a = p.parse_args()

    filas = [dict(f, hoja_origen="reglas") for f in leer(a.carpeta / "reglas.csv")]
    filas += [dict(f, hoja_origen="url-a-url") for f in leer(a.carpeta / "url-a-url.csv")]

    print(f"== Redirecciones ({a.servidor or 'DNS real'})")
    hoja_si = script_si = comprobadas = 0
    con_aviso = []
    for f in filas:
        cadena = seguir(f["origen"], a)
        hoja, script, final, codigo = veredictos(f["prevista"], cadena)
        avisos = avisos_de(cadena)
        comprobadas += 1 if cadena else 0
        hoja_si += hoja == SI
        script_si += script == SI
        con_aviso += [f["origen"]] if avisos else []
        marca = "  ! " + ", ".join(avisos) if avisos else ""
        print(f"   {f['origen']}")
        print(f"      prevista {f['prevista']}")
        print(f"      final    {final} ({codigo})  Hoja: {hoja} · Script: {script}{marca}")

    print("\n== robots.txt de los orígenes")
    bloqueados = robots_bloqueados(filas, a)

    total = len(filas)
    print(f"\n== Resultado")
    print(f"   Comprobadas {comprobadas} de {total} (reglas + URL a URL)")
    print(f"   Hoja:   {hoja_si} Sí de {total} = {porcentaje(hoja_si, total)}")
    print(f"   Script: {script_si} Sí de {total} = {porcentaje(script_si, total)}")
    print(f"   Con avisos: {len(con_aviso)}. Orígenes con robots.txt bloqueante: {len(bloqueados)}")

    ok = script_si == total and not con_aviso and not bloqueados
    print("   OK AL LANZAMIENTO" if ok else "   NO SE PUEDE DAR EL OK")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
