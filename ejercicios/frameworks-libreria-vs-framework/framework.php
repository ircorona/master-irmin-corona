<?php
// Framework: ÉL llama a tu código (inversión de control, «no nos llames, ya te llamaremos»).
// Tú rellenas los huecos que te deja y sigues su método: rutas registradas,
// controladores que devuelven datos, vistas en un formato fijo.

final class MiniFramework
{
    private array $rutas = [];

    public function get(string $ruta, callable $controlador): void
    {
        $this->rutas[$ruta] = $controlador;
    }

    // El framework decide el flujo: busca la ruta, llama al controlador, pinta la vista.
    public function run(string $url): void
    {
        if (!isset($this->rutas[$url])) {
            // Un 404 real. Una SPA mal hecha devolvería 200 aquí (soft 404).
            echo "HTTP 404  $url\n<h1>No encontrada</h1>\n\n";
            return;
        }
        [$vista, $datos] = ($this->rutas[$url])();
        echo "HTTP 200  $url\n" . $this->render($vista, $datos) . "\n\n";
    }

    // Motor de plantillas al estilo Blade/Twig/Liquid:
    // {{ x }} escapa el HTML, {!! x !!} lo imprime tal cual.
    private function render(string $plantilla, array $datos): string
    {
        $plantilla = preg_replace_callback('/\{!!\s*(\w+)\s*!!\}/',
            fn($m) => $datos[$m[1]] ?? '', $plantilla);
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/',
            fn($m) => htmlspecialchars($datos[$m[1]] ?? '', ENT_QUOTES), $plantilla);
    }
}

// --- Mi código: solo los huecos que el framework me deja ---
$app = new MiniFramework();

$app->get('/', fn() => [
    '<title>{{ titulo }}</title><h1>{{ titulo }}</h1>',
    ['titulo' => 'Inicio'],
]);

$app->get('/blog', fn() => [
    '<h1>{{ titulo }}</h1>{!! cuerpo !!}<p>Escapado: {{ cuerpo }}</p>',
    ['titulo' => 'Blog', 'cuerpo' => '<b>hola</b>'],
]);

$app->run('/');
$app->run('/blog');
$app->run('/no-existe');
