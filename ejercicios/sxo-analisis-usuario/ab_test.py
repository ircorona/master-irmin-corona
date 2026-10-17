"""¿Ha ganado de verdad la versión B? Test z de dos proporciones (95 %).

Uso:
    python ab_test.py                          # ejemplos de la clase
    python ab_test.py 10000 300 10000 360      # visitas_a conv_a visitas_b conv_b
"""
import sys
from math import sqrt, erf


def ab_test(visitas_a, conv_a, visitas_b, conv_b):
    pa, pb = conv_a / visitas_a, conv_b / visitas_b
    p = (conv_a + conv_b) / (visitas_a + visitas_b)
    se = sqrt(p * (1 - p) * (1 / visitas_a + 1 / visitas_b))
    z = (pb - pa) / se
    p_valor = 2 * (1 - 0.5 * (1 + erf(abs(z) / sqrt(2))))
    print(f"A: {pa:.2%}  B: {pb:.2%}  mejora: {(pb - pa) / pa:+.1%}")
    print(f"z = {z:.2f}  p = {p_valor:.4f}  ->", "B gana (95 %)" if p_valor < 0.05 else "no concluyente: sigue el test")
    return p_valor


if __name__ == "__main__":
    if len(sys.argv) == 5:
        ab_test(*(int(x) for x in sys.argv[1:]))
    else:
        ab_test(10000, 300, 10000, 360)
        ab_test(1000, 30, 1000, 36)
