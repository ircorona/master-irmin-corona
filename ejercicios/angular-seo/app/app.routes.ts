import { Routes } from '@angular/router';
import { Producto, NoEncontrada } from './producto';

const titulo = 'Zapatillas de trail | Tienda';

export const routes: Routes = [
  { path: 'prerender', component: Producto, title: titulo },
  { path: 'servidor', component: Producto, title: titulo },
  { path: 'cliente', component: Producto, title: titulo },
  { path: '**', component: NoEncontrada, title: 'No encontrada' },
];
