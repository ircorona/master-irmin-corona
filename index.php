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
        
        <h2>HTML Reference</h2>
        <img src="images/ultimate-html-cheatsheet.jpg" alt="HTML cheatsheet" width="600" height="450">
        
        <video width="400" controls>
            <source src="video/technology video.mp4" type="video/mp4">
            Your browser does not support HTML video.
        </video>
        
        <?php 
include __DIR__.'/assets/footer.php';  
?>
