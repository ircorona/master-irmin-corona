import { Component, inject, DOCUMENT } from '@angular/core';
import { Meta } from '@angular/platform-browser';
import { RouterLink } from '@angular/router';

// La misma página de producto para las tres rutas (prerender, servidor, cliente).
// El <title> lo pone la ruta (propiedad `title`); la description, el servicio Meta;
// el canonical, a mano sobre el DOCUMENT (Angular no trae servicio para <link>).
@Component({
  selector: 'app-producto',
  imports: [RouterLink],
  template: `
    <h1>Zapatillas de trail</h1>
    <p>Suela de 5 mm, 280 g.</p>
    <nav>
      <a routerLink="/categoria/trail">Más de trail</a>
      <button routerLink="/ofertas">Ofertas (routerLink en un botón)</button>
    </nav>
  `,
})
export class Producto {
  constructor() {
    inject(Meta).updateTag({ name: 'description', content: 'Zapatillas de trail con suela de 5 mm.' });
    const doc = inject(DOCUMENT);
    const canonical = doc.createElement('link');
    canonical.setAttribute('rel', 'canonical');
    canonical.setAttribute('href', 'https://tienda.example/zapatillas-trail/');
    doc.head.appendChild(canonical);
  }
}

@Component({
  selector: 'app-no-encontrada',
  template: `<h1>Página no encontrada</h1>`,
})
export class NoEncontrada {}
