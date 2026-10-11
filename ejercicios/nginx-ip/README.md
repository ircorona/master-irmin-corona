# Bloqueo por IP en nginx

Clase 19 (NGINX IP) de Servidores (Avanzado): bloquear IPs con `allow` y `deny`.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | El código de clase, tal cual |
| `conf/ejercicio.conf` | Las mismas cuatro IPs bloqueadas de verdad, y `/privado/` solo para la oficina |
| `conf/demos.conf` | Tres demos: herencia, sin realip y la lista con `geo` |
| `prueba.py` | Arranca el nginx 1.27.3 de Laragon y prueba cada caso simulando la IP |

## Cómo se ejecuta

```bash
python prueba.py            # 25 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # y probar: curl -H "X-Real-IP: 216.24.213.200" http://127.0.0.1:8002/
```

Puertos 8001 a 8005. No toca el nginx ni el Apache de Laragon.

Todas las peticiones salen de 127.0.0.1, así que la IP del visitante se simula con la cabecera `X-Real-IP` y el módulo realip (`set_real_ip_from 127.0.0.1`). Es exactamente lo que se configura detrás de Cloudflare o de un proxy.

## El código de clase no bloquea a nadie

```nginx
allow all;
deny 216.24.213.200;
deny 216.24.213.230;
deny 216.24.213.250;
deny 216.24.213.206;
```

nginx lee las reglas de arriba abajo y **se queda con la primera que casa**. `allow all` casa con cualquier IP, así que los `deny` no se llegan a leer. Medido: las cuatro IPs reciben 200.

Viene de Apache 2.2: allí, `Order Allow,Deny` + `Allow from all` + `Deny from …` sí bloquea, porque con ese `Order` el `Deny` gana aunque vaya después. nginx no tiene `Order`: manda el orden de las líneas.

## El ejercicio

```nginx
deny 216.24.213.200;
deny 216.24.213.230;
deny 216.24.213.250;
deny 216.24.213.206;
allow all;

location /privado/ {
    allow 203.0.113.10;
    deny all;
}
```

| Prueba | Resultado |
|---|---|
| Las cuatro IPs | 403 en toda la web, también en `robots.txt` |
| 216.24.213.201 (misma red, fuera de la lista) | 200 |
| Cualquier otra IP | 200 |
| `/privado/` desde la oficina | 200 |
| `/privado/` desde el resto, incluidas las bloqueadas | 403 |

## Lo que enseñan las demos

| Demo | Medido |
|---|---|
| Herencia: el `server` bloquea cuatro IPs y `location /privado/` añade `deny 198.51.100.7` | Las cuatro **entran** en `/privado/`: una `location` con sus propias reglas no hereda las del `server` |
| Sin realip | La IP bloqueada **entra**: nginx ve la IP del proxy, no la del visitante |
| `geo` | Bloquea igual que la lista de `deny`. Es lo que recomienda nginx cuando hay muchas IPs |
