"""Aplica la plantilla de la clase 02 a una exportación de GA4 (mes x país x dispositivo).

Uso:  python analizar.py [fichero.csv]      (por defecto datos-ejemplo.csv)

Pasos, cada uno con su recuento de filas:
  1. Normalizar etiquetas      4. Valores faltantes (imputación)
  2. Filtrar la población      5. Coherencia (validación)
  3. Quitar duplicados         6. Atípicos: picos de bots
Después: moda del dispositivo y tasa de leads por dispositivo con su error,
calculadas con los datos sucios y con los limpios para ver el sesgo.
"""
import csv
import math
import statistics
import sys
from pathlib import Path

PAIS = "Colombia"
COLUMNAS_NUM = ("usuarios_totales", "sesiones", "sesiones_con_interaccion", "leads")


def cargar(ruta):
    with open(ruta, encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f))


def a_numero(filas):
    for f in filas:
        for c in COLUMNAS_NUM:
            f[c] = int(f[c]) if f[c].strip() else None
    return filas


def paso(nombre, antes, despues, detalle=""):
    print(f"  {nombre:<28} {len(antes):>3} -> {len(despues):>3} filas  {detalle}")
    return despues


def limpiar(filas):
    print("LIMPIEZA")
    n_inicial = len(filas)
    quitadas = 0

    # 1. Normalizar etiquetas: "Tablet " y "tablet" son la misma categoría
    cambiadas = sum(1 for f in filas if f["dispositivo"] != f["dispositivo"].strip().lower())
    for f in filas:
        f["dispositivo"] = f["dispositivo"].strip().lower()
    paso("1 etiquetas normalizadas", filas, filas, f"({cambiadas} corregida/s)")

    # 2. Población: solo el país del estudio
    nuevas = [f for f in filas if f["pais"] == PAIS]
    quitadas += len(filas) - len(nuevas)
    filas = paso("2 filtro país", filas, nuevas, f"(fuera {len(filas) - len(nuevas)} de otros países)")

    # 3. Duplicados exactos: misma combinación mes + dispositivo
    vistas, nuevas = set(), []
    for f in filas:
        clave = (f["mes"], f["dispositivo"])
        if clave not in vistas:
            vistas.add(clave)
            nuevas.append(f)
    quitadas += len(filas) - len(nuevas)
    filas = paso("3 duplicados", filas, nuevas, f"(fuera {len(filas) - len(nuevas)})")

    # 4. Faltantes: se imputa con la tasa mediana del mismo dispositivo
    faltan = [f for f in filas if f["leads"] is None]
    for f in faltan:
        tasas = [g["leads"] / g["sesiones"] for g in filas
                 if g["dispositivo"] == f["dispositivo"] and g["leads"] is not None]
        f["leads"] = round(statistics.median(tasas) * f["sesiones"])
        f["imputado"] = True
        print(f"    imputado {f['mes']} {f['dispositivo']}: leads = {f['leads']}")
    paso("4 faltantes imputados", filas, filas, f"({len(faltan)} imputado/s)")

    # 5. Coherencia: no puede haber más sesiones con interacción que sesiones
    nuevas = [f for f in filas if f["sesiones_con_interaccion"] <= f["sesiones"]]
    for f in filas:
        if f not in nuevas:
            print(f"    incoherente {f['mes']} {f['dispositivo']}: "
                  f"{f['sesiones_con_interaccion']} con interacción > {f['sesiones']} sesiones")
    quitadas += len(filas) - len(nuevas)
    filas = paso("5 coherencia", filas, nuevas, f"(fuera {len(filas) - len(nuevas)})")

    # 6. Atípicos: tasa de interacción por debajo de la mitad de la mediana del dispositivo
    nuevas = []
    for f in filas:
        tasas = [g["sesiones_con_interaccion"] / g["sesiones"] for g in filas
                 if g["dispositivo"] == f["dispositivo"]]
        tasa = f["sesiones_con_interaccion"] / f["sesiones"]
        if tasa < statistics.median(tasas) / 2:
            print(f"    atípico {f['mes']} {f['dispositivo']}: interacción {tasa:.1%} "
                  f"(mediana {statistics.median(tasas):.1%}), {f['sesiones']:,} sesiones")
        else:
            nuevas.append(f)
    quitadas += len(filas) - len(nuevas)
    filas = paso("6 atípicos (bots)", filas, nuevas, f"(fuera {len(filas) - len(nuevas)})")

    # Recuento final esperado frente a real
    esperado = n_inicial - quitadas
    estado = "OK" if esperado == len(filas) else "DESCUADRE"
    print(f"  recuento final: esperado {esperado}, real {len(filas)} -> {estado}")
    if estado != "OK":
        sys.exit(1)
    return filas


def resumen(titulo, filas):
    print(titulo)
    por_disp = {}
    for f in filas:
        d = por_disp.setdefault(f["dispositivo"], {"sesiones": 0, "leads": 0})
        d["sesiones"] += f["sesiones"]
        d["leads"] += f["leads"] or 0
    total = sum(d["sesiones"] for d in por_disp.values())
    moda = max(por_disp, key=lambda k: por_disp[k]["sesiones"])
    print(f"  moda del dispositivo: {moda} ({por_disp[moda]['sesiones'] / total:.1%} de las sesiones)")
    print(f"  {'dispositivo':<10} {'sesiones':>10} {'leads':>6} {'tasa':>7}  IC 95 %")
    for disp, d in sorted(por_disp.items(), key=lambda kv: -kv[1]["sesiones"]):
        p = d["leads"] / d["sesiones"]
        ee = math.sqrt(p * (1 - p) / d["sesiones"])   # error estándar de una proporción
        print(f"  {disp:<10} {d['sesiones']:>10,} {d['leads']:>6} {p:>7.3%}  "
              f"[{p - 1.96 * ee:.3%}, {p + 1.96 * ee:.3%}]")
    return por_disp


def main():
    ruta = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).with_name("datos-ejemplo.csv")
    crudas = a_numero(cargar(ruta))
    print(f"{ruta.name}: {len(crudas)} filas cargadas\n")

    sucias = [dict(f) for f in crudas if f["pais"] == PAIS]
    resumen(f"SIN LIMPIAR (solo filtro {PAIS})", sucias)
    print()
    limpias = limpiar([dict(f) for f in crudas])
    print()
    resumen("LIMPIO", limpias)

    suma_mensual = sum(f["usuarios_totales"] for f in limpias)
    print(f"\nAVISO: la suma de usuarios mensuales es {suma_mensual:,}, pero NO son "
          "usuarios del año: quien vuelve en varios meses cuenta una vez por mes. "
          "Los usuarios del periodo se piden a GA4 con el rango de 12 meses, no se suman.")


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    main()
