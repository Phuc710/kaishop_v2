---
title: "Architecture and Standards"
description: "Guidelines for CSS, JS, and project structure."
---

# Architecture and Code Standards

This document outlines the directory structure and the standard conventions for writing CSS and JS in the KaiShop v2 project, particularly for the Admin panel. By following these guidelines, you ensure code is maintainable, scalable, and easy for any developer to understand.

---

## 📂 Directory Structure

### Core Application
* `app/` - Contains the core OOP components.
  * `Controllers/` - System Controllers handling business logic.
  * `Models/` - Database models representing the schema.
  * `Services/` - Helper services (e.g., `AuthService`).
  * `Validators/` - Input validation classes.

### Views & UI
* `views/` - Contains all frontend templates (PHP files rendering HTML).
  * `admin/` - Admin panel views.
    * `layout/` - Shared admin structures (`head.php`, `nav.php`, `foot.php`).
    * `[feature]/` - Feature-specific views (e.g., `products`, `users`, `finance`).

### Public Assets (CSS, JS, Images)
* `assets/` - Static assets served to the client.
  * `css/` - Master stylesheets.
  * `js/` - Master JavaScript files.
  * `images/` - Static images.

### System & Configuration
* `hethong/` - Legacy procedural includes and global configurations.
* `config/` - Main configuration files (`database.php`, `routes.php`).

---

## 🎨 Global CSS Standards (Admin Panel)

**The Rule of One:** All core layout, table styling, and recurring UI component styles for the Admin Panel must live in **one** file: `assets/css/admin-pages.css`.

### 1. No Inline Styles
Do **NOT** write `<style>` blocks inside individual view files (e.g., inside `users.php` or `giftcodes.php`).
Do **NOT** write `style="..."` attributes on elements unless absolutely necessary for dynamic layout calculations (like progress bars).

### 2. File Organization
All admin views load `admin/head.php`, which automatically imports `assets/css/admin-pages.css`.

### 3. Naming Conventions (BEM-lite)
Use a simplified Block-Element-Modifier (BEM) approach to keep CSS specific and conflicts low.
* **Block:** The main component (e.g., `.custom-card`).
* **Element:** A child of the block (e.g., `.custom-card-header`).
* **Modifier:** A variation of the block/element (e.g., `.custom-card--highlight`).

### 4. Global Variables
Define standard variables at the top of shared CSS files using `:root`.
```css
:root {
    --primary-color: #8b5cf6;
    --card-bg: #ffffff;
    --text-color: #374151;
}
```

### 5. Table Styling Standard
All Admin DataTables must use the following classes, with colors and borders handled globally via `admin-pages.css`.
```html
<!-- Correct Standard Table Classes -->
<table id="example" class="table text-nowrap table-hover table-bordered admin-table w-100">
```
*Note: We have explicitly removed `table-striped` globally to maintain a clean white aesthetic for all rows.*

---

## ⚡ JavaScript Standards

### 1. Centralized Helpers
Functions that are reused across multiple pages (e.g., alerts, API requests) must reside in shared JS files within `assets/js/`.
* **Example:** `assets/js/swal_helper.js` handles all SweetAlert popups (`SwalHelper.success()`, `SwalHelper.error()`).

### 2. Page-Specific Logic
For heavy logic specific to a single page (e.g., initializing a complex graph or a specific DataTable), place the JavaScript script block at the **bottom** of the view file, right before the closing `</body>` tag, or link a specific `.js` file for that feature.

### 3. Event Listeners
Avoid inline handlers where possible (e.g., prefer `document.getElementById('btn').addEventListener` over `onclick="doSomething()"`), unless using specific legacy patterns where it's established.

---

## 🚀 Production Best Practices

1. **Minification:** In a true production deployment, CSS and JS in the `assets/` folder should be minified (using tools like Webpack or Vite) but source files should remain readable with clear comments.
2. **Caching:** Update the query string for assets when changes are deployed to bust browser cache (e.g., `href="assets/css/admin-pages.css?v=1.2"`, though right now the `asset()` helper handles path resolution).
3. **Commenting:** Leave brief block comments separating major UI sections in CSS (e.g., `/* --- TABLES --- */`).
