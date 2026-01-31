# Step-by-Step: Host School System on Railway

Do these in order after your code is pushed to GitHub.

---

## Step 1: Get your APP_KEY (do this first)

On your computer, in the project folder, run:

```powershell
cd D:\SchoolSystem-master
php artisan key:generate --show
```

Copy the output (e.g. `base64:JRNNBbGctdTXhaq74K8GQyYNqRzd3/qx5OuF6uJa6G8=`). You will paste it in Step 7.

---

## Step 2: Open Railway and create a project

1. Go to **https://railway.com** and log in (or sign up with GitHub).
2. Click **New Project**.
3. Choose **Deploy from GitHub repo**.
4. Select your **School System** repository.
5. Select the branch (**master** or **main**, whichever you pushed).
6. Click **Deploy**. Railway will create a service and start building (it may fail at first until you add the database and variables—that’s OK).

---

## Step 3: Add PostgreSQL database

1. On the **Project** page, click **+ New**.
2. Click **Database**.
3. Choose **PostgreSQL**.
4. Wait until the database service shows as running (green).
5. Click the **Postgres** service → **Variables** tab. You will see `DATABASE_URL`. You’ll reference it in the app in Step 7.

---

## Step 4: Configure the app service (build & deploy)

1. Click your **app service** (the one connected to GitHub, not the database).
2. Go to the **Settings** tab (or the gear icon).
3. Find **Build**:
   - Set **Custom Build Command** to:  
     `npm run build`
4. Find **Deploy** (or **Deploy Command** / **Pre-Deploy**):
   - If you see **Pre-Deploy Command**, set it to:  
     `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
   - If you only see **Custom Start Command**, leave it **empty** (Railway will use the default for Laravel).

---

## Step 5: Generate a public URL

1. Still on your **app service**, open the **Settings** tab.
2. Find **Networking** or **Public Networking**.
3. Click **Generate Domain** (or **Add Domain**).
4. Copy the URL Railway gives you (e.g. `https://schoolsystem-production-xxxx.up.railway.app`). You’ll use it in the next step.

---

## Step 6: Add environment variables

1. Click your **app service** (the web app, not Postgres).
2. Go to the **Variables** tab.
3. Click **Raw Editor** (or **Add Variable** and add one by one).
4. Paste the following and **edit the placeholders**:

```env
APP_NAME=SchoolSystem
APP_ENV=production
APP_DEBUG=false
APP_KEY=PASTE_YOUR_APP_KEY_FROM_STEP_1
APP_URL=PASTE_YOUR_RAILWAY_URL_FROM_STEP_5

LOG_CHANNEL=stderr
LOG_LEVEL=warning
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter

DB_CONNECTION=pgsql
DATABASE_URL=${{Postgres.DATABASE_URL}}

SESSION_DRIVER=cookie
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

# Required so nginx uses public/ as web root (fixes CSS/JS not loading)
NIXPACKS_PHP_ROOT_DIR=/app/public
```

Replace:

- `PASTE_YOUR_APP_KEY_FROM_STEP_1` → the full `base64:...` value from Step 1.
- `PASTE_YOUR_RAILWAY_URL_FROM_STEP_5` → the **exact** URL from Step 5, including `https://` (e.g. `https://schoolsystem-production-xxxx.up.railway.app`). This is required for CSS/JS and links to load correctly.
- If your database service is **not** named `Postgres`, change `Postgres` in `${{Postgres.DATABASE_URL}}` to your database service name.

5. **If the build fails with “ext-zip” or “ext-gd” missing:** add this variable so the builder installs PHP extensions:
   - **Name:** `RAILPACK_PHP_EXTENSIONS` (or `NIXPACKS_PHP_EXTENSIONS` on older Railway)
   - **Value:** `zip,gd`

6. Save (e.g. **Update Variables** or **Save**).

---

## Step 7: Redeploy the app

1. Go to the **Deployments** tab of your app service.
2. Click the **⋮** (three dots) on the latest deployment → **Redeploy**,  
   **or** push a small change to GitHub so Railway deploys again.
3. Wait until the deployment status is **Success** or **Active**.

---

## Step 8: Run database seed (default users)

You only need to do this once.

**Option A – Railway dashboard (if available)**  
If your app service has a **Shell** or **Console**, open it and run:

```bash
php artisan migrate --force
php artisan db:seed --force
```

**Option B – Railway CLI**

1. Install: https://docs.railway.com/guides/cli  
2. In a terminal:

```powershell
cd D:\SchoolSystem-master
railway login
railway link
railway run php artisan migrate --force
railway run php artisan db:seed --force
```

After this, default logins (e.g. `superadmin` / `password`) from `database/seeders/UserSeeder.php` will work.

---

## Step 9: Open your app

1. Go to the URL you set as `APP_URL` (from Step 5).
2. You should see the login page.
3. Log in with e.g. **superadmin** / **password** (or another user from the seeder).

---

## Quick checklist

| Step | What to do |
|------|------------|
| 1 | Run `php artisan key:generate --show` and copy `APP_KEY` |
| 2 | Railway → New Project → Deploy from GitHub repo → select repo & branch |
| 3 | + New → Database → PostgreSQL |
| 4 | App service → Settings → Custom Build: `npm run build`, Pre-Deploy: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh` |
| 5 | App service → Settings → Networking → Generate Domain → copy URL |
| 6 | App service → Variables → paste env and set `APP_KEY`, `APP_URL`, `DATABASE_URL=${{Postgres.DATABASE_URL}}` |
| 7 | Redeploy app |
| 8 | Run `php artisan migrate --force` and `php artisan db:seed --force` (once) |
| 9 | Open `APP_URL` in browser and log in |

---

## If something goes wrong

- **500 Internal Server Error**
  1. **See the real error**: In your app service **Variables**, set `APP_DEBUG=true` (and optionally `LOG_LEVEL=debug`). Redeploy, then open your app URL again—Laravel will show the exception and message. **Set `APP_DEBUG=false` again after debugging.**
  2. **Check variables**: Ensure `APP_KEY` is set (from Step 1, e.g. `base64:...`) and `APP_URL` matches your Railway domain (e.g. `https://yourservice.up.railway.app`). Redeploy after changing variables.
  3. **Check logs**: In the app service, open **Deployments** → click the latest deployment → **View Logs**. Look for PHP/Laravel errors (e.g. "No application encryption key", database connection errors).
  4. **Clear config cache**: If you changed env vars but still get 500, redeploy so the Pre-Deploy script runs again (it runs `optimize:clear` then `config:cache` from current env).
- **Database error**: Ensure `DATABASE_URL=${{Postgres.DATABASE_URL}}` and the Postgres service name matches.
- **Blank or broken page**: Make sure **Custom Build Command** is `npm run build` and the last deploy succeeded.
- **Logs**: In the app service, open **Deployments** → click a deployment → **View Logs**.

That’s it. Once these steps are done, your app is hosted on Railway.
