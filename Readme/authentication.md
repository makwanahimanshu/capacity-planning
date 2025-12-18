Got it ✅ — you already have **Laravel 12.33.0** installed and now want to **set up Laravel’s default authentication system (login, register, logout, etc.)**.

Let’s go step by step 👇

---

## 🧩 Step 1: Confirm Laravel Installation

Run this command in your project root to confirm your Laravel version:

```bash
php artisan --version
```

You should see something like:

```
Laravel Framework 12.33.0
```

---

## ⚙️ Step 2: Install Laravel Breeze (Recommended for Laravel 12+)

For Laravel 10–12, **Laravel Breeze** is the official lightweight starter kit for authentication (built on Blade, Livewire, Vue, or React — you can choose).

Run these commands inside your project:

```bash
composer require laravel/breeze --dev
```

Then install Breeze with the Blade scaffolding (since you’re using the default Laravel Blade UI):

```bash
php artisan breeze:install blade
```

This will scaffold:

* Login, Register, Forgot Password, Reset Password
* Email Verification
* Profile management (for authenticated users)
* Tailwind CSS styling

---

## 🧱 Step 3: Install Frontend Dependencies

Run:

```bash
npm install
npm run dev
```

This compiles the Tailwind and JS assets for the auth pages.

---

## 🗄️ Step 4: Run Database Migrations

Before migrating, make sure your `.env` file is configured properly for your database.

Then run:

```bash
php artisan migrate
```

This will create the default `users`, `password_reset_tokens`, and related tables.

---

## 🚀 Step 5: Start the Server

Run:

```bash
php artisan serve
```

Now visit:

```
http://127.0.0.1:8000/register
```

or

```
http://127.0.0.1:8000/login
```

You’ll see the default Laravel authentication pages ready to use 🎉

---

## 🔐 Optional: Authentication Middleware Protection

You can protect any route using `auth` middleware:

```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');
```

---

## 🧹 Step 6: (Optional) Publish Breeze Views for Customization

If you want to customize the login/register pages:

```bash
php artisan vendor:publish --tag=breeze-views
```

You can then modify files inside:

```
resources/views/auth/
resources/views/layouts/
```

---

## ✅ Summary

| Task                  | Command                                 |
| --------------------- | --------------------------------------- |
| Install Breeze        | `composer require laravel/breeze --dev` |
| Scaffold Auth (Blade) | `php artisan breeze:install blade`      |
| Install Frontend      | `npm install && npm run dev`            |
| Run Migrations        | `php artisan migrate`                   |
| Serve App             | `php artisan serve`                     |

---

Would you like to set up **email verification and password reset** with this too?
I can show how to configure those parts next (they’re included in Breeze but need small `.env` tweaks).
