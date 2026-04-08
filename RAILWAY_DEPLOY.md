# Railway Deployment Guide

This project deploys as **3 Railway services** in a single project:

| Service     | Source              | Public? | Healthcheck |
|-------------|---------------------|---------|-------------|
| `MySQL`     | Railway plugin      | No      | built-in    |
| `app`       | `Dockerfile` (root) | **Yes** | `/up`       |
| `ml-api`    | `ml_api/Dockerfile` | No      | `/health`   |

## 1. Prepare locally

Generate a Laravel app key (paste into `APP_KEY` later):

```bash
cd src && php artisan key:generate --show
```

Generate a long random ML token (used by both services):

```bash
openssl rand -hex 32
```

## 2. Provision MySQL

1. New Project → **+ New** → **Database** → **Add MySQL**.
2. Wait for it to provision. It auto-exposes:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
3. (Optional) Rename the service to `MySQL` so the variable references in this guide match.

## 3. Deploy the ML API service

1. **+ New** → **GitHub Repo** → select this repo.
2. **Settings → Source**:
   - Root Directory: `ml_api`
   - Dockerfile Path: `Dockerfile`
3. **Settings → Networking**: do **not** generate a public domain. Keep it private.
4. **Variables**: paste the *ML API* block from `.railway.env.example`.
5. Rename the service to `ml-api` (lowercase, hyphenated) so the Laravel reference variable resolves.
6. Deploy. Verify in the deploy logs that uvicorn started on `$PORT`.

## 4. Deploy the Laravel app service

1. **+ New** → **GitHub Repo** → same repo.
2. **Settings → Source**:
   - Root Directory: `/`
   - Dockerfile Path: `Dockerfile`
3. **Settings → Networking** → **Generate Domain**.
4. **Variables**: paste the *Laravel* block from `.railway.env.example`. Set `APP_KEY` to the value you generated, and `ML_API_TOKEN` to match the ML service.
5. Deploy. The entrypoint will:
   - rewrite nginx to listen on `$PORT`
   - run `php artisan migrate --force`
   - cache config / routes / views
   - start php-fpm + nginx via supervisord

## 5. Persistent storage (recommended)

Railway containers are ephemeral. Add Volumes:

| Service  | Mount path             | Purpose                        |
|----------|------------------------|--------------------------------|
| `app`    | `/var/www/storage/app` | Laravel uploaded files         |
| `ml-api` | `/app/models`          | Trained `.joblib` model files  |

Without these, uploads and trained models are lost on every redeploy.

## 6. First-deploy checklist

- [ ] MySQL service is healthy
- [ ] `ml-api` deploy logs show `Uvicorn running on http://0.0.0.0:<port>`
- [ ] `app` deploy logs show migrations succeeded
- [ ] Public domain returns 200 at `/up`
- [ ] Login flow works
- [ ] Trigger a training run from the admin panel and confirm `ai_model_metrics` row appears

## 7. Common issues

| Symptom                                 | Fix                                                                 |
|-----------------------------------------|---------------------------------------------------------------------|
| 502 from Railway edge                   | Healthcheck failing — verify `/up` returns 200 and `$PORT` binding  |
| `SQLSTATE[HY000] [2002]`                | `DB_HOST` reference wrong; check the MySQL service name             |
| Mixed-content / wrong scheme in URLs    | `trustProxies(at: '*')` must be set (already done in `bootstrap/app.php`) |
| `APP_KEY` missing error                 | Set `APP_KEY` to the `base64:...` value you generated locally       |
| ML API returns 401                      | `ML_API_TOKEN` must be identical on both services                   |
| Model retrains every cold start         | Attach a Volume at `/app/models` on the `ml-api` service            |
| Migration locks on redeploy             | Set `RUN_MIGRATIONS=false` and run manually via `railway run`       |

## 8. Manual commands via Railway CLI

```bash
railway login
railway link                              # link this repo to the project
railway run --service app php artisan migrate:status
railway run --service app php artisan db:seed --force
railway run --service app php artisan tinker
```
