// Renderiza en servidor (SSR) una página de Vue 3.5 real y mira qué HTML recibe un
// bot sin JavaScript. Tres trampas propias de Vue:
//   1. v-if frente a v-show en un acordeón de preguntas cerrado
//   2. datos cargados en async setup() frente a onMounted()
//   3. <RouterLink> (Vue Router 5): ¿genera un <a href> de verdad?
// Uso: node prueba.mjs
import { createSSRApp, defineComponent, h, ref, onMounted, Suspense } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createRouter, createMemoryHistory, RouterLink, RouterView } from 'vue-router';

const espera = ms => new Promise(r => setTimeout(r, ms));

// Acordeón cerrado: la respuesta 1 con v-show, la 2 con v-if
const Faq = defineComponent({
  setup: () => ({ abierta: ref(false) }),
  template: `
    <section>
      <h2>Preguntas</h2>
      <p v-show="abierta" id="resp-vshow">RESPUESTA-CON-V-SHOW</p>
      <p v-if="abierta" id="resp-vif">RESPUESTA-CON-V-IF</p>
    </section>`,
});

// Datos que tardan: uno espera en async setup (SSR lo espera), otro en onMounted (solo cliente)
const PrecioAsyncSetup = defineComponent({
  async setup() {
    await espera(200);
    return { precio: 'PRECIO-ASYNC-SETUP 89 EUR' };
  },
  template: `<p id="precio-setup">{{ precio }}</p>`,
});
const PrecioOnMounted = defineComponent({
  setup() {
    const precio = ref('Cargando...');
    onMounted(async () => { await espera(200); precio.value = 'PRECIO-ON-MOUNTED 89 EUR'; });
    return { precio };
  },
  template: `<p id="precio-mounted">{{ precio }}</p>`,
});

const Producto = defineComponent({
  components: { Faq, PrecioAsyncSetup, PrecioOnMounted, RouterLink },
  template: `
    <main>
      <h1>Zapatillas de trail</h1>
      <Suspense><PrecioAsyncSetup /></Suspense>
      <PrecioOnMounted />
      <Faq />
      <nav>
        <RouterLink to="/categoria/trail/">Más de trail</RouterLink>
        <RouterLink to="/ofertas/" custom v-slot="{ navigate }"><span @click="navigate">Ofertas (custom)</span></RouterLink>
      </nav>
    </main>`,
});

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: '/', component: Producto },
    { path: '/categoria/trail/', component: { template: '<p>trail</p>' } },
    { path: '/ofertas/', component: { template: '<p>ofertas</p>' } },
  ],
});

const app = createSSRApp({ render: () => h(RouterView) });
app.use(router);
await router.push('/');
await router.isReady();
const ssr = await renderToString(app);

// Lo que sirve create-vue (Vite) por defecto: una SPA
const csr = '<div id="app"></div><script type="module" src="/src/main.js"></script>';

const fila = (nombre, html) => ({
  variante: nombre,
  h1: (html.match(/<h1[\s>]/g) || []).length,
  'v-show (oculto)': html.includes('RESPUESTA-CON-V-SHOW'),
  'v-if (oculto)': html.includes('RESPUESTA-CON-V-IF'),
  'async setup': html.includes('PRECIO-ASYNC-SETUP'),
  'onMounted': html.includes('PRECIO-ON-MOUNTED'),
  '<a href>': (html.match(/<a [^>]*href=/g) || []).length,
});
console.table([fila('CSR (create-vue)', csr), fila('SSR (renderToString)', ssr)]);
console.log('\nHTML del SSR:\n' + ssr.replace(/></g, '>\n<') + '\n');

const s = fila('', ssr);
const checks = {
  'CSR: 0 h1 y 0 enlaces': fila('', csr).h1 === 0 && fila('', csr)['<a href>'] === 0,
  'v-show: la respuesta oculta SÍ está en el HTML': s['v-show (oculto)'] && /display:none/.test(ssr),
  'v-if: la respuesta oculta NO está en el HTML': !s['v-if (oculto)'],
  'async setup: el SSR espera al dato': s['async setup'],
  'onMounted: el SSR envía «Cargando...»': !s['onMounted'] && ssr.includes('Cargando...'),
  'RouterLink: 1 <a href> (el custom no genera <a>)': s['<a href>'] === 1 && ssr.includes('href="/categoria/trail/"'),
};
let fallos = 0;
for (const [nombre, ok] of Object.entries(checks)) {
  console.log(`${ok ? 'OK   ' : 'FALLO'} ${nombre}`);
  if (!ok) fallos++;
}
console.log(`\n${Object.keys(checks).length - fallos}/${Object.keys(checks).length} comprobaciones OK`);
process.exit(fallos ? 1 : 0);
