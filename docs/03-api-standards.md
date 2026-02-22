# 03 — API & Controller Standards

> How Controllers handle requests, return responses, and follow MVC patterns.

---

## MVC Flow

```
Browser Request
    → index.php (bootstrap)
    → Router.php (parse URL → find route)
    → Controller@method (handle logic)
    → Model (query database)
    → View (render HTML) or JSON response
```

---

## Controller Structure

Every controller extends `core/Controller.php` which provides:

| Method | Purpose |
|---|---|
| `$this->view($path, $data)` | Render a PHP view, passing variables via `extract()` |
| `$this->json($data)` | Return a JSON response with correct headers |
| `$this->redirect($url)` | HTTP redirect |
| `$this->post($key)` | Safe access to `$_POST[$key]` |

### Standard Controller Template

```php
<?php
class ExampleController extends Controller {
    private $authService;
    private $model;

    public function __construct() {
        $this->authService = new AuthService();
        $this->model = new ExampleModel();
    }

    // Admin-only guard
    private function requireAdmin() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        if ($user['level'] != 9) {
            http_response_code(403);
            die('Access denied');
        }
    }

    // GET /admin/example
    public function index() {
        $this->requireAdmin();
        $items = $this->model->all();
        $this->view('admin/example/index', ['items' => $items]);
    }

    // POST /admin/example/store
    public function store() {
        $this->requireAdmin();
        // validate → insert → redirect with flash message
    }

    // POST /admin/example/delete
    public function delete() {
        $this->requireAdmin();
        $id = $this->post('id');
        $this->model->delete($id);
        return $this->json(['success' => true]);
    }
}
```

---

## Route Definitions (`config/routes.php`)

```php
// GET routes
$router->get('/admin/users', 'Admin/UserController@index');
$router->get('/admin/users/edit/{username}', 'Admin/UserController@edit');

// POST routes
$router->post('/admin/users/edit/{username}', 'Admin/UserController@update');
$router->post('/admin/users/delete', 'Admin/UserController@delete');
```

### Naming Convention
- **Nouns** for resources: `/admin/users`, `/admin/products`
- **Verbs** for actions: `/add`, `/edit/{id}`, `/delete`
- **Nested resources**: `/admin/finance/giftcodes/log/{id}`

---

## JSON API Responses

For AJAX endpoints, always return a consistent shape:

```json
// Success
{ "success": true, "message": "Deleted successfully" }

// Error
{ "success": false, "message": "Item not found" }

// With data
{ "success": true, "data": { ... } }
```

---

## Flash Messages (Session Notifications)

Use `$_SESSION['notify']` for post-redirect messages:

```php
$_SESSION['notify'] = [
    'type'    => 'success',   // success | error | warning | info
    'title'   => 'Success',
    'message' => 'Record updated!'
];
$this->redirect(url('admin/users'));
```

The layout automatically picks this up and shows a SweetAlert toast via `swal_helper.js`.

---

## Model Standards

Models extend `core/Model.php`. Set `$table` and get CRUD for free:

```php
class User extends Model {
    protected $table = 'users';
}

// Usage:
$user = new User();
$all = $user->all();
$one = $user->find($id);
$user->create(['username' => 'new', 'email' => 'test@x.com']);
$user->update($id, ['email' => 'updated@x.com']);
$user->delete($id);
```

For complex queries, use `global $connection` and write raw SQL.

---

## Authentication Guard Pattern

```php
// In every admin controller constructor or method:
private function requireAdmin() {
    $this->authService->requireAuth();   // redirects to login if not logged in
    $user = $this->authService->getCurrentUser();
    if ($user['level'] != 9) {
        http_response_code(403);
        die('Admin access only');
    }
}
```
