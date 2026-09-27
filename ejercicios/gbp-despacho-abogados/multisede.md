# Ejercicio III: estrategia multisede con anidamiento de fichas

**Ejercicio:** SEO local — La tarea III
**Enunciado:** *«Seguimos con Pérez & Salamero Abogados. El despacho ha crecido y se van a incorporar dos nuevas sedes en capitales de provincia. Vistas las limitaciones descubiertas en el ejercicio anterior: plantea una estrategia multisede que permita maximizar la relevancia temática.»*
**Aviso del enunciado:** *«Sólo piensa. La estrategia en este caso va a ser el 80 % del éxito del proyecto.»*
**Fecha:** 16 de agosto de 2026

> Continúa la [ficha del ejercicio I](ficha.md). Datos inventados; decisiones de estructura reales.

---

## 1. La limitación heredada

Del ejercicio anterior, el diagnóstico del profesor:

> *No podemos utilizar una categoría principal concreta al no ser un despacho especializado en una rama del derecho, y con esto perdemos relevancia para búsquedas específicas. Las categorías secundarias no tienen tanta relevancia que la principal, y es utópico querer posicionar para especialidades del derecho con ellas.*

**Una ficha, una categoría principal.** Y ahora el problema se multiplica por tres:

| | Con una sede | Con tres sedes |
|---|---|---|
| Categorías principales disponibles | **1** | **3** — pero una por ciudad, no una por especialidad |
| Consultas que se pierden | «abogado penalista en Valencia» | Lo mismo **en tres ciudades** |

Abrir sedes **no resuelve nada de la relevancia temática**: solo replica el mismo techo en tres sitios.

## 2. La solución: anidamiento de fichas

> **La idea:** montamos la **ficha madre** de Pérez & Salamero Abogados con las **categorías generales**, y luego abrimos **una ficha personal para cada letrado** poniendo **su especialidad en la categoría principal**.

Es la vía de las **fichas de profesional individual** de la [clase 03](../../knowledge/seo-local/03-gbp-una-nueva-ficha.md), llevada a escala. Y le da la vuelta al problema:

> **La relevancia temática deja de estar en las categorías secundarias del bufete y pasa a estar en la categoría PRINCIPAL de cada letrado.**

Cada abogado es, a efectos de Google, **un negocio con una sola especialidad** — exactamente el tipo de ficha que sí puede competir por «abogado penalista en Valencia».

### Las tres reglas del enunciado

| Regla | Detalle |
|---|---|
| **Dirección** | *Las fichas deben registrarse en la dirección del despacho **en que trabaje el profesional***. No todas en la sede central: cada letrado, en la suya |
| **Teléfono** | ***Números diferentes para cada una*** — tanto para los despachos como para los profesionales. Sin excepción |
| **Categoría** | La **especialidad** como principal. Y **sin solaparse** con la del bufete ([clase 04](../../knowledge/seo-local/04-factores-de-posicionamiento.md)): si coinciden, Google filtra una de las dos |

### El anidamiento propiamente dicho

> *El anidamiento de las fichas se hace **sugiriendo una modificación de los datos de la ficha desde fuera de GBP**, indicando que el negocio a modificar **se encuentra dentro de otra ubicación**. Esto suele hacerse en negocios que están dentro de grandes superficies como un centro comercial.*

**Es la relación «Located in» del pantallazo de McDonald's** de la [clase 06](../../knowledge/seo-local/06-marcado-de-datos.md) —`containedInPlace` en schema— aplicada a algo para lo que no se inventó: **meter las fichas de los letrados «dentro» de la ficha del despacho.**

```
Pérez & Salamero Abogados — Valencia   ← ficha madre (Bufete de abogados)
   └─ Located in ──┬── Ricardo Pérez, abogado      (Abogado penalista)
                   ├── Lucía Salamero, abogada     (Abogado de familia)
                   └── …
```

**Qué se gana:** las fichas hijas **heredan contexto** de la madre y quedan explícitamente asociadas a ella, en vez de parecer siete negocios distintos amontonados en el mismo portal.

## 3. La matriz de fichas

Tres capitales de provincia de la Comunitat Valenciana, coherentes con la sede original:

