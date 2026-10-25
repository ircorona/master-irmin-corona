// Genera la misma página de producto de cuatro formas y mide qué HTML recibiría
// un bot que no ejecuta JavaScript.
//   CSR          la cáscara que sirve una SPA (Vite por defecto): <div id="root"></div>
//   renderToString   SSR sin streaming: no espera a los datos
//   stream (shell)   renderToPipeableStream cortando en onShellReady
//   stream (allReady) el mismo, esperando a onAllReady («for crawlers»)
//   prerender        react-dom/static, generación estática (SSG)
// Uso: node prueba.mjs
import { createElement as h } from 'react';
import { renderToString, renderToPipeableStream } from 'react-dom/server';
import { prerenderToNodeStream } from 'react-dom/static';
import { Writable } from 'node:stream';
import { Producto, Documento } from './app.mjs';

const pagina = () => h(Documento, null, h(Producto));

const csr = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Tienda</title></head>'
  + '<body><div id="root"></div><script type="module" src="/main.js"></script></body></html>';

function aTexto(conectar) {
  return new Promise(resolve => {
    let html = '';
    const destino = new Writable({ write(trozo, _, cb) { html += trozo; cb(); }, final(cb) { cb(); resolve(html); } });
    conectar(destino);
  });
}

// Corta lo que se haya enviado justo en onShellReady (lo que llega en los primeros bytes)
function streamHasta(evento) {
  return new Promise(resolve => {
    let html = '';
    const destino = new Writable({ write(trozo, _, cb) { html += trozo; cb(); } });
    const { pipe } = renderToPipeableStream(pagina(), {
      [evento]() { pipe(destino); setImmediate(() => resolve(html)); },
    });
  });
}

const variantes = {
  'CSR (SPA)': csr,
  'renderToString': '<!doctype html>' + renderToString(pagina()),
  'stream, onShellReady': await streamHasta('onShellReady'),
  'stream, onAllReady': await streamHasta('onAllReady'),
  'prerender (SSG)': await aTexto(async d => (await prerenderToNodeStream(pagina())).prelude.pipe(d)),
};

const cuenta = (html, re) => (html.match(re) || []).length;
const enHead = (html, re) => re.test(html.split('</head>')[0]);
const filas = [];
for (const [nombre, html] of Object.entries(variantes)) {
  filas.push({
    variante: nombre,
    h1: cuenta(html, /<h1[\s>]/g),
    'title en head': enHead(html, /<title>Zapatillas/),
    'canonical en head': enHead(html, /rel="canonical"/),
    'enlaces <a href>': cuenta(html, /<a href=/g),
    'reseñas': /id="resenas"/.test(html.replace(/<script[\s\S]*?<\/script>/g, '').replace(/<template[\s\S]*?<\/template>/g, '')) || /Muy buena/.test(html),
    'fallback "Cargando"': /Cargando reseñas/.test(html),
  });
}
console.table(filas);

const [csrF, rts, shell, all, pre] = filas;
const checks = {
  'CSR: el bot recibe 0 h1 y 0 enlaces': csrF.h1 === 0 && csrF['enlaces <a href>'] === 0,
  'renderToString: title y canonical subidos al <head>': rts['title en head'] && rts['canonical en head'],
  'renderToString: sin reseñas, solo el fallback': !rts['reseñas'] && rts['fallback "Cargando"'],
  'onShellReady: primeros bytes sin reseñas': !shell['reseñas'],
  'onAllReady: HTML final con reseñas': all['reseñas'],
  'prerender: HTML final con reseñas': pre['reseñas'] && pre['title en head'],
};
let fallos = 0;
for (const [nombre, ok] of Object.entries(checks)) {
  console.log(`${ok ? 'OK   ' : 'FALLO'} ${nombre}`);
  if (!ok) fallos++;
}
console.log(`\n${Object.keys(checks).length - fallos}/${Object.keys(checks).length} comprobaciones OK`);
process.exit(fallos ? 1 : 0);
