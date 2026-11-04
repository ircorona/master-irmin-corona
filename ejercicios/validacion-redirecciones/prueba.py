"""Levanta el servidor de pre en tres estados y comprueba el resultado de validar.py.

Uso: python prueba.py [--puerto 8134]

  1. roto + transversal           datos/             fallos del mapa: no hay OK
  2. arreglado + transversal      datos-arreglados/  100 % pero con cadenas: no hay OK
  3. arreglado + específico       datos-arreglados/  100 % sin avisos: OK

Sale con 0 si los tres escenarios dan lo esperado.
"""
import argparse
import re
import socket
import subprocess
import sys
import time
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

AQUI = Path(__file__).parent
ESCENARIOS = [
    # estado, orden, carpeta, hoja Sí, script Sí, avisos, robots, código de salida
    ("roto", "transversal", "datos", 6, 4, 3, 1, 1),
    ("arreglado", "transversal", "datos-arreglados", 11, 11, 2, 0, 1),
    ("arreglado", "especifico", "datos-arreglados", 11, 11, 0, 0, 0),
]


def esperar(puerto):
    for _ in range(50):
        with socket.socket() as s:
            s.settimeout(0.2)
            if s.connect_ex(("127.0.0.1", puerto)) == 0:
                return
        time.sleep(0.1)
    raise SystemExit("el servidor no arranca")


def escenario(puerto, estado, orden, carpeta, hoja, script, avisos, robots, codigo):
    servidor = subprocess.Popen(
        [sys.executable, str(AQUI / "servidor.py"), "--puerto", str(puerto),
         "--estado", estado, "--orden", orden],
        stdout=subprocess.DEVNULL,
    )
    try:
        esperar(puerto)
        r = subprocess.run(
            [sys.executable, str(AQUI / "validar.py"), str(AQUI / carpeta),
             "--servidor", f"127.0.0.1:{puerto}"],
            capture_output=True, text=True, encoding="utf-8",
        )
    finally:
        servidor.terminate()
        servidor.wait()

    print(f"\n######## {estado} + {orden} ({carpeta})")
    print(r.stdout, end="")
    if r.stderr:
        print(r.stderr)
    total = int(re.search(r"Comprobadas \d+ de (\d+)", r.stdout).group(1))
    medido = (
        int(re.search(r"Hoja:\s+(\d+) Sí", r.stdout).group(1)),
        int(re.search(r"Script:\s+(\d+) Sí", r.stdout).group(1)),
        int(re.search(r"Con avisos: (\d+)", r.stdout).group(1)),
        int(re.search(r"bloqueante: (\d+)", r.stdout).group(1)),
        r.returncode,
    )
    esperado = (hoja, script, avisos, robots, codigo)
    bien = medido == esperado and total == 11
    print(f"   esperado {esperado}, medido {medido}: {'correcto' if bien else 'FALLO'}")
    return bien


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--puerto", type=int, default=8134)
    puerto = p.parse_args().puerto
    bien = sum(escenario(puerto, *e) for e in ESCENARIOS)
    print(f"\nEscenarios correctos: {bien} de {len(ESCENARIOS)}")
    return 0 if bien == len(ESCENARIOS) else 1


if __name__ == "__main__":
    sys.exit(main())