| Sede | Ficha madre · categoría principal | Fichas de profesional · categoría principal |
|---|---|---|
| **València** *(central)* | Pérez & Salamero Abogados — **Bufete de abogados** | **Ricardo Pérez** — Abogado penalista<br>**Lucía Salamero** — Abogado de familia<br>**Marta Gil** — Abogado laboralista |
| **Alacant** | Pérez & Salamero Abogados Alicante — **Bufete de abogados** | **Javier Ruiz** — Abogado mercantil<br>**Nuria Bonet** — Abogado de extranjería |
| **Castelló** | Pérez & Salamero Abogados Castellón — **Bufete de abogados** | **Álvaro Mena** — Abogado de accidentes de tráfico<br>**Elena Prats** — Abogado civil |

**3 fichas madre + 7 de profesional = 10 fichas**, y por tanto **10 números de teléfono distintos**.

### La cobertura que se consigue

| Consulta | Antes | Ahora |
|---|---|---|
| «bufete de abogados en Valencia» | ✅ ficha madre | ✅ ficha madre |
| «abogado penalista en Valencia» | ❌ categoría secundaria | ✅ **ficha de Ricardo Pérez** |
| «abogado mercantil en Alicante» | ❌ no existía sede | ✅ **ficha de Javier Ruiz** |
| «abogado de tráfico en Castellón» | ❌ | ✅ **ficha de Álvaro Mena** |

**De 1 categoría principal a 10.** Ese es el rendimiento de la estrategia, y por eso el enunciado dice que es el 80 % del proyecto.

## 4. La arquitectura web que lo sostiene

> *Es necesario modificar la arquitectura de la web para adaptarla al sistema multisede mediante URL de localización.*

Se pasa de **sede única** a **multiubicación + E-E-A-T** ([clase 05](../../knowledge/seo-local/05-arquitectura-local.md)):

```
/                                    Home → SOLO marca
├── /despachos/                      hub de ubicaciones
│   ├── /despachos/valencia/         location page  ← ficha madre València
│   ├── /despachos/alicante/         location page  ← ficha madre Alacant
│   └── /despachos/castellon/        location page  ← ficha madre Castelló
├── /equipo/                         hub de profesionales
│   ├── /equipo/ricardo-perez/       ← ficha GBP de Ricardo Pérez
│   ├── /equipo/lucia-salamero/      ← ficha GBP de Lucía Salamero
│   └── …                            una por letrado
├── /servicios/
│   ├── /servicios/derecho-penal/
│   ├── /servicios/derecho-de-familia/
│   └── …
├── /sobre-nosotros/
├── /contacto/
└── /blog/
```

**La home deja de pelear por «abogados en Valencia».** Con tres sedes ya no puede: pasa a ser página de marca, y cada consulta geográfica se resuelve en su página de ubicación.

### La página de autor, que es la pieza clave

> *La URL (web) de las fichas de los profesionales debe apuntar a **la de autor modificada** para que incluya la información del negocio hijo (datos del profesional, teléfono, etc.) y **aprovechemos la autoridad a nivel de E-E-A-T**.*

Es la idea más fina del enunciado: **`/equipo/ricardo-perez/` deja de ser una biografía y pasa a ser, a la vez, una *location page* en miniatura.**

| Como página de autor (**E-E-A-T**) | Como *location page* del negocio hijo |
|---|---|
| Formación y colegiación | **NAP con SU teléfono**, no el del despacho |
| Áreas de práctica | Dirección de **su** sede |
| Publicaciones y ponencias | Horario de atención |
| Casos y trayectoria | **Enlace a su ficha de GBP** |
| Artículos firmados en el blog | Mapa embebido |
| Enlace a su ficha del ICAV | Botón de cita |

**Y ahí está el doble rendimiento:** la autoridad que acumula como autor —firmando artículos, citado en prensa jurídica— **cae sobre una página que también es el destino de una ficha de GBP**. En un sector YMYL, eso no es un extra: es el activo.

### El punto delicado: las páginas de servicio × sede

Aquí es donde esta estrategia se rompe si se hace por volumen. La tentación es:

```
3 sedes × 8 servicios = 24 páginas «derecho penal en Alicante»…
```

**No.** Serían 24 páginas casi idénticas compitiendo entre sí — canibalización de manual ([descubrir-contenido-canibalizado](../../knowledge/descubrir-contenido-canibalizado/)), y detectable en un minuto con los *embeddings* de Screaming Frog ([herramientas 07](../../knowledge/herramientas/07-screaming-frog-analisis-del-rastreo.md)).

**El criterio que sí funciona, y que además es honesto:**

> **Solo se crea la página «servicio + ciudad» donde haya un letrado real de esa especialidad en esa sede.**

