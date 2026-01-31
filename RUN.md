# How to Run School System (Laravel)

This is a **Laravel 10** School System app (PHP 8.3+, MySQL, Vite frontend).

---

## Prerequisites

- **PHP 8.3+** with extensions: `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`
- **Composer** ([getcomposer.org](https://getcomposer.org))
- **Node.js** (for frontend build)
- **MySQL** (or use SQLite — see below)

---

## Quick run (after prerequisites)

1. **Install PHP dependencies**
   ```bash
   composer install
   ```

2. **Environment**
   - Copy `.env.example` to `.env` if you don’t have `.env`:
     ```bash
     copy .env.example .env
     ```
   - Generate app key:
     ```bash
     php artisan key:generate
     ```
   - Edit `.env`: set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for MySQL, or set `DB_CONNECTION=sqlite` and create `database/database.sqlite` for SQLite.

3. **Database**
   - Create an empty MySQL database (e.g. `school_system`), then:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Storage link**
   ```bash
   php artisan storage:link
   ```

5. **Frontend**
   ```bash
   npm install
   npm run build
   ```
   For development with hot reload: `npm run dev` (keep it running in another terminal).

6. **Start the app**
   ```bash
   php artisan serve
   ```
   Open **http://127.0.0.1:8000**

---

## One-time setup script (PowerShell)

From the project root, run:

```powershell
.\setup-and-run.ps1
```

This script runs: `composer install` → copy `.env` → `key:generate` → `migrate` → `db:seed` → `storage:link` → `npm install` → `npm run build` → `php artisan serve`.  
It will stop with a clear message if `php` or `composer` are not in your PATH.

---

## Using SQLite (no MySQL)

1. In `.env` set:
   ```
   DB_CONNECTION=sqlite
   ```
   (Comment out or remove `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` if you want to rely only on SQLite defaults.)

2. Create an empty file:
   ```
   database/database.sqlite
   ```

3. Then run:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

---

## Login after seeding

Check `database/seeders/UserSeeder.php` for default user(s) and passwords created by the seeder.
