# 04 — SEO Standards

> Search Engine Optimization best practices applied to every public-facing page.

---

## Page Title (`<title>`)

Every page must set a descriptive `$pageTitle` variable before including `head.php`:

```php
$pageTitle = 'Mua tài khoản Netflix giá rẻ - KaiShop';
```

The layout renders:
```html
<title><?= $pageTitle ?? 'KaiShop' ?></title>
```

### Rules
- Include the **primary keyword** first.
- Keep under **60 characters**.
- Append brand name with a separator: `Page Name - KaiShop`

---

## Meta Description

Set in `head.php` or via controller:

```html
<meta name="description" content="Shop bán tài khoản giá rẻ, uy tín. Giao hàng tự động 24/7.">
```

### Rules
- **150–160 characters** max.
- Include a **call-to-action** or **value proposition**.
- Unique per page — no duplicates.

---

## Heading Hierarchy

```html
<h1>Main Page Title</h1>         <!-- ONE per page, matches intent -->
<h2>Section Title</h2>            <!-- Major sections -->
<h3>Subsection</h3>               <!-- Subsections -->
```

### Rules
- Only **one `<h1>`** per page.
- `<h1>` should describe the main topic clearly.
- Don't skip levels (e.g., `<h1>` → `<h3>`).

---

## Semantic HTML

Use HTML5 elements for structure:

```html
<header>    <!-- Site header / nav -->
<main>      <!-- Primary content -->
<section>   <!-- Thematic grouping -->
<article>   <!-- Self-contained content (product card, blog post) -->
<aside>     <!-- Sidebar, related content -->
<footer>    <!-- Site footer -->
<nav>       <!-- Navigation menus -->
```

---

## Image Optimization

```html
<img src="product.webp"
     alt="Tài khoản Netflix Premium 4K"
     loading="lazy"
     width="300" height="200">
```

### Rules
- **Always** include `alt` text with a descriptive keyword.
- Use `loading="lazy"` for below-the-fold images.
- Prefer **WebP** format for smaller file sizes.
- Specify `width` and `height` to prevent layout shift (CLS).

---

## URL Structure

Clean, readable URLs:

```
✅ /san-pham/netflix-premium
✅ /admin/users/edit/admin
❌ /product.php?id=123&type=2
```

The `.htaccess` rewrite and `Router.php` handle clean URLs automatically.

---

## Open Graph (Social Sharing)

For public pages, include OG tags:

```html
<meta property="og:title" content="Netflix Premium - KaiShop">
<meta property="og:description" content="Tài khoản Netflix 4K giá rẻ nhất">
<meta property="og:image" content="https://kaishop.vn/assets/images/og-cover.jpg">
<meta property="og:url" content="https://kaishop.vn/san-pham/netflix">
<meta property="og:type" content="website">
```

---

## Performance (Core Web Vitals)

| Metric | Target | How |
|---|---|---|
| **LCP** (Largest Contentful Paint) | < 2.5s | Optimize images, preload critical CSS |
| **FID** (First Input Delay) | < 100ms | Defer non-critical JS |
| **CLS** (Cumulative Layout Shift) | < 0.1 | Set image dimensions, avoid layout jumps |

### Quick Wins
- Load CSS in `<head>`, JS at bottom.
- Use CDN for third-party libraries.
- Minify CSS/JS for production.
- Enable `gzip` compression in Apache.

---

## Robots & Sitemap

```
# robots.txt
User-agent: *
Disallow: /admin/
Disallow: /hethong/
Disallow: /ajax/
Allow: /

Sitemap: https://kaishop.vn/sitemap.xml
```

> Admin pages should **never** be indexed by search engines.