| Página | ¿Se crea? | Por qué |
|---|---|---|
| `/despachos/valencia/derecho-penal/` | ✅ | Ricardo Pérez está allí |
| `/despachos/alicante/derecho-penal/` | ❌ | **No hay penalista en Alicante** |
| `/despachos/alicante/derecho-mercantil/` | ✅ | Javier Ruiz está allí |

Con eso, **cada página sede×servicio tiene contenido único de verdad**: una persona con nombre, cara, teléfono, colegiación y casos. Y el enlazado interno queda como la [clase 05](../../knowledge/seo-local/05-arquitectura-local.md): **cada servicio enlaza solo a las ubicaciones donde se presta.**

**Siete páginas sede×servicio en vez de veinticuatro.** Menos, pero cada una defendible.

## 5. El marcado de datos

Aplicando los conectores de la [clase 06](../../knowledge/seo-local/06-marcado-de-datos.md), el grafo completo:

```
Organization (Pérez & Salamero Abogados)          ← la home
   │ subOrganization
   ├──▶ LegalService València    ──employee──▶ Person Ricardo Pérez
   │                                          ──employee──▶ Person Lucía Salamero
   ├──▶ LegalService Alacant     ──employee──▶ Person Javier Ruiz
   └──▶ LegalService Castelló    ──employee──▶ Person Álvaro Mena
             │ knowsAbout
             ▼
        Service (Derecho penal)  ──subjectOf──▶ FAQPage
```

**La home** — solo `Organization`, sin dirección ni horario:

```json
{ "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://perezsalamero.es/#organization",
  "name": "Pérez & Salamero Abogados",
  "legalName": "PEREZ Y SALAMERO ABOGADOS SLP",
  "taxID": "B98765432",
  "url": "https://perezsalamero.es/",
  "areaServed": { "@type": "AdministrativeArea", "name": "Comunidad Valenciana" },
  "subOrganization": [
    { "@id": "https://perezsalamero.es/despachos/valencia/#LocalBusiness" },
    { "@id": "https://perezsalamero.es/despachos/alicante/#LocalBusiness" },
    { "@id": "https://perezsalamero.es/despachos/castellon/#LocalBusiness" }
  ] }
```

**Cada página de ubicación** — `LegalService` con su NAP propio:

```json
{ "@type": "LegalService",
  "@id": "https://perezsalamero.es/despachos/alicante/#LocalBusiness",
  "name": "Pérez & Salamero Abogados Alicante",
  "parentOrganization": { "@id": "https://perezsalamero.es/#organization" },
  "telephone": "+34965XXXXXX",
  "address": { "@type": "PostalAddress", "addressLocality": "Alacant", "postalCode": "03001", "addressCountry": "ES" },
  "geo": { "@type": "GeoCoordinates", "latitude": 38.3452, "longitude": -0.4815 },
  "hasMap": "https://www.google.com/maps?cid=CID_FICHA_ALICANTE",
  "employee": [ { "@id": "https://perezsalamero.es/equipo/javier-ruiz/#person" } ] }
```

**Cada página de autor** — `Person` **y** los datos del negocio hijo:

```json
{ "@type": "Person",
  "@id": "https://perezsalamero.es/equipo/javier-ruiz/#person",
  "name": "Javier Ruiz",
  "jobTitle": "Abogado mercantil",
  "telephone": "+34965YYYYYY",
  "worksFor": { "@id": "https://perezsalamero.es/despachos/alicante/#LocalBusiness" },
  "workLocation": { "@id": "https://perezsalamero.es/despachos/alicante/#LocalBusiness" },
  "knowsAbout": ["Derecho mercantil", "Derecho concursal", "Contratación entre empresas"],
  "alumniOf": { "@type": "CollegeOrUniversity", "name": "Universitat d'Alacant" },
  "memberOf": { "@type": "Organization", "name": "Ilustre Colegio de Abogados de Alicante" },
  "sameAs": ["https://www.linkedin.com/in/javier-ruiz-abogado"] }
```

> **Y aquí se cumplen las cuatro indicaciones básicas de la clase 06:** nada de reinos de Taifas —todo unido por `@id`—, el tipo más específico (`LegalService`), **marcado distinto en cada URL**, y todos los datos visibles en la página.

## 6. Los riesgos, dichos claramente

Esta estrategia es **avanzada**, y el propio enunciado la califica así. Lo que hay que saber antes de vendérsela a un cliente:

