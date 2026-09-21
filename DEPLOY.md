# Aqdi (عقدي) Backend — Deployment Guide

Laravel 10 API. Base paths: `/api/v2` (app/web) and `/api/admin` (dashboard).

This app is **paste-the-key-and-go**: it runs end-to-end with no external keys
(test-mode payments, logged OTP, no-op push) and turns real the instant you add
keys and flip the driver values. You only ever edit `.env`.

---

## 1. What the owner must obtain (the only manual work)

| Integration | Keys to paste | Where to get them |
|-------------|---------------|-------------------|
| **Moyasar** (payments) | `MOYASAR_SECRET_KEY` (`sk_live_…`), `MOYASAR_PUBLISHABLE_KEY` (`pk_live_…`) | https://dashboard.moyasar.com → Settings → API Keys |
| **Taqnyat** (SMS/OTP, KSA) | `TAQNYAT_BEARER`, `TAQNYAT_SENDER` (approved sender), `TAQNYAT_SMS_ID` | https://taqnyat.sa → Dashboard → API / Senders |
| **Twilio** (alternative SMS) | `TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_PHONE` | https://console.twilio.com |
| **Firebase** (push) | service-account JSON file + `FIREBASE_PROJECT_ID` | Firebase Console → Project Settings → Service accounts → Generate new private key |

You do **not** need any of these to boot — pick them up when you want each
feature to become real.

---

## 2. Manual / bare-metal deploy

```bash
# 0. Requirements: PHP 8.2+, Composer 2, MySQL 8, nginx (or Apache).
#    PHP extensions: pdo_mysql, mbstring, bcmath, gd, zip, exif, intl, openssl, curl.

# 1. Get the code and configure the environment
cp .env.example .env
#    → edit .env: APP_ENV=production, APP_DEBUG=false, DB_*, and any keys you have.

# 2. Install production dependencies
composer install --no-dev --optimize-autoloader

# 3. Generate the app key (only if APP_KEY is empty)
php artisan key:generate

# 4. Create the schema
php artisan migrate --force

# 5. Seed LOOKUP / reference tables + the admin account (see §4 — NOT the demo data)
php artisan db:seed --class=Database\\Seeders\\RegionSeeder --force
#    ...(full recommended list in §4)...

# 6. Public storage symlink
php artisan storage:link

# 7. Cache config / routes / views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Permissions (nginx/php-fpm must own the writable dirs)
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

Point nginx at the **`public/`** directory. A ready server block is in
`docker/nginx.conf` (change `fastcgi_pass` to your php-fpm socket/host).

### Queue worker
`QUEUE_CONNECTION` defaults to `sync` (jobs run inline — fine to start).
If you switch it to `database`/`redis`, run a worker under supervisor/systemd:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### After changing `.env` later
Re-run `php artisan config:cache` (and `route:cache`) so cached config picks up
the new values — otherwise the pasted keys appear to "not take effect".

---

## 3. Docker deploy (app + mysql + nginx)

```bash
cp .env.example .env          # fill DB_* and any keys
# APP_KEY must be set BEFORE the first boot: the entrypoint runs config:cache on start,
# so a key generated afterwards would not be picked up until the container restarts.
php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'   # paste as APP_KEY= in .env
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force   # or the lookup-only list in §4
```

The `app` container entrypoint (`docker/entrypoint.sh`) auto-runs
`migrate --force`, `storage:link` and the config/route/view caches on every boot.
Whenever you edit `.env` afterwards run `docker compose -f docker-compose.prod.yml restart app`
(the entrypoint re-caches). Uploaded files are stored in the `storage` named volume, which nginx
mounts read-only and serves under `/storage/*`; `.dockerignore` keeps the local `.env`, sqlite
files and `vendor/` out of the image.

---

## 4. Seeding: lookup data vs demo data

`php artisan db:seed` (the full `DatabaseSeeder`) also inserts **demo data**
(`AnalysisSeeder`, `ContractRealDataSeeder`, `UserSeeder`, `BlogSeeder`,
fake contracts + payments). For a clean production DB, run only the
reference/lookup seeders plus the admin account:

```bash
for S in RegionSeeder CitySeeder \
         ContractStatusSeeder ContractPeriodSeeder ReaEstatTypeSeeder \
         ReaEstatUsageSeeder UnitTypeSeeder UnitUsageSeeder \
         PaymentTypeSeeder BankAccountSeeder ServicesPricingSeeder \
         QuestionSeeder InstructionSectionSeeder WebsiteImageSeeder SettingsSeeder \
         RoleSeeder PermissionSeeder AdminSeeder; do
  php artisan db:seed --class="Database\\Seeders\\$S" --force
done
```

> `AdminSeeder` creates the initial dashboard admin login (**employee** `admin@aqdi.com` / `Admin@123`,
> role `admin`; the dashboard `POST /api/admin/employees/login` authenticates against `employees`) — change its password
> after first sign-in. Skip `EmployeeSeeder`/`UserSeeder`/`BlogSeeder`/
> `AnalysisSeeder`/`ContractRealDataSeeder` in production.

---

## 5. Going live per integration (flip a value in `.env`)

**Payments (Moyasar)**
```
PAYMENTS_DRIVER=moyasar
MOYASAR_SECRET_KEY=sk_live_xxx
MOYASAR_PUBLISHABLE_KEY=pk_live_xxx
MOYASAR_TEST_MODE=false
```
With `PAYMENTS_DRIVER=test` (or, outside production, simply no secret key) the backend
simulates a successful charge and marks the contract paid — the whole flow works with no
account. In `APP_ENV=production` a *missing* key is never treated as test-mode: the public
payment endpoint would otherwise let anyone mark contracts paid for free, so payments are
rejected and a critical log line is written until the key is set. Payment page language is
forced to Arabic (`MOYASAR_LOCALE=ar`).

**OTP / SMS**
```
OTP_DRIVER=taqnyat            # or twilio
TAQNYAT_BEARER=xxxxx
```
With `OTP_DRIVER=log` (or missing provider keys) no SMS is sent — the code is
written to the log **and** stored in `sms_logs` so login still works (see §6).

**Firebase push**
```
FIREBASE_DISABLED=false
FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
FIREBASE_PROJECT_ID=your-project-id
```
Until a valid credentials file exists, push is a silent no-op (never crashes).

Then: `php artisan config:cache`.

---

## 6. Retrieving the OTP in test-mode (no SMS account)

When `OTP_DRIVER=log`, request an OTP (signup/login/forgot) then read it back:

```bash
# From the log:
tail -f storage/logs/laravel.log     # look for "OTP (test-mode, no SMS sent)"

# Or from the database (sms_id = 'test-mode', message contains the code):
php artisan tinker --execute="echo \App\Models\SmsLog::latest('id')->first()->message;"
```

Enter that code in the verification endpoint to complete login/verification.

---

## 7. Post-deploy checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
- [ ] `php artisan route:list` runs clean
- [ ] HTTPS terminated; `SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL`, `DASHBOARD_URL` set
- [ ] `storage/` and `bootstrap/cache/` writable by the web user
- [ ] Config/route/view caches built
- [ ] Real keys pasted for the integrations you want live; `config:cache` re-run
