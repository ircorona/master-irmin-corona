<?php 
$pagina = "about";  // ← Define PRIMERO qué página es
include $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';  // ← Solo UNA inclusión
?>
    <iframe width="560" height="315" 
    src="https://www.youtube.com/embed/tO4T3G2GRn8?si=-F8SX-Xfxr9HgFUF&amp;start=1" 
    title="YouTube video player" 
    frameborder="0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
    allowfullscreen>
    </iframe>
    
    <!-- <h1>Who am I?</h1> --> <h1><?php echo $titulo; ?></h1>
    <p>I'm <b>Irmin Corona</b>, learning web development and building my first HTML pages. Still figuring things out but enjoying the process.</p>
    
    <blockquote cite="https://climbthesearches.com">
        "My professor Carlos told me I was using too much AI, so here I am using less AI and more manual code. But this is how you learn, right?"
        <cite>Irmin Corona</cite>
    </blockquote>

    <h2>Image with CSS</h2>
    <style>
        .imagenclase{
           background-image: url('/images/Exodia.webp');
           background-size: cover;
           width: 250px;
           height: 360px;
           margin: 0 auto;
        }
    </style>
    <div class="imagenclase"></div>
    <h2>Image with image and src</h2>
    <image src="/images/Exodia.webp" alt="Exodia the Forbidden One" 
    title="Exodia the Forbidden One"
    width="250" height="360">
    <h3>What I'm into:</h3>
    <ol>
        <li>Programming - I like to code to make my life easier</li>
        <li>WordPress dev, Elementor enthusiast</li>
        <li>Data Analyst - that's my main job</li>
        <li><span>Salsa Dancing</span> - yeah I can dance with rhythm baby</li>
    </ol>
    
    <h2>What I know so far</h2>
    <ul>
        <li>Basic HTML</li>
        <li>Tables</li>
        <li>Multimedia</li>
    </ul>
    
    <h2>Code Example</h2>
    <p>Here's a simple HTML example:</p>
    <code>
        &lt;div&gt; This is a code inside the code &lt;/div&gt;
    </code>
    
    <h2>Let's connect</h2>
    <ul>
        <li><b>LinkedIn:</b> <a href="https://www.linkedin.com/in/irmin-corona/">Irmin Corona</a></li>
        <li><b>GitHub:</b> <a href="https://github.com/ircorona">@ircorona</a></li>
        <li><b>Email:</b> <a href="mailto:irminorta@gmail.com">irminorta@gmail.com</a></li>
        <li><b>Website:</b> <a href="https://climbthesearches.com/">climbthesearches.com</a></li>
    </ul>
    
    <p>Check out my <a href="/folder/file-folder.php"><span>projects page</span></a> to see what I'm working on, or visit my <a href="https://climbthesearches.com/">personal website</a> for more.</p>
    
    <?php 
include $_SERVER['DOCUMENT_ROOT'].'/assets/footer.php';  
?>