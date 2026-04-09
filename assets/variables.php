<?php
// Cache busting
$var_param_monthly = '?' . date('Ym');
define("var_param_monthly", $var_param_monthly);
define("css_version", "v1");

// Canonical URL (strips query parameters, always HTTPS)
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$canonical_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');

// QR detection and timed promotion
$from_qr = isset($_GET['source']) && $_GET['source'] === 'qr';
$promo_end = strtotime('2026-03-31');
$promo_active = time() < $promo_end;
