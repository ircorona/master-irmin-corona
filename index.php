<?php 
# header("Location: https://auramip.com");
$pagina = "home";  // Define qué página es
include __DIR__.'/assets/header.php';
include __DIR__.'/assets/functions.php';
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
    <p id="firstonclick">My first onclick</p>
    <button onclick="
    document.getElementById('firstonclick').
    innerHTML = '¡Cambié TODO!' + ejemplo 
    + ' - ' + testing 
    + ' - Pi es ' 
    + pi + ' - Array example: ' 
    + arrayExample
    + ' - Name: ' + masterseo.name;  
    document.getElementById('firstonclick').style.color = 'white';
    document.getElementById('firstonclick').style.fontSize = '20px';
    ">
        Change Everything!
    </button>

    <p id="js-test">
    This text will be changed by JavaScript based on the time of day.
    </p>
    <p id="js-day">
    This will show the current day.
    </p>
    <div class = "js-test-div" onclick=functionIrmin()>
    Click me amigo!
    </div>
    
    <script> 
    let time = new Date().getHours();
    let greeting;

    if (time < 12) {
        greeting = "Good morning! ☀️";
    } 
    else if (time < 18) {
        greeting = "Good afternoon! 🌤️";
    } 
    else {
        greeting = "Good evening! 🌙";
    }

    document.getElementById("js-test").innerHTML = greeting;
    </script>
    <script> 
// SWITCH PARA EL DÍA DE LA SEMANA
// ============================================
    let dayNumber = new Date().getDay(); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
    let dayName;
    let dayMessage;

    switch (dayNumber) {
        case 0:
            dayName = "Sunday";
            dayMessage = "¡Relax! It's Sunday! 😴";
            break;
        case 1:
            dayName = "Monday";
            dayMessage = "Start of the week! 💪";
            break;
        case 2:
            dayName = "Tuesday";
            dayMessage = "Keep pushing! 🚀";
            break;
        case 3:
            dayName = "Wednesday";
            dayMessage = "Midweek! You're halfway there! 🎯";
            break;
        case 4:
            dayName = "Thursday";
            dayMessage = "Almost there! 🏃";
            break;
        case 5:
            dayName = "Friday";
            dayMessage = "TGIF! Weekend is near! 🎉";
            break;
        case 6:
            dayName = "Saturday";
            dayMessage = "Weekend vibes! 🎊";
            break;
        default:
            dayName = "Unknown";
            dayMessage = "Something went wrong!";
    }

    document.getElementById("js-day").innerHTML = `Today is <strong>${dayName}</strong>: ${dayMessage}`;
    </script>

    <script> 
    let ejemplo = 'Variable with let';
    var testing = 'Variable with var';
    const pi = 3.1416;
    let arrayExample = [1, 2, 3, 4, 5];
    let masterseo = {
        name: "Irmin Corona",
        role: "Web Developer",
        country: "Thailand"
    };
    </script>
    <h2>Testing JavaScript getElementsByTagName</h2>
    <!-- Por que cuando es [0] solo trae el primero? <div> -->
  <!--   <div id="demo" class="demo-div">-->
    <p id="demo2" class="demo-div">The date and time is:</p>
    <p id="demo3" class="demo-div">The date and time is:</p>
    <p id="demo4" class="demo-div">The date and time is:</p>
        <script>
        // document.getElementById("demo").innerHTML = Date();

        // document.getElementsByClassName("demo-div")[0].innerHTML = Date();

        const activador = document.querySelector(".js-test-div");
        activador.addEventListener("click", functionIrmin);
        function functionIrmin(){
        const collections = document.getElementsByClassName("demo-div"); 
        for (let i = 0; i < collections.length; i++) {
            collections[i].innerHTML = Date();
        } 
        }
        // document.querySelectorAll(".demo-div").innerHTML = Date();

        // Pero querySelectorAll necesita un loop para recorrer todos los elementos
        // const elements = document.querySelectorAll(".demo-div");
        // elements.forEach((el, index) => {
        //    el.style.color = "blue";
        //    el.innerHTML = `🔵 Elemento ${index + 1}: ${Date()}`;
        //});



        //document.getElementsByTagName("h2")[0].innerHTML = Date();

        //const elements = document.getElementsByTagName("h2");
        //for (let i = 0; i < elements.length; i++) {
         //   elements[i].innerHTML = Date();
        //}  
        
        </script>
    </div>


        <section>
            <h1 id="heading1">Welcome to My Website <?php echo date("Y/M/d");?></h1>
            <noscript>
                <p style="color: red; font-weight: bold;">
                    JavaScript is disabled in your browser. Some features may not work as intended.
                     <?php echo date("Y/M/d");?>
                </p>    
            </noscript>
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
        <?php
        echo '<img src="' . $_SERVER['DOCUMENT_ROOT'] . '/images/faq-icon.png" alt="FAQ icon" width="50" height="50">';
        echo '<img src="/images/faq-icon.png" alt="FAQ frequently asked questions icon with question mark symbol" width="50" height="50">';
        ctas();
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
        
        <!-- EXERCISE: 5 tipos de enlaces (relativas y absolutas) -->
        <h2>Ejercicio: URL Relativas y Absolutas</h2>

        <!-- 1. Absolute URL (external) -->
        <p>
            <a href="https://github.com/ircorona" target="_blank" rel="noopener noreferrer">1. Absoluta: My GitHub</a>
            <br><code>&lt;a href="https://github.com/ircorona"&gt;</code> — URL completa con protocolo + dominio
        </p>

        <!-- 2. Relative: same folder (sin /) -->
        <p>
            <a href="about-me">2. Relativa misma carpeta: About Me</a>
            <br><code>&lt;a href="about-me"&gt;</code> — Busca en la misma carpeta actual (puede fallar desde subcarpetas)
        </p>

        <!-- 3. Relative: subfolder (sin /) -->
        <p>
            <a href="folder/file-folder">3. Relativa subcarpeta: Projects</a>
            <br><code>&lt;a href="folder/file-folder"&gt;</code> — Entra a la carpeta folder/ desde la ubicacion actual
        </p>

        <!-- 4. Relative: from ROOT (con /) — THE BEST -->
        <p>
            <a href="/contact">4. Relativa desde ROOT: Contact</a>
            <br><code>&lt;a href="/contact"&gt;</code> — Siempre desde la raiz del sitio (LA MEJOR OPCION)
        </p>

        <!-- 5. Relative: one level up (../) -->
        <p>
            <a href="../">5. Relativa un nivel arriba: Parent folder</a>
            <br><code>&lt;a href="../"&gt;</code> — Sube un nivel desde la carpeta actual
        </p>

        <!-- VISUAL EXPLANATION: Building analogy -->
        <h3>Tu proyecto (planta baja)</h3>
        <pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-size: 14px; line-height: 1.6; overflow-x: auto;">
