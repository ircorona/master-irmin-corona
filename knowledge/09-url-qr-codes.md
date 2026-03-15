# URL: QR Codes (Extra)

## What is a QR code?
A 2D barcode that stores data (usually a URL). Scanned with a phone camera to open content instantly.

## Tool
https://qr.io/ — generate QR codes with custom frames, shapes, and logos.

## Content types QR can encode
Link, Text, Email, Call, SMS, V-card, WhatsApp, Wi-Fi, PDF, App, Images, Video, Social Media, Events, 2D Barcodes.

## Key strategy: URL parameters for tracking

Instead of encoding a plain URL, add a parameter to track the source:
```
Plain:     yoursite.com/menu
Tracked:   yoursite.com/menu?source=qr
UTM:       yoursite.com/menu?utm_source=qr&utm_medium=table&utm_campaign=summer2026
```

This shows up in Google Analytics → Acquisition → Campaigns.

### Different QR = different parameter
```
Table QR:     yoursite.com/menu?source=qr-table
Flyer QR:     yoursite.com/?source=qr-flyer
Card QR:      yoursite.com/contact?source=qr-card
```

## Key strategy: QR-triggered promotions

Detect the QR parameter in PHP and show a time-limited promotion:
```php
$from_qr = isset($_GET['source']) && $_GET['source'] === 'qr';
$promo_end = strtotime('2026-03-31');
$promo_active = time() < $promo_end;

if ($from_qr && $promo_active) {
    $days_left = ceil(($promo_end - time()) / 86400);
    echo "10% off — code QR2026 — valid for $days_left days";
}
```

Important: promotions must have an expiration date — they are not permanent.

## Connection to class topics
- **Ruta** → QR encodes a path (`/menu`)
- **Parámetros** → `?source=qr` tells the server where the visitor came from
- **Hash** → `#section` could scroll to a specific part of the page
- **Cache** → QR landing pages should NOT be heavily cached (promotions change)

## SEO impact
- QR codes don't directly affect rankings
- They drive high-intent traffic (user chose to scan)
- Parameter tracking provides marketing data
- Bridges offline marketing → online measurement
