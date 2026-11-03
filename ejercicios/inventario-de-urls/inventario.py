"""Las dos hojas de la clase en Python: inventario de URLs por tipo y comparación de valores antiguo/nuevo.

Uso:
  python inventario.py                 usa los CSV de ejemplo de datos/
  python inventario.py CARPETA         usa tus propios CSV con los mismos nombres

CSV de entrada (cabecera en la primera fila):
  organicas.csv      url,sesiones      URLs con tráfico orgánico (Search Console o GA4)
  referral.csv       url,sesiones      URLs con tráfico de referencia (GA4)
  todas.csv          url               todas las URLs conocidas (rastreo, sitemaps, logs)
  enlaces.csv        url               URLs con enlaces externos (Search Console, Ahrefs...)
  mapa.csv           antigua,nueva     el mapa de redirecciones
  crawl_antiguo.csv  url,title,description,h1
  crawl_nuevo.csv    url,title,description,h1

1. Inventario («Localizar URLs»): cada URL recibe un tipo por prioridad (orgánico > referral >
   todas > sin catalogar), igual que la fórmula de la clase, más si tiene enlaces. Después se
   comprueba que toda URL con tráfico o enlaces esté en el mapa de redirecciones.
2. Comparación («Scraping de valores»): para cada par del mapa, title, description y h1 antiguos
   frente a nuevos.
Las comparaciones distinguen mayúsculas, como las URLs. Google Sheets no: VLOOKUP y «=» ignoran
mayúsculas, y el script avisa de cada caso en que la hoja de la clase habría dado otro resultado.
Sale con código 0 si ha podido leer todos los CSV y 1 si falta alguno.
"""
import csv
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

TIPOS = ["URL con tráfico orgánico", "URL Referral", "URL sin tráfico orgánico ni referral", "Sin catalogar"]
CAMPOS = ["title", "description", "h1"]


def leer(carpeta, nombre):
    with open(carpeta / nombre, encoding="utf-8", newline="") as f:
        return list(csv.DictReader(f))


def inventario(organicas, referral, todas, enlaces):
    org = {f["url"]: int(f["sesiones"]) for f in organicas}
    ref = {f["url"]: int(f["sesiones"]) for f in referral}
    tod = {f["url"] for f in todas}
    enl = {f["url"] for f in enlaces}
    urls = list(dict.fromkeys([*org, *ref, *(f["url"] for f in todas), *(f["url"] for f in enlaces)]))
    filas = []
    for u in urls:
        tipo = TIPOS[0] if u in org else TIPOS[1] if u in ref else TIPOS[2] if u in tod else TIPOS[3]
        filas.append((u, tipo, u in enl, org.get(u, 0) + ref.get(u, 0)))
    return filas


def colisiones(urls):
    """URLs que solo se diferencian en mayúsculas: Sheets las trataría como la misma."""
    vistas = {}
    for u in urls:
        vistas.setdefault(u.lower(), []).append(u)
    return [g for g in vistas.values() if len(g) > 1]


def main(carpeta):
    try:
        datos = {n: leer(carpeta, f"{n}.csv") for n in
                 ["organicas", "referral", "todas", "enlaces", "mapa", "crawl_antiguo", "crawl_nuevo"]}
    except FileNotFoundError as e:
        print(f"FALLO: {e}")
        return 1

    print("== 1. Inventario (Localizar URLs)")
    filas = inventario(datos["organicas"], datos["referral"], datos["todas"], datos["enlaces"])
    print(f"   {'URL':48} {'Tipo':38} {'Enlaces':8} Sesiones")
    for u, tipo, enl, ses in filas:
        print(f"   {u:48} {tipo:38} {'Sí' if enl else 'No':8} {ses}")
    for g in colisiones([f[0] for f in filas]):
        print(f"   ! Solo cambian mayúsculas: {' / '.join(g)}. Son URLs distintas; en Sheets, VLOOKUP las confunde")

    mapeadas = {f["antigua"] for f in datos["mapa"]}
    importantes = [f for f in filas if f[1] in TIPOS[:2] or f[2]]
    sin_mapa = [f for f in importantes if f[0] not in mapeadas]
    print(f"\n   Con tráfico o enlaces: {len(importantes)}. En el mapa de redirecciones: {len(importantes) - len(sin_mapa)}")
    for u, tipo, enl, ses in sin_mapa:
        motivo = tipo if tipo in TIPOS[:2] else "tiene enlaces"
        print(f"   ! Falta redirección: {u}  ({motivo}{', ' + str(ses) + ' sesiones' if ses else ''})")

    print("\n== 2. Comparación de valores (Scraping de valores)")
    antiguo = {f["url"]: f for f in datos["crawl_antiguo"]}
    nuevo = {f["url"]: f for f in datos["crawl_nuevo"]}
    distintos = engano = 0
    for par in datos["mapa"]:
        a, n = antiguo.get(par["antigua"]), nuevo.get(par["nueva"])
        if not a or not n:
            print(f"   ! Sin rastrear: {par['antigua'] if not a else par['nueva']}")
            continue
        for campo in CAMPOS:
            va, vn = a[campo].strip(), n[campo].strip()
            if va == vn:
                continue
            distintos += 1
            if va.lower() == vn.lower():
                engano += 1
                nota = "solo cambian mayúsculas: Sheets diría «Coincide»"
            elif not vn:
                nota = "vacío en la web nueva"
            else:
                nota = "No coincide"
            print(f"   {par['nueva']:40} {campo:12} «{va}» -> «{vn}»  ({nota})")
    print(f"\n   Pares comparados: {len(datos['mapa'])}. Valores distintos: {distintos}"
          f" (de ellos, {engano} que Sheets daría por iguales)")
    return 0


if __name__ == "__main__":
    sys.exit(main(Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).parent / "datos"))
