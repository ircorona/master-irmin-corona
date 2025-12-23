<?php 
# header("Location: https://auramip.com");
$pagina = "home";  // Define qué página es
include $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';
# include_once $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';
# require $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';
# require_once $_SERVER['DOCUMENT_ROOT'].'/assets/header.php'; 
?>
<p>
    <?php
    echo $_SERVER['DOCUMENT_ROOT'];
    ?>
    <?php

    /* Example of variables in PHP
    */
    $tiger = "Roarrrr!";
    $seo = "google";
    $posicionamiento = "google";
    if ($seo == $posicionamiento) {
        echo "<p>No tiger for you!</p>";
    } 
    elseif($posicionamiento == "google") {
        echo "<p>Hola! Aquí está tu tigre:</p>";
    }
    else     
    {
    echo "<p>$tiger</p>";
    }
    ?>
</p>

        <section>
            <h1 id="heading1">Welcome to My Website <?php echo date("Y/M/d");?></h1>
            <div class="CSS">This is a tag div <span>that has a span inside</span></div>
            <!-- INLINE CSS -->
            <div style="background: pink; padding: 15px; margin: 10px; color: darkred; font-size: 20px;">
                This is an INLINE CSS example
            </div>
            <!-- INTERNAL CSS -->
            <div class="internal-example">
                This is an INTERNAL CSS example (from style tag in head)
            </div>

            <img src="images/loading-hero-banner.webp" alt="Loading banner" width="800" height="450">
            
            <h2>About this project</h2>
            <p>This is my <b>HTML</b> project where I'm learning how to build <a href="/about-me.html">web pages</a>.</p>
            
            <!-- This is personalized tag -->
            <tiger>This is my personalized tag - just playing around with HTML!</tiger>
            
            <h3>What I'm learning:</h3>
            <p><span>Important:</span> These are the basics I'm working on right now.</p>
            <ul>
                <li>HTML5</li>
                <li>CSS3</li>
                <li>JavaScript</li>
                <li>Git and GitHub</li>
            </ul>
        </section> 
        
        <!-- FAQ Section -->
        <h2>FAQs</h2>
        <?php ctas(); 
        how_do_you_turn_this_on();
        ?>
        
        <details>
            <summary>What is this website about?</summary>
            <p>It's just my learning project where I'm practicing HTML and figuring out how web development works.</p>
        </details>
        
        <details>
            <summary>How can I contact you?</summary>
            <p>Just shoot me an email at <a href="mailto:irminorta@gmail.com">irminorta@gmail.com</a></p>
        </details>
        
        <h2>HTML Reference</h2>
        <img src="images/ultimate-html-cheatsheet.jpg" alt="HTML cheatsheet" width="600" height="450">
        
        <video width="400" controls>
            <source src="video/technology video.mp4" type="video/mp4">
            Your browser does not support HTML video.
        </video>
        
        <?php 
include $_SERVER['DOCUMENT_ROOT'].'/assets/footer.php';  
?>
<script src="/scripts/pruebas.js"></script> 