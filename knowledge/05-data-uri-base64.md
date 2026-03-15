# Data URIs — Base64 Inline Images

## URL vs URI
- **URL** = path to fetch a resource from a server (`/images/logo.png`)
- **URI** = complete resource identifier — can contain the data itself
- Every URL is a URI, but not every URI is a URL

## Data URI format
```
data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...
↑     ↑          ↑      ↑
|     MIME type   encoding  actual image data as text
protocol
```

## Convert image to Data URI in PHP
```php
function image_to_data_uri($filepath) {
    $type = mime_content_type($filepath);
    $data = file_get_contents($filepath);
    $base64 = base64_encode($data);
    return "data:$type;base64,$base64";
}
```

## When to use

| Image size | Use | Why |
|-----------|-----|-----|
| < 2KB (icons, dots) | Data URI | HTTP request overhead > image size |
| > 2KB (photos, logos) | Regular URL | Let browser cache independently |

## Important: Data URIs are NOT smaller
Base64 encoding adds ~33% to file size. The benefit is eliminating an HTTP request, not reducing size.

## Connection to cache
- Regular images: cached for months (via .htaccess ExpiresByType)
- Data URIs: cached with the HTML page (usually 30 minutes)
- Large image as Data URI = re-downloaded every 30 minutes instead of cached for months

## Reference
https://web.dev/articles/responsive-images#inlining-pros-and-cons
