"""Genera las redirecciones de un cambio de dominio sin perder las antiguas y sin cadenas.

Uso: python aplanar.py redirecciones-antiguas.csv https://dominio-nuevo.com [--salida carpeta]

Entrada: un CSV con las redirecciones que ya tenía la web (origen,destino, rutas que
empiezan por /). Puede tener cadenas (A -> B -> C); aquí se resuelven al destino final.

Salida:
  a.htaccess  para el dominio VIEJO: cada ruta antigua va directa al destino final en el
              dominio nuevo (1 salto) y el resto, a la misma ruta en el dominio nuevo.
  b.htaccess  para el dominio NUEVO: conserva las redirecciones antiguas, ya aplanadas.

Sale con 1 si encuentra un bucle en el CSV.
"""
import argparse
import csv
import re
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")


def leer(csv_path):
    with open(csv_path, encoding="utf-8", newline="") as f:
        return {fila["origen"].strip(): fila["destino"].strip() for fila in csv.DictReader(f)}


def aplanar(mapa):
    """Devuelve ({origen: destino_final}, [cadenas encontradas]). Lanza ValueError si hay bucle."""
    final, cadenas = {}, []
    for origen in mapa:
        visto, actual = [origen], mapa[origen]
        while actual in mapa:
            if actual in visto:
                raise ValueError("bucle: " + " -> ".join(visto + [actual]))
            visto.append(actual)
            actual = mapa[actual]
        final[origen] = actual
        if len(visto) > 1:
            cadenas.append(visto + [actual])
    return final, cadenas


def patron(ruta):
    # /contadores/ -> ^contadores/?$  (en .htaccess la ruta llega sin la barra inicial)
    return "^" + re.escape(ruta.strip("/")) + "/?$"


def generar(final, nuevo):
    nuevo = nuevo.rstrip("/")
    a = ["RewriteEngine On", "# Rutas antiguas: directas al destino final en el dominio nuevo"]
    b = ["RewriteEngine On", "# Redirecciones que ya tenía la web, aplanadas"]
    for origen, destino in final.items():
        a.append(f"RewriteRule {patron(origen)} {nuevo}{destino} [R=301,L]")
        b.append(f"RewriteRule {patron(origen)} {destino} [R=301,L]")
    a += ["# Todo lo demás: misma ruta en el dominio nuevo", f"RewriteRule ^(.*)$ {nuevo}/$1 [R=301,L]"]
    return "\n".join(a) + "\n", "\n".join(b) + "\n"


def main():
    p = argparse.ArgumentParser()
    p.add_argument("csv")
    p.add_argument("nuevo", help="dominio nuevo con protocolo, p. ej. https://dominio-nuevo.com")
    p.add_argument("--salida", default="salida")
    a = p.parse_args()
    try:
        final, cadenas = aplanar(leer(a.csv))
    except ValueError as e:
        print(f"ERROR, {e}")
        return 1
    for c in cadenas:
        print("Cadena aplanada: " + " -> ".join(c))
    texto_a, texto_b = generar(final, a.nuevo)
    out = Path(a.salida)
    out.mkdir(exist_ok=True)
    (out / "a.htaccess").write_text(texto_a, encoding="utf-8")
    (out / "b.htaccess").write_text(texto_b, encoding="utf-8")
    print(f"{len(final)} redirecciones antiguas -> {out / 'a.htaccess'} y {out / 'b.htaccess'}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
