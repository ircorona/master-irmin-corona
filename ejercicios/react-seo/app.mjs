// Una página de producto en React 19, sin JSX ni compilador: createElement a mano.
// El <title>, la <meta> y el <link rel="canonical"> van DENTRO del componente:
// React 19 los sube al <head> él solo.
import { createElement as h, Suspense, use } from 'react';

// Simula una API lenta (las reseñas tardan 300 ms)
let promesaResenas;
function cargarResenas() {
  promesaResenas ??= new Promise(r => setTimeout(() => r(['Muy buena', 'Llegó rápido']), 300));
  return promesaResenas;
}

function Resenas() {
  const resenas = use(cargarResenas()); // suspende hasta que llegan
  return h('ul', { id: 'resenas' }, resenas.map(t => h('li', { key: t }, t)));
}

export function Producto() {
  return h('main', null,
    h('title', null, 'Zapatillas de trail | Tienda'),
    h('meta', { name: 'description', content: 'Zapatillas de trail con suela de 5 mm.' }),
    h('link', { rel: 'canonical', href: 'https://tienda.example/zapatillas-trail/' }),
    h('h1', null, 'Zapatillas de trail'),
    h('p', null, 'Suela de 5 mm, 280 g.'),
    h('nav', null,
      h('a', { href: '/categoria/trail/' }, 'Más de trail'),
      h('button', { onClick: () => {} }, 'Ver más (botón, no enlace)')),
    h(Suspense, { fallback: h('p', { id: 'cargando' }, 'Cargando reseñas...') },
      h(Resenas)));
}

export function Documento({ children }) {
  return h('html', { lang: 'es' },
    h('head', null, h('meta', { charSet: 'utf-8' })),
    h('body', null, h('div', { id: 'root' }, children)));
}
