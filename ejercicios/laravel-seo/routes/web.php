<?php

use Illuminate\Support\Facades\Route;
use Laravel\Head\Facades\Head;

// Página de producto: title, description y canonical con Laravel Head
Route::get('/producto', function () {
    Head::title('Zapatillas de trail', suffix: ' | Tienda')
        ->description('Zapatillas de trail con suela de 5 mm.')
        ->canonical(); // la URL actual, sin parámetros

    return view('producto');
})->name('producto');

// Dos redirecciones que parecen iguales: Route::redirect da 302 por defecto
Route::redirect('/viejo', '/producto');
Route::permanentRedirect('/antiguo', '/producto');

// Zona privada: noindex declarado en la propia ruta
Route::view('/admin', 'producto')->withHead(robots: 'noindex, nofollow');
