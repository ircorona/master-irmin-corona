<?php 
$pagina = "ejemplo2";
include $_SERVER['DOCUMENT_ROOT'].'/assets/header.php';
?>

<link rel="stylesheet" href="/css/ejemplo2.css">

<div class="hero-section">
    <img id="imagen-hero" src="/images/morning.webp" alt="Hero image">
    <h1 id="mensaje-hora">Good Morning! ☀️</h1>
    <p>La hora actual es: <span id="hora-actual"></span></p>
</div>

<!-- ✅ Script AL FINAL, después del HTML -->
<script src="/scripts/ejemplo2.js"></script>

<?php 
include $_SERVER['DOCUMENT_ROOT'].'/assets/footer.php';
?>