import { RenderMode, ServerRoute } from '@angular/ssr';

// Un modo de renderizado por ruta (guía de SSR de angular.dev)
export const serverRoutes: ServerRoute[] = [
  { path: 'prerender', renderMode: RenderMode.Prerender }, // HTML en el build (SSG)
  { path: 'servidor', renderMode: RenderMode.Server },     // HTML en cada petición (SSR)
  { path: 'cliente', renderMode: RenderMode.Client },      // HTML en el navegador (CSR)
  { path: '**', renderMode: RenderMode.Server, status: 404 }, // 404 de verdad, no soft 404
];
