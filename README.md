Below is a **clean, professional README** written **only for site/project information**, based strictly on what you have provided and implemented so far.
(No extra explanations, no future suggestions.)

---

# 📊 Capacity Tracker – Project README

## 📌 Project Overview

**Capacity Tracker** is a Laravel-based internal web application designed to manage and visualize **resource capacity planning**, including working hours, allocations, utilization, and availability.
The system provides a dynamic UI for managing resources, leaves, allocations, and system-level working hour settings.

---

## 🧱 Tech Stack

### Backend

* **Laravel** (PHP Framework)
* **MySQL** (Database)
* **Laravel Config & Cache**
* **Eloquent ORM**

### Frontend

* **Blade Templates**
* **Bootstrap (Modals, Buttons, Layouts)**
* **Vanilla JavaScript**
* **AJAX / Fetch API**
* **jQuery** (for legacy UI handling)

---

## 📂 Project Structure (Key Areas)

```
/app
 ├── Http/Controllers
 ├── Models
 ├── Helpers
/config
 ├── constants.php
/database
 ├── migrations
/resources
 ├── views
/public
 ├── css
 ├── js
```

---

## ⚙️ Configuration & Constants

### `config/constants.php`

This file contains **default system-level values** and is **not modified at runtime**.

```php
return [
    'cache_ver' => env('SCRIPT_VERSION', '1.0.0'),
    'daily_working_hours' => 8.5,
    'max_hours_per_day' => 10,
];
```

### Purpose

* Acts as **fallback defaults**
* Safe with `php artisan config:cache`
* No frontend write access

---

## 🎛 UI Features

### Resource-Style Management UI

* Button-triggered modal
* Form fields for numeric input (decimal support)
* Save & Cancel actions
* AJAX-based updates
* Instant reflection after reload

### Validation

* Numeric only
* Decimal support (e.g., `8.5`)
* Min / Max hour limits enforced

---

## 📊 Capacity & Utilization Logic

* Supports decimal hour calculations
* Handles floating-point precision safely
* UI values are rounded for display
* Over-allocation is clearly shown:

  ```
  Available + Over (Over)
  ```

---

## 🧮 Number Handling Rules

* Regex validation for numbers and decimals
* `parseFloat()` used instead of `parseInt()`
* `toFixed()` applied for UI display only
* Raw values preserved for calculations

---

## 🎨 Asset & Cache Handling

### CSS / JS Versioning

* Cache busting using `SCRIPT_VERSION`
* Prevents hard refresh issues after deployment
* Example:

```blade
?v={{ config('constants.cache_ver') }}
```

---

## 🔐 Security & Access Control

* Frontend updates handled via secured routes
* CSRF protection enabled
* Designed for admin-only access to system settings

---

## 🧪 Environment Requirements

* PHP 8+
* Laravel-compatible web server (Apache / Nginx)
* MySQL
* Composer
* Node.js (if asset build is used)

---

## 📌 Key Design Principles

* Config files are **static**
* Runtime values come from **database**
* Frontend never modifies server files
* Cache-safe architecture
* Clear separation of defaults vs dynamic values

---

## ✅ Current Scope

* Resource capacity planning
* Dynamic working hour management
* Clean admin UI
* Reliable cache & precision handling

---

**End of README**
