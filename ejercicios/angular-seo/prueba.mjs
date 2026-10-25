// Monta un proyecto de Angular con SSR en la carpeta temporal (fuera de OneDrive:
// node_modules pesa cientos de MB), copia los ficheros de app/, compila, arranca el
// servidor de Angular y mide qué HTML recibe un bot en cada modo de renderizado.
// Uso: node prueba.mjs        (la primera vez tarda unos minutos)
import { execSync, spawn } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { tmpdir } from 'node:os';
import { fileURLToPath } from 'node:url';

const CLI = '@angular/cli@21.2.28'; // Angular 22 pide Node >= 24.15
const aqui = dirname(fileURLToPath(import.meta.url));
const base = join(tmpdir(), 'ng-seo');
const proyecto = join(base, 'tienda');
const PUERTO = 4210;

if (!existsSync(proyecto)) {
  mkdirSync(base, { recursive: true });
  console.log(`Creando el proyecto con ${CLI} en ${proyecto} ...`);
  execSync(`npx -y ${CLI} new tienda --ssr --defaults --skip-git --interactive=false`, { cwd: base, stdio: 'ignore' });
}
cpSync(join(aqui, 'app'), join(proyecto, 'src', 'app'), { recursive: true });
console.log('Compilando (ng build) ...');
execSync('npx ng build', { cwd: proyecto, stdio: 'ignore' });

// Arranca el servidor de Angular, pide las cuatro rutas y lo para.
// hosts: valor de NG_ALLOWED_HOSTS (lista de hosts aceptados; sin ella, Angular 21
// rechaza "localhost:4210" y cae a renderizado en cliente con un 200).
async function medir(hosts) {
  const env = { ...process.env, PORT: String(PUERTO) };
  if (hosts) env.NG_ALLOWED_HOSTS = hosts; else delete env.NG_ALLOWED_HOSTS;
  const servidor = spawn(process.execPath, ['dist/tienda/server/server.mjs'], { cwd: proyecto, env });
  let log = '';
  servidor.stderr.on('data', d => { log += d; });
  await new Promise(r => servidor.stdout.on('data', d => /listening/i.test(d) && r()));

  const filas = [];
  for (const ruta of ['/prerender', '/servidor', '/cliente', '/no-existe']) {
    const r = await fetch(`http://localhost:${PUERTO}${ruta}`);
    const html = await r.text();
    const head = html.split('</head>')[0];
    filas.push({
      ruta,
      codigo: r.status,
      h1: (html.match(/<h1[\s>]/g) || []).length,
      title: (head.match(/<title>([^<]*)<\/title>/) || [])[1],
      description: /name="description"/.test(head),
      canonical: /rel="canonical"/.test(head),
      'a href': (html.match(/<a [^>]*href="\/categoria\/trail"/g) || []).length,
      'button href': /<button[^>]*href=/.test(html),
    });
  }
  await new Promise(r => { servidor.on('exit', r); servidor.kill(); });
  return { filas, rechazado: /is not allowed/.test(log) };
}

const sinHosts = await medir(null);
console.log('\nSin NG_ALLOWED_HOSTS:');
console.table(sinHosts.filas);
if (sinHosts.rechazado) console.log('Log del servidor: Header "host" ... is not allowed. Falling back to client side rendering.');

const conHosts = await medir('localhost');
console.log('\nCon NG_ALLOWED_HOSTS=localhost:');
console.table(conHosts.filas);
const filas = conHosts.filas;
const prerenderEnDisco = existsSync(join(proyecto, 'dist/tienda/browser/prerender/index.html'));
console.log(`Fichero prerenderizado en el build: dist/tienda/browser/prerender/index.html ${prerenderEnDisco ? 'existe' : 'NO existe'}`);
const version = JSON.parse(readFileSync(join(proyecto, 'node_modules/@angular/core/package.json'))).version;
console.log(`Angular ${version}`);

const [pre, ser, cli, nf] = filas;
const completa = f => f.h1 === 1 && f.title === 'Zapatillas de trail | Tienda' && f.description && f.canonical && f['a href'] === 1;
const checks = {
  'Sin hosts permitidos: TODO cae a CSR con 200': sinHosts.rechazado && sinHosts.filas.every(f => f.h1 === 0 && f.codigo === 200),
  'Prerender: HTML completo y fichero en el build': completa(pre) && prerenderEnDisco,
  'Server: HTML completo': completa(ser),
  'Client: el bot recibe 0 h1 y sin description': cli.h1 === 0 && !cli.description,
  'routerLink en <a> da href; en <button>, no': ser['a href'] === 1 && !ser['button href'],
  'Ruta inexistente: 404 de verdad': nf.codigo === 404,
};
let fallos = 0;
for (const [nombre, ok] of Object.entries(checks)) {
  console.log(`${ok ? 'OK   ' : 'FALLO'} ${nombre}`);
  if (!ok) fallos++;
}
console.log(`\n${Object.keys(checks).length - fallos}/${Object.keys(checks).length} comprobaciones OK`);
process.exit(fallos ? 1 : 0);