&#x1F3E2; ROOT (lobby / planta baja)
&#x251C;&#x2500;&#x2500; &#x1F6AA; index.php         &#x2190; tu estas AQUI
&#x251C;&#x2500;&#x2500; &#x1F6AA; about-me.php
&#x251C;&#x2500;&#x2500; &#x1F6AA; contact.php
&#x2514;&#x2500;&#x2500; &#x1F4C1; folder/ (piso 1)
    &#x2514;&#x2500;&#x2500; &#x1F6AA; file-folder.php</pre>

        <h3>Edificio con 4 pisos</h3>
        <pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-size: 14px; line-height: 1.6; overflow-x: auto;">
&#x1F3E2; ROOT (lobby)
&#x251C;&#x2500;&#x2500; &#x1F6AA; index.php
&#x251C;&#x2500;&#x2500; &#x1F6AA; contact.php
&#x2514;&#x2500;&#x2500; &#x1F4C1; floor1/
    &#x251C;&#x2500;&#x2500; &#x1F6AA; page-a.php
    &#x2514;&#x2500;&#x2500; &#x1F4C1; floor2/
        &#x251C;&#x2500;&#x2500; &#x1F6AA; page-b.php
        &#x2514;&#x2500;&#x2500; &#x1F4C1; floor3/
            &#x2514;&#x2500;&#x2500; &#x1F6AA; page-c.php  &#x2190; estas en el piso 3</pre>

        <h3>Desde page-c.php (piso 3) quieres llegar a contact.php (lobby)</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px;">
            <thead>
                <tr style="background: #2d2d2d; color: #fff;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #444;">Tu dices...</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #444;">El navegador busca...</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #444;">Resultado</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #ffebee;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><code>href="contact"</code></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">floor1/floor2/floor3/contact</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: red; font-weight: bold;">&#x2717; No existe</td>
                </tr>
                <tr style="background: #e8f5e9;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><code>href="/contact"</code></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">/contact (desde el lobby)</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: green; font-weight: bold;">&#x2713; Siempre funciona</td>
                </tr>
                <tr style="background: #ffebee;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><code>href="../contact"</code></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">floor1/floor2/contact</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: red; font-weight: bold;">&#x2717; Piso equivocado</td>
                </tr>
                <tr style="background: #ffebee;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><code>href="../../contact"</code></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">floor1/contact</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: red; font-weight: bold;">&#x2717; Piso equivocado</td>
                </tr>
                <tr style="background: #fff8e1;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><code>href="../../../contact"</code></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">/contact (lobby)</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: orange; font-weight: bold;">&#x2713; Funciona, pero tienes que contar pisos...</td>
                </tr>
            </tbody>
        </table>
        <p style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid green; font-size: 16px;">
            <strong>Regla:</strong> <code>../</code> = escaleras (tienes que contar pisos) | <code>/</code> = elevador al lobby (siempre correcto) &#x1F6D7;
        </p>

        <h2>HTML Reference</h2>
        <img src="images/ultimate-html-cheatsheet.jpg" alt="HTML cheatsheet" width="600" height="450">
        
        <video width="400" controls>
            <source src="video/technology video.mp4" type="video/mp4">
            Your browser does not support HTML video.
        </video>
        
        <?php 
include __DIR__.'/assets/footer.php';  
?>
