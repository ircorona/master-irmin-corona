<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/ejemplo/CSS/style.css">

    <!-- no me funciono mi Roboto Mono -->
    <link href="/CSS/fonts.css" rel="stylesheet"> 

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@100..900&display=swap" rel="stylesheet">
    <!-- INTERNAL CSS -->
    <style>
        .internal-example {
            background: lightgreen;
            padding: 20px;
            margin: 15px 0;
            border: 3px solid green;
            font-size: 1.2rem;
        }
    </style>
    <title>Irmin's Website</title>
</head>
<body>
    <h1 id="main-header">Welcome to My Website <?php echo date("Y/M/d");?></h1>
  
    <header>
        <nav>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/about-me.html">About Me</a></li>
                <li><a href="/contact.html">Contact</a></li>
                <li><a href="/folder/file-folder.html">Projects</a></li>
                <li><a href="https://github.com/ircorona">My GitHub</a></li>
            </ul>
        </nav>
    </header>
    
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
    
    <!-- FAQ Section -->
    <h2>FAQs</h2>
    
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
    
    <footer>
        <p>&copy; 2025 Irmin Corona</p>
        <p>Mexico City, Mexico</p>
    </footer>
</body>
</html>