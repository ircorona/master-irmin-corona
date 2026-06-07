<?php
$pagina = "noticia";  // ← Define PRIMERO qué página es
include $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';  // ← Solo UNA inclusión
?>
    <article>
        <h1>Google elimina definitivamente el endpoint de ping de sitemaps</h1>

        <!-- En noticias SIEMPRE es importante la HORA: <time> con datetime completo -->
        <p class="byline">
            Por <a href="https://github.com/ircorona" rel="author">Irmin Corona</a>
            &middot;
            <time datetime="2026-06-06T09:30:00-06:00">6 de junio de 2026, 09:30 (CDMX)</time>
        </p>

        <img src="/images/ultimate-html-cheatsheet.jpg"
             alt="Diagrama de un fichero sitemap XML" width="600" height="450">

        <p>
            <strong>Google ha confirmado la retirada definitiva del endpoint de ping de
            sitemaps</strong> (<code>/ping?sitemap=</code>), el método HTTP que durante años
            permitió a los webmasters avisar al buscador de que su sitemap había cambiado.
            La llamada, que antes devolvía un <code>HTTP 200</code>, hoy responde con un
            <code>404</code>.
        </p>

        <p>
            Según la compañía, la inmensa mayoría de los envíos sin autenticar a ese endpoint
            <em>acababan siendo spam</em> y aportaban poco valor real al rastreo. La decisión
            afecta tanto a Google como a Bing, que retiró su endpoint equivalente en las mismas
            fechas.
        </p>

        <h2>¿Qué deben hacer ahora los SEO?</h2>
        <ul>
            <li>Declarar el sitemap en el <a href="/robots.txt">robots.txt</a> con la directiva <code>Sitemap:</code>.</li>
            <li>Enviarlo y monitorizarlo desde <strong>Google Search Console</strong> (envío autenticado, con estado de errores).</li>
            <li>Para avisar de cambios puntuales, usar <strong>IndexNow</strong> (Bing y Yandex, no Google).</li>
        </ul>

        <blockquote cite="https://developers.google.com/search/blog/2023/06/sitemaps-lastmod-ping">
            "El código que use este endpoint no causará problemas en la Búsqueda de Google;
            simplemente dejará de tener efecto."
            <cite>Google Search Central</cite>
        </blockquote>

        <p>
            La recomendación de fondo no cambia: mantener un <code>&lt;lastmod&gt;</code>
            honesto y dejar que Google descubra los cambios en su rastreo habitual.
        </p>

        <p><a href="/">&larr; Volver a la portada</a></p>
    </article>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/assets/footer.php';
?>