| Riesgo | Detalle | Mitigación |
|---|---|---|
| **10 teléfonos distintos** | Es el cuello de botella real, y **operativo antes que técnico**: alguien tiene que contestarlos | Numeración directa (DDI) sobre la centralita: números geográficos reales, extensión interna |
| **El anidamiento no está garantizado** | Se pide **sugiriendo una edición desde fuera de GBP**. Google puede no aprobarla, o revertirla | La estrategia debe funcionar **aunque el anidamiento no cuaje**: las fichas de profesional valen por sí solas |
| **Suspensión por fichas en la misma dirección** | Google admite fichas de profesional, pero **solo si son de cara al público y localizables allí** ([clase 03](../../knowledge/seo-local/03-gbp-una-nueva-ficha.md)) | **Nada de fichas para administrativos ni para letrados que no atienden.** Y horario real de atención de cada uno |
| **Solapamiento de categorías** | Si un letrado usa la misma categoría que su sede, **Google filtra una** | El bufete, «Bufete de abogados»; cada letrado, **una especialidad distinta** |
| **Canibalización sede×servicio** | 24 páginas casi iguales | Solo donde hay profesional real. Verificar con *embeddings* |
| **Si un letrado se va** | Se queda una ficha huérfana con reseñas | Protocolo de baja: cerrar ficha, **301** de su página de autor a la de su sustituto o a la de la sede |

> **Sobre la técnica del anidamiento:** las fichas de profesional individual **están expresamente permitidas** por las directrices de Google. Lo que no está documentado es **usar la relación «located in» para anidarlas** — es un uso oportunista de una función pensada para inquilinos de centros comerciales. **No es black hat, pero no es un mecanismo soportado**, así que no debe ser la pata sobre la que se apoye el proyecto.

## 7. Plan de ejecución

| Fase | Qué | Antes de pasar a la siguiente |
|---|---|---|
| **0. Preparación** | Contratar 10 líneas. Rótulos en las tres sedes. Correos `@perezsalamero.es` para cada letrado | Sin esto no hay verificación |
| **1. Web primero** | Publicar la arquitectura multisede completa **antes** de tocar GBP: `/despachos/*`, `/equipo/*` y el marcado | **Las fichas apuntan a URLs que deben existir ya** |
| **2. Fichas madre** | Alta y verificación de Alacant y Castelló. Vídeo, previsiblemente | Esperar a que estén verificadas |
| **3. Fichas de profesional** | Una a una, **no las siete el mismo día**. Cada una a su sede y con su teléfono | Un alta masiva desde la misma IP y dirección es la señal que dispara revisiones |
| **4. Anidamiento** | Sugerir la edición «located in» ficha por ficha | Es el paso incierto: se hace al final, cuando lo demás ya funciona |
| **5. Contenido** | Páginas sede×servicio solo donde hay letrado. Artículos **firmados** por cada uno | |
| **6. Reseñas** | Pedirlas **al letrado que atendió**, no al despacho | Es lo que alimenta las justificaciones de reseña de su ficha |

**El orden importa**: la web antes que las fichas, y las fichas de escalonadas.

## 8. Cómo se mide

| Qué | Dónde | Por qué |
|---|---|---|
| Posición por **cuadrícula** alrededor de cada sede | Herramienta de seguimiento local | Recuerda «el gran engaño» de la [clase 04](../../knowledge/seo-local/04-factores-de-posicionamiento.md): comprobar desde el propio despacho no vale |
| Llamadas **por número** | Centralita | Con 10 líneas distintas, **cada ficha es medible por separado**. Es el mejor efecto colateral de la regla del teléfono único |
| Rendimiento **por ficha** | Panel de cada perfil | Search Console no ve Maps |
| Consultas por sede | Search Console, filtrando por carpeta `/despachos/…` | |
| Duplicación semántica | Screaming Frog + *embeddings* | Vigilar las páginas sede×servicio |

> **La regla del teléfono único no es solo un requisito de Google: es lo que convierte el proyecto en medible.** Diez números son diez fuentes de atribución.

---

## Resumen en cinco líneas

1. **Ficha madre por sede** con categoría general, **ficha por letrado** con su especialidad como categoría principal: de 1 categoría principal a 10.
2. **Cada ficha, en la dirección de su sede y con teléfono propio.** Diez líneas.
3. **Anidar las hijas en la madre** con la relación «located in», sabiendo que no está garantizada.
4. **Arquitectura multisede por URL de localización**, y **la página de autor haciendo de location page** del negocio hijo — que es donde se aprovecha el E-E-A-T.
5. **Páginas sede×servicio solo donde hay un profesional real**, o se canibalizan entre ellas.
