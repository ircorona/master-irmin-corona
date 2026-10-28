"""Tamaño de muestra y margen de error, con la fórmula de las calculadoras de clase.

Uso:
  python muestra.py                          comprueba los ejemplos de SurveyMonkey y muestra casos SEO
  python muestra.py tamano N [conf] [margen]  tamaño de muestra (conf en %, margen en %)
  python muestra.py margen exitos n [conf]    margen de error de una tasa medida (p. ej. leads/sesiones)

Fórmula (SurveyMonkey, Raosoft, Qualtrics usan la misma):
  n0 = z² · p(1−p) / e²          (población infinita, p = 0,5 en el peor caso)
  n  = n0 / (1 + n0 / N)         (corrección por población finita)
"""
import math
import sys

Z = {80: 1.28, 85: 1.44, 90: 1.65, 95: 1.96, 99: 2.58}   # tabla de SurveyMonkey


def tamano(poblacion, confianza=95, margen=5, p=0.5):
    z, e = Z[confianza], margen / 100
    n0 = z * z * p * (1 - p) / (e * e)
    return math.ceil(n0 / (1 + n0 / poblacion))


def margen_error(exitos, n, confianza=95):
    p = exitos / n
    return p, Z[confianza] * math.sqrt(p * (1 - p) / n)


def comprobar():
    casos = [  # (población, confianza, margen, esperado según SurveyMonkey)
        (500_000, 95, 5, 384),   # ejemplo 1: repartidores de una ciudad
        (300, 80, 10, 37),       # ejemplo 2: encuesta interna de empresa
    ]
    fallos = 0
    print("Ejemplos de SurveyMonkey")
    for N, c, m, esperado in casos:
        n = tamano(N, c, m)
        ok = n == esperado
        fallos += not ok
        print(f"  N={N:>7,}  {c}%  ±{m}%  -> {n:>4}  (esperado {esperado})  {'OK' if ok else 'FALLO'}")

    print("\nLa población casi no importa a partir de unos miles (95 %, ±5 %)")
    for N in (100, 1_000, 10_000, 100_000, 2_579_496):
        print(f"  N={N:>9,} -> n={tamano(N):>4}")

    print("\nExtrapolar de muestras pequeñas: la misma tasa del 7,5 % con distinto volumen")
    for exitos, n in ((3, 40), (30, 400), (300, 4_000), (3_000, 40_000)):
        p, me = margen_error(exitos, n)
        print(f"  {exitos:>5} leads / {n:>6,} sesiones = {p:.1%} ± {me:.1%}  "
              f"-> entre {max(p - me, 0):.1%} y {p + me:.1%}")

    print(f"\n{len(casos) - fallos}/{len(casos)} comprobaciones OK")
    return 1 if fallos else 0


def main(args):
    if not args:
        return comprobar()
    if args[0] == "tamano":
        N = int(args[1])
        conf = int(args[2]) if len(args) > 2 else 95
        mar = float(args[3]) if len(args) > 3 else 5
        print(tamano(N, conf, mar))
    elif args[0] == "margen":
        exitos, n = int(args[1]), int(args[2])
        conf = int(args[3]) if len(args) > 3 else 95
        p, me = margen_error(exitos, n, conf)
        print(f"{p:.2%} ± {me:.2%} ({conf} %)")
    else:
        print(__doc__)
        return 2
    return 0


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    sys.exit(main(sys.argv[1:]))
