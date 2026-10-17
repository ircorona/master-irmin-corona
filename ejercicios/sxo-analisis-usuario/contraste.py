"""Contraste WCAG 2.2 entre dos colores.

Uso:
    python contraste.py                      # ejemplos de la clase
    python contraste.py "#777777" "#ffffff"  # texto y fondo propios
"""
import sys


def luminancia(hex_color):
    h = hex_color.lstrip("#")
    if len(h) == 3:
        h = "".join(c * 2 for c in h)
    canales = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        canales.append(c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4)
    r, g, b = canales
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def ratio(texto, fondo):
    l1, l2 = sorted((luminancia(texto), luminancia(fondo)), reverse=True)
    return (l1 + 0.05) / (l2 + 0.05)


def contraste(texto, fondo):
    r = ratio(texto, fondo)
    veredicto = "AA ok" if r >= 4.5 else ("solo texto grande" if r >= 3 else "NO pasa")
    print(f"{texto} sobre {fondo}: {r:.2f}:1  ->  {veredicto}")
    return r


if __name__ == "__main__":
    if len(sys.argv) == 3:
        contraste(sys.argv[1], sys.argv[2])
    else:
        contraste("#777777", "#ffffff")
        contraste("#767676", "#ffffff")
        contraste("#ffffff", "#f7ca00")
        contraste("#111111", "#f7ca00")
