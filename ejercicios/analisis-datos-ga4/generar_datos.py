"""Genera datos-ejemplo.csv: una exportación FICTICIA de GA4 (mes x país x dispositivo).

Los datos son inventados. Llevan a propósito los fallos que la clase 02 manda
limpiar: un pico de bots, una fila duplicada, una etiqueta mal escrita, un valor
faltante, una fila incoherente y filas de otro país.
"""
import csv
import random
from pathlib import Path

random.seed(2026)
MESES = [f"{a}-{m:02d}" for a, m in [(2025, 11), (2025, 12)] + [(2026, m) for m in range(1, 11)]]
BASE = {"mobile": 140_000, "desktop": 60_000, "tablet": 6_000}   # usuarios/mes
TASA_LEAD = {"mobile": 0.004, "desktop": 0.009, "tablet": 0.005}  # leads por sesión

filas = []
for mes in MESES:
    for pais in ("Colombia", "Mexico"):
        escala = 1.0 if pais == "Colombia" else 0.3
        for disp, base in BASE.items():
            usuarios = int(base * escala * random.uniform(0.85, 1.15))
            sesiones = int(usuarios * random.uniform(1.25, 1.45))
            con_interaccion = int(sesiones * random.uniform(0.55, 0.65))
            leads = sum(1 for _ in range(sesiones // 50) if random.random() < TASA_LEAD[disp] * 50)
            filas.append([mes, pais, disp, usuarios, sesiones, con_interaccion, leads])

def fila(mes, pais, disp):
    return next(f for f in filas if f[:3] == [mes, pais, disp])

# 1. Pico de bots: marzo, escritorio, muchas sesiones sin interacción y sin leads
bot = fila("2026-03", "Colombia", "desktop")
bot[3] *= 5
bot[4] *= 5
bot[5] = int(bot[4] * 0.02)
# 2. Fila duplicada (exportación pegada dos veces)
filas.append(list(fila("2026-06", "Colombia", "mobile")))
# 3. Etiqueta inconsistente
fila("2026-08", "Colombia", "tablet")[2] = "Tablet "
# 4. Valor faltante
fila("2026-09", "Colombia", "mobile")[6] = ""
# 5. Incoherente: más sesiones con interacción que sesiones
inc = fila("2026-10", "Colombia", "desktop")
inc[5] = inc[4] + 1000

salida = Path(__file__).with_name("datos-ejemplo.csv")
with salida.open("w", newline="", encoding="utf-8") as f:
    w = csv.writer(f)
    w.writerow(["mes", "pais", "dispositivo", "usuarios_totales", "sesiones",
                "sesiones_con_interaccion", "leads"])
    w.writerows(filas)
print(f"{salida.name}: {len(filas)} filas")
