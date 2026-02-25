# 08 — Security & Performance Optimization

> Thinking patterns for building secure and fast web applications.

---

## Security Mindset

### 1. Never Trust User Input

Every value from `$_POST`, `$_GET`, `$_COOKIE`, and URL params is **untrusted**.

```php
// ❌ DANGEROUS — SQL Injection
$sql = "SELECT * FROM users WHERE username = '$_POST[username]'";

// ✅ SAFE — Prepared statements
$stmt = $connection->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $_POST['username']);
$stmt->execute();
```

> 🚨 **Priority:** Migrate all raw SQL queries to prepared statements over time.

### 2. XSS Prevention (Cross-Site Scripting)

Always escape output displayed in HTML:

```php
// ❌ BAD — Raw output allows script injection
echo $row['username'];

// ✅ GOOD — Escaped output
echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');

// Shorthand used in views:
<?= htmlspecialchars($value) ?>
```

### 3. CSRF Protection

For forms that modify data, consider adding CSRF tokens:

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validate on submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}
```

### 4. Environment Variables

Sensitive data lives in `.env`, **never** hardcoded:

```env
DB_HOST=localhost
DB_NAME=kaishop
DB_USER=root
DB_PASS=secret_password
TELEGRAM_BOT_TOKEN=123456:ABC...
```

```php
// Access via:
$_ENV['DB_HOST']
```

> `.env` is in `.gitignore` — never committed to version control.

### 5. Password Hashing

```php
// Store passwords with bcrypt
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verify
if (password_verify($input, $stored_hash)) {
    // Login success
}
```

> ❌ Never store passwords as plain text or MD5.

---

## Access Control

### Admin Guard Pattern

Every admin controller method must call `requireAdmin()`:

```php
public function index() {
    $this->requireAdmin();  // ← FIRST LINE, always
    // ... rest of logic
}
```

### Session Security

```php
// Regenerate session ID after login to prevent session fixation
session_regenerate_id(true);

// Set session cookie flags
ini_set('session.cookie_httponly', 1);  // Prevent JS access
ini_set('session.cookie_secure', 1);   // HTTPS only (production)
ini_set('session.use_strict_mode', 1); // Reject uninitialized session IDs
```

---

## Performance Optimization

### Database

| Practice | Why |
|---|---|
| Add indexes to frequently queried columns | Speeds up `WHERE`, `ORDER BY` |
| Use `SELECT specific_columns` not `SELECT *` | Less data transferred |
| Avoid N+1 queries (query inside loops) | Use JOINs instead |
| Cache expensive queries if results rarely change | Reduce DB load |

### Frontend

| Practice | Why |
|---|---|
| Load CSS in `<head>` | Prevents unstyled content flash |
| Load JS before `</body>` | Doesn't block page rendering |
| Use `loading="lazy"` on images | Load below-fold images on demand |
| Minify CSS/JS for production | Smaller file sizes |
| Enable gzip in Apache | Compresses responses ~70% |

### Apache `.htaccess` Performance

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/webp "access plus 1 year"
</IfModule>
```

---

## Error Handling

### Production Error Pages

Custom error pages exist for common HTTP errors:

| File | HTTP Code | When |
|---|---|---|
| `403.php` | Forbidden | Access denied |
| `404.php` | Not Found | Invalid URL |
| `502.php` | Bad Gateway | Upstream error |
| `503.php` | Service Unavailable | Maintenance mode |

### Logging

- Errors should be logged to a file, **not** displayed to users in production.
- Use `error_log()` or a logging service.
- Telegram alerts via `sendTele()` for critical errors.

---

## Security Checklist

- [ ] All user input is escaped with `htmlspecialchars()` in views
- [ ] Sensitive data is in `.env`, not in code
- [ ] Admin routes are protected by `requireAdmin()`
- [ ] Passwords are hashed with `password_hash()`
- [ ] `.env` and `hethong/` are in `.gitignore`
- [ ] `robots.txt` blocks admin paths
- [ ] No `var_dump()` or `print_r()` in production code
- [ ] Session cookies have `httponly` and `secure` flags
