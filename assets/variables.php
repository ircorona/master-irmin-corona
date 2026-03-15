<?php
// Cache busting
$var_param_monthly = '?' . date('Ym');
define("var_param_monthly", $var_param_monthly);
define("css_version", "v1");

// QR detection and timed promotion
$from_qr = isset($_GET['source']) && $_GET['source'] === 'qr';
$promo_end = strtotime('2026-03-31');
$promo_active = time() < $promo_end;
