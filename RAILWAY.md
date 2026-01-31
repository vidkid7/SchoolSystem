# Deploy School System on Railway

This guide walks you through hosting this Laravel app on [Railway](https://railway.com).

---

## Prerequisites

- A [Railway](https://railway.com) account (free tier works)
- Your project pushed to **GitHub** (Railway deploys from GitHub)

---

## Option A: Quick deploy (app + database)

### 1. Push code to GitHub

If the project isn’t on GitHub yet:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

### 2. Create a Railway project

1. Go to [railway.com](https://railway.com) and log in.
2. Click **New Project**.
3. Choose **Deploy from GitHub repo**.
4. Select your School System repo and the branch (e.g. `main`).

### 3. Add a database

1. In the project, click **+ New**.
2. Choose **Database** → **PostgreSQL** (or **MySQL** if available).
3. Railway will create the DB and expose a `DATABASE_URL` (or MySQL vars).

### 4. Configure the app service

1. Click your **app service** (the one from the GitHub repo).
2. Open **Settings**:
   - **Build**: set **Custom Build Command** to:  
     `npm run build`
   - **Deploy**: set **Custom Start Command** to (optional):  
     leave empty so Railway uses default (PHP/Nginx), or set to:  
     `chmod +x ./railway/init-app.sh 2>/dev/null; ./railway/init-app.sh; php-fpm`  
     If you prefer to run migrations manually once, skip the custom start and use **Variables** + **Redeploy** after adding env (see below).
   - Or use **Pre-Deploy Command** (if your plan supports it):  
     `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`

### 5. Set environment variables

In the app service, go to **Variables** → **Raw Editor** and add (replace values as needed):

```env
APP_NAME=SchoolSystem
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://YOUR-APP-UP.railway.app

LOG_CHANNEL=stderr
LOG_LEVEL=warning
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter

DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}

SESSION_DRIVER=cookie
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

**Important:**

- **APP_KEY**: Generate locally with `php artisan key:generate --show` and paste the `base64:...` value.
- **APP_URL**: After first deploy, open **Settings** → **Networking** → **Generate Domain**. Set `APP_URL` to that URL (e.g. `https://schoolsystem-production.up.railway.app`).
- **DATABASE_URL**: If you named the Postgres service something other than `Postgres`, use `${{YourDbServiceName.DATABASE_URL}}` (see [Railway variables](https://docs.railway.com/guides/variables#referencing-another-services-variable)).

If Railway gives you **MySQL** instead of Postgres:

- Use `DB_CONNECTION=mysql` and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from the MySQL service (or `DATABASE_URL` if MySQL provides it and Laravel supports it).

### 6. Run migrations and seed (first time)

**Option 1 – Pre-Deploy (recommended)**  
In the app service **Settings** → **Deploy** → **Pre-Deploy Command** set:

```bash
chmod +x ./railway/init-app.sh && ./railway/init-app.sh
```

This runs `php artisan migrate --force` and caches on each deploy.

**Option 2 – One-off via Railway CLI**

```bash
railway link
railway run php artisan migrate --force
railway run php artisan db:seed --force
```

### 7. Generate a public URL

1. App service → **Settings** → **Networking**.
2. Click **Generate Domain**.
3. Set **Variables** → `APP_URL` to this URL (with `https://`).
4. Redeploy if needed.

### 8. (Optional) Seed default users

If you didn’t run the seeder in Pre-Deploy, run once:

```bash
railway run php artisan db:seed --force
```

Default logins are in `database/seeders/UserSeeder.php` (e.g. `superadmin` / `password`).

---

## Option B: Deploy with Railway CLI (no GitHub)

1. Install CLI: <https://docs.railway.com/guides/cli>
2. In the project root:

```bash
railway login
railway init
railway add --database postgres
railway up
```

3. In the dashboard: set **Variables** (same as above), then **Generate Domain** and set `APP_URL`.
4. Run migrations:

```bash
railway run php artisan migrate --force
railway run php artisan db:seed --force
```

---

## Files added for Railway

| File | Purpose |
|------|--------|
| `railway/init-app.sh` | Runs migrations, `storage:link`, and Laravel cache (used by Pre-Deploy or custom start). |

---

## Troubleshooting

- **500 / APP_KEY**: Set `APP_KEY` in Variables (from `php artisan key:generate --show`).
- **DB connection**: Ensure `DATABASE_URL` (or MySQL vars) references the correct Railway DB service.
- **APP_URL**: Must match the generated Railway domain (with `https://`).
- **Logs**: Use **View Logs** in the service; with `LOG_CHANNEL=stderr` they appear in Railway’s log stream.
- **Storage**: Railway’s disk is ephemeral; uploads won’t persist across deploys. For production file storage, use S3 or similar and configure `config/filesystems.php` and `.env`.

---

## Summary checklist

1. Push code to GitHub.
2. New Project → Deploy from GitHub repo.
3. Add PostgreSQL (or MySQL) and note `DATABASE_URL`.
4. App service: Custom Build = `npm run build`; set Pre-Deploy to run `railway/init-app.sh` (or run migrations manually).
5. Variables: `APP_KEY`, `APP_URL`, `DATABASE_URL`, `DB_CONNECTION=pgsql`, `LOG_CHANNEL=stderr`.
6. Generate Domain and set `APP_URL` to it.
7. Deploy; run `db:seed` once if needed.

After that, your app should be live at the generated Railway URL.
