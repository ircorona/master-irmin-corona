# Rastreo (Crawling)

## How Google crawls
1. Discovers URL (sitemap, links, previous crawl)
2. Checks `robots.txt` — am I allowed?
3. Downloads HTML (from Googlebot IP, e.g., `66.249.x.x`)
4. Downloads CSS, JS, images (resources for rendering)
5. Renders page (headless Chrome) → sees what users see
6. Extracts links → adds to crawl queue
7. Sends to indexing pipeline

## Key concepts

### IPs
Googlebot crawls from specific IP ranges. Verify with `nslookup`.
Fake bots use different IPs — check server logs.

### User-agents
Each crawler identifies itself:
- `Googlebot/2.1` — Google
- `bingbot/2.0` — Bing
- `Screaming Frog SEO Spider` — audit tool

### Rendering (two waves)
- Wave 1: HTML downloaded → text indexed immediately
- Wave 2: JavaScript rendered → dynamic content indexed (delayed)
- PHP (server-rendered) has advantage over JS frameworks for SEO

## Crawl budget optimization

| Factor | Wastes budget | Saves budget |
|--------|--------------|-------------|
| Duplicate content | Same page crawled multiple times | Use canonical tags |
| URL parameters | Infinite `?page=1,2,3...` variations | Parameter handling in Search Console |
| Slow server | Fewer pages per visit | Cache + GZIP in .htaccess |
| Broken links | Googlebot hits 404s | Fix or 301 redirect |
| Blocking CSS/JS | Can't render page | Allow in robots.txt |
| Orphan pages | Never discovered | Internal linking |

### What to optimize
- **Links** — more internal links to a page = crawled more often
- **Users** — pages with more traffic get crawled more frequently
- **Changes** — frequently updated pages get crawled more often
- **Internal linking** — every page reachable within 3 clicks from homepage
- **Robots.txt** — block admin/assets, allow CSS/JS
- **Server speed** — 200ms response = 500 pages/visit; 2000ms = 50 pages/visit
- **Cache** — proper headers reduce server load during crawling

## robots.txt example
```
User-agent: *
Disallow: /admin/
Disallow: /assets/
Allow: /css/
Sitemap: https://yoursite.com/sitemap.xml
```
Note: `Disallow` prevents crawling, NOT indexing. Use `noindex` meta tag to remove from Google.

## DoS vs DDoS

| | DoS | DDoS |
|---|---|---|
| Source | 1 machine | 1,000+ machines (botnet) |
| Effectiveness | Low — easy to block 1 IP | High — many IPs, looks like real traffic |
| SEO impact | Site down → Googlebot gets errors → rankings drop |

## Screaming Frog (crawler tool)
Desktop app that simulates Googlebot. Free version crawls up to 500 URLs.

Finds: broken links, missing meta tags, duplicate content, redirect chains, missing alt text, slow pages, orphan pages.

## Connection to our project
- `.htaccess` cache/GZIP → faster server → better crawl budget
- `robots.txt` → controls what Googlebot accesses
- `core.php` front controller → returns proper 404 for unknown URLs
- Internal links in `header.php` nav → ensures all pages are discoverable
- Meta tags in `header.php` → help Google understand each page
