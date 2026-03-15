# SEO Meta Tags

## Open Graph (Facebook, LinkedIn, WhatsApp)
```html
<meta property="og:title" content="Page Title">
<meta property="og:description" content="Description">
<meta property="og:image" content="https://yoursite.com/og-image.jpg">
<meta property="og:url" content="https://yoursite.com/page">
<meta property="og:type" content="website">
```

## Twitter Cards
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Page Title">
<meta name="twitter:description" content="Description">
<meta name="twitter:image" content="https://yoursite.com/image.jpg">
```

## Robots meta
```html
<meta name="robots" content="index, follow">          <!-- default -->
<meta name="robots" content="noindex, nofollow">       <!-- hide from Google -->
<meta name="robots" content="noindex, follow">          <!-- hide page, follow links -->
```

## data-nosnippet
```html
<span data-nosnippet>This text won't appear in Google search results</span>
```
Prevents Google from using specific text as the search snippet.

## LinkedIn Post Inspector
- Tool to test how your URL appears when shared on LinkedIn
- Reads Open Graph tags and shows preview
- URL parameters change the preview: `?version=3` vs `?version=50` can show different content
- Use it to verify OG tags are working before sharing

## Automated meta tags (from class 09)
```php
<?php
switch (true) {
    case strpos($uri_clean, '/contact') !== false:
        $titulo = "Contact - My Website";
        break;
    case strpos($uri_clean, '/about-me') !== false:
        $titulo = "About Me - My Website";
        break;
    default:
        $titulo = "My Website";
}
?>
<title><?php echo $titulo; ?></title>
```

## Applied in
- `assets/header.php` — automated titles based on URL
- `unica.php` — full meta tag examples
- Auramip — production OG tags on 30+ pages, tested with LinkedIn Post Inspector
