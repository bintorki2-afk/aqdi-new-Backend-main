# Deep QA — Aqdi backend (Laravel 10, `/api/v2` + `/api/admin`)

Independent re-verification of the fixes in `BACKEND_FIXES_LOG.md` / `CONTRACT_CHANGES.md`, plus an
active attempt to break the API (authz, validation, money, business rules, migrations, deploy).

Environment: local sqlite (`APP_ENV=local`, `PAYMENTS_DRIVER=test`, `OTP_DRIVER=log`), `php artisan serve` on
`127.0.0.1:8000`. Tokens: user #1 (`U1`), user #2 (`U2`), non-admin employee (`E1`), system-admin employee
(`ADM`, employee `admin@aqdi.com` created by the fixed `AdminSeeder`). Helper scripts (`e2e.sh`, `req.sh`,
`pricing_matrix.php`, `pricing_vat.php`, `fieldcheck.py`, `nplus1.php`) lived in the session scratchpad; the
exact commands are quoted in the Evidence column.

Legend: **PASS** verified OK · **FAIL→FIXED** bug found and fixed here · **NOTE** works, needs owner attention.

---

## A. Check matrix

### 1) Pricing — `ContractPricing` single source of truth

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 1.1 | Residential & commercial × 3m / 6m / 1y / 2y / 3y / other (2y5m, 1y1m) → one `fee/vat/total` | PASS | `pricing_matrix.php`: housing 3m=6m=1y=**249**, 2y=399, 3y=549, 2y5m=549, 1y1m=399; commercial 3m=6m=1y=**349**, 2y=**849**, 3y=1349, 2y5m=1349, 1y1m=849 (ceil to whole years) |
| 1.2 | Standard wizard path (`contract_term_in_years` = `contract_periods.id`, **no** `duration_preset`) priced by DocFee | **FAIL→FIXED** | The website sends `contract_term_in_years` for every non-custom duration; `DocFee::forContract()` returned `null` and `ContractPricing::fee()` fell back to `contract_periods.price` (seed: 150/400/750/**1400** housing, 250/700/**2500** commercial) while the website shows 249/349 → the "849 vs 799"-class conflict was still alive on the *main* path. `DocFee::monthsFromContractPeriod()` now maps the period (شهري/ربع سنوي/نصف سنوي/سنوي, numeric labels, سنتين; unknown/dangling → 12) into the same rule. After: all 7 seeded periods → 249 / 349 (`pricing_matrix.php` "period#" rows). |
| 1.3 | VAT rate 0 → `vat 0`, label "مجانًا" | PASS | `pricing_matrix.php` |
| 1.4 | VAT rate 15 proportional; rounding; `fee+vat+meter == total` | PASS | `pricing_vat.php` (rolled-back txn): housing 1y vat 37.35, 2y 59.85; commercial 2y 127.35, 2y5m 202.35; 15.5 % of 349 = 54.10 |
| 1.5 | Negative VAT rate clamps to 0 | PASS | rate −5 → vat 0 |
| 1.6 | Meter fees added once, only for tenant-owned meters, per contract type | PASS | settings 100/50 (housing) 200/75 (commercial): totals 436.35 / 608.85 / 1251.35 / 1826.35 |
| 1.7 | Meter ownership selected in the wizard actually reaches pricing | **FAIL→FIXED** | Step 5 stores ownership per *unit*; `MeterFees` reads the *contract* columns the v2 wizard never set → meter fees could never be charged. `SubmitContractStep5Action` now rolls unit ownership up to the contract (`tenant` if any unit is tenant-owned) and `MeterFees` derives from `units` when the contract column is empty (legacy rows). Verified: contract 267 stored `electricity_meter_ownership=tenant`, `water=owner`. |
| 1.8 | Coupon > total never negative; ratio coupon on fee; capped at fee | PASS | value 99999 → coupon 349, total = VAT only 52.35; 10 % → 34.90 |
| 1.9 | Coupon endpoint before/after == finance / payment total | **FAIL→FIXED** | `CouponController::Coupon` returned `fee − discount` (no VAT / meter). Now `ContractPricing::total($c,false)` and `max(0, before − discount)`. |
| 1.10 | Legacy paths (`application_fees`, `housing_tax`/`commercial_tax` 15, `ContractPeriod.price`) no longer feed any number | PASS (after 1.2) | grep: only `ContractPricing`/`DocFee` produce money; `contract_periods.price` is informational only (NOTE-1) |
| 1.11 | Test-mode payment response carries the documented breakdown | **FAIL→FIXED** | simulated response lacked `fee/vat/vat_rate/vat_label/coupon/total` (CONTRACT_CHANGES §2) → added |
| 1.12 | Live E2E: finance == payment init == invoice == admin detail == report | PASS | contract 267 (commercial, other 2y): `GET /api/v2/financial/713667` fee 849 total 849; `GET /api/v2/payment/713667` cart 849 fee 849 total 849; `GET /api/v2/contracts/267/invoice` total_amount 849; `GET /api/admin/orders/267` total_price 849 / amount_payment 849; contract 269 (housing, سنوي id 4): 249 everywhere |
| 1.13 | Reconciliation (#35): dashboard tile == sales report == raw sum | PASS | tile `اجمالي المدفوعات في العقود` 132 528 == `reports/sales.kpis.total_sales` 132 528 == `Payment::successful()->sum` 132 528; by-type 84 201 + 48 327 = 132 528; `عقود مدفوعه` is a count (39) |

### 2) Contract data — every step for every instrument type

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 2.1 | `POST /contract/step2` for non-skip instrument types | **FAIL→FIXED** | **500** `Call to undefined method ContractPropertyAddressService::mergeRequiredPropertyAddress()` (method is `mergeRequired()`): every step-2 submission crashed. |
| 2.2 | `POST /contract/step4` with an unknown `authorization_type` | **FAIL→FIXED** | **500** (sqlite CHECK / MySQL strict enum). Added `in:` rule (v1 + v2) + Arabic message → 422. |
| 2.3 | Step 1: deed ×3 (private), endowment, trusteeship, inheritance, heirs-PoA, guardians-PoA, `property_owner_is_deceased`, instrument no./date, floors/units, registry no., address, coordinates → stored | PASS | `fieldcheck.py dbrow`: 57/61 exact (4 "diffs" are path-vs-signed-URL / list encoding) |
| 2.4 | Step 3 owner + agency block (name, id, mobile, IBAN, agent id/mobile, PoA no./date, PoA file) → stored | PASS | same |
| 2.5 | Step 4 tenant company (unified no., authorization_type, agent id/mobile, owner-record file, region/city) → stored | PASS | same |
| 2.6 | Step 5 multiple units (2) with per-unit meters / furnishing → stored | PASS | 2 `contract_units` rows, `units[]` in response |
| 2.7 | Step 6 duration / rent / guarantee / deposit / daily_fine / other_conditions_list / additional terms → stored | PASS | same |
| 2.8 | Admin detail `GET /api/admin/orders/{id}` returns every sent field | PASS | `fieldcheck.py adminshow`: **61/61** |
| 2.9 | Resume data `POST /contract/uncompleted-contract` returns every field | **FAIL→FIXED** | 59/61 — `Guarantee_amount` and `deposit` (added in the previous pass) were stored but not returned by `Step6Resource`, so a resumed wizard lost them → added. |
| 2.10 | `GET /api/v2/contracts/{id}` / list (`ContractResource`) and `Step2Resource` deed images | **FAIL→FIXED** | Returned the raw *private* disk path (`contracts/deeds/xxx.png`) / a broken `asset('storage/…')` URL instead of the signed URL → clients could not load the image and a storage path leaked. Now `DeedImage::signedUrl()` (verified `…/deed-image/image_instrument?expires=…&signature=…`). |
| 2.11 | All 13 instrument types (`electronic, electronic_tax_register, old_handwritten, strong_argument, sale_agreement, economic_cities_authority_suspended, sublease_agreement, lease_renewal, property_ownership_owner_are_deceased, property_ownership_owner_is_endowment, property_ownership_owner_are_deceased_endowment, property_ownership_owner_are_suspended, electronic_deed_from_the_ministry_of_justice`) through start + steps 1-6 | PASS | `e2e.sh <type> <housing|commercial>`: 7/7 steps 2xx for every type (after 2.1/2.2) |

### 3) Auth & AuthZ

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 3.1 | `GET /api/admin/leads` with a website **User** token | **FAIL→FIXED** | Was **200** (route only had `auth:sanctum`, which accepts website users). Added `permission:users.view`; U1 → 401, ADM → 200. Route audit (`route:list --json`): every other `/api/admin` route already carries a `permission:` gate except login/logout/me/refresh/fcm and gateway callbacks (expected). |
| 3.2 | `POST /api/v2/leads` with another user's `contract_uuid` | **FAIL→FIXED** | Was **201** and echoed the other user's `name_owner` + mobile (IDOR / PII leak). Contract is now resolved with `ownedBy(auth()->id())` → 404; `contract_uuid`/`contract_id` required. |
| 3.3 | Unauthenticated `/api/v2/contracts*`, `/financial`, `/leads`, `/invoices`, `/api/admin/*` | PASS | all 401 |
| 3.4 | U1 token vs U2 contract: show / destroy / step6 / draft / uncompleted / financial / invoice | PASS | all 404 (`ownedBy` scopes + `ContractPolicy`) |
| 3.5 | `GET /api/v2/getContracts/{uuid}`, `GET /api/v2/search/{term}` | **FAIL→FIXED** | **500** `Method V2\ContractController::getContracts does not exist` (routes registered, methods missing). Added both, ownership-scoped. |
| 3.6 | Deed-image signed URL: valid / tampered / other contract / other field / unsigned / expired / bad field / file not on public disk | PASS | 200 image/png · 403 · 403 · 403 · 403 · 403 (expires=1 and a real `now()->subMinute()` URL) · 403 · `public/storage/<path>` does not exist |
| 3.7 | A dashboard login exists after the documented production seed | **FAIL→FIXED** | `DEPLOY.md` says `AdminSeeder` creates the dashboard login, but `POST /api/admin/employees/login` authenticates against `employees`; `AdminSeeder` only wrote the unused `admins` table and `EmployeeSeeder` (dev-only) has no `admin` role → **nobody could sign in to the dashboard in production**. `AdminSeeder` now also `firstOrCreate`s employee `admin@aqdi.com` / `Admin@123` (role `admin`). Verified on a fresh prod-seeded DB: login 200, `isSystemAdmin() = true`. |
| 3.8 | Public payment-init in test mode can mark any contract paid | **FAIL→FIXED (hardened)** | `GET /api/v2/payment/{uuid}` is unauthenticated by frontend design. Test-mode auto-enabled whenever the secret key was missing, so a production box with a forgotten key would let anyone mark contracts paid for free. Implicit test-mode is now refused in `APP_ENV=production` (explicit `PAYMENTS_DRIVER=test` still honoured; missing key logs `critical`). Docs updated. See NOTE-2. |
| 3.9 | `instanceof App\Models\Employee` checks in admin controllers | **FAIL→FIXED** | 21 `App\Models\*` classes are only `class_alias()` shims and PHP never autoloads for `instanceof`, so `$request->user() instanceof Employee` was **false** unless something else had loaded the alias earlier in the request → e.g. a system admin got **403 "ليس لديك صلاحية"** on `POST /api/admin/refundable-contracts`. `AppServiceProvider::register()` now force-loads all 21 aliases (`class_exists`). Verified refund create → 200. |
| 3.10 | Refund routes by non-admin employee / website user | PASS | E1 → 403 (permission), U1 → 401 |

### 4) Validation (422 with field errors, never 500)

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 4.1 | v2 Step 3/4 national ids: letters / 9 digits / leading 3 / 11 digits | **FAIL→FIXED** | rule was only `min:10` → letters, 11 digits and `3xxxxxxxxx` accepted. Added `regex:/^[12]\d{9}$/` to owner, owner-agent, tenant and tenant-agent ids, `^7\d{9}$` to `tenant_entity_unified_registry_number` (matches the frontend), Arabic messages. All now 422 (`id letters/9 digits/starts 3/11 digits`, `agent id letters`, `tenant id 3xxx`, `CR bad/letters`). |
| 4.2 | v2 mobiles: `0512`, `+9665…`, 11 digits, `06…` | PASS | 422 / 200 (normalised to national) / 422 / 422 |
| 4.3 | Uploads: `.php` as deed / PoA; 11 MB file | PASS | 422 "يجب أن يكون بصيغة jpg…"; 413 (php `post_max_size`) — never 500 |
| 4.4 | `copy_of_the_owner_record` had no `file`/size rule | **FAIL→FIXED** | now `file|mimes|max:10240` |
| 4.5 | Step 6: negative rent / guarantee, `other` 0y0m, 31y, unknown period id, rent as text | PASS (rent: **FAIL→FIXED**) | `annual_rent_amount_for_the_unit` gained `min:0`; others already 422 |
| 4.6 | v2 422 payload has per-field errors | **FAIL→FIXED (additive)** | `BaseApiV2Request::failedValidation` returned only `message`; now also `errors: {field: [..]}` |
| 4.7 | Admin edit-order: ids letters / 9 digits / leading 3, mobiles `0512` / 11 digits, CR | PASS | all 422 with field key |
| 4.8 | Admin edit-order rejects a mobile stored by the v2 wizard | **FAIL→FIXED** | v2 stores national `5XXXXXXXX`; admin rule is `^05\d{8}$` → re-saving an unchanged order failed 422. `UpdateContractRequest::normalizeMobiles()` canonicalises 5…/05…/966…/00966…/+966… → `05XXXXXXXX` (`+966551234567` and `551234567` → 200, stored `0551234567`; `0512` still 422). |
| 4.9 | Admin edit-order enum columns (`tenant_entity`, `authorization_type`, `contract_type`, hijri/gregorian types, meter ownership, `duration_preset`…) | **FAIL→FIXED** | Unknown values hit the DB CHECK and came back as **HTTP 200 / success:true / "حدث خطأ: SQLSTATE…"** (SQL leaked). Added `ENUM_RULES` → 422 Arabic. |
| 4.10 | Admin edit-order numeric / path columns | **FAIL→FIXED** | `annual_rent_amount_for_the_unit: -5`, `step: "abc"` accepted; `image_instrument: "../../.env"` accepted (stored path is later resolved by the deed-image route). Added `NON_NEGATIVE_NUMERIC` (`numeric|min:0`) and `PATH_COLUMNS` (relative, no `..`, no scheme) rules → 422. `is_completed` moved to `EXCLUDED_COLUMNS` (payment-derived). |
| 4.11 | `Responser::apiResponse($data,$msg,false,404)` legacy call shape | **FAIL→FIXED** | 3-arg signature: the `false` landed in `$code` → every such error (order/period not found, validation, 500s) went out as **HTTP 200, success:true**. `apiResponse` now accepts the 4-arg shape (`success`, `httpCode`). |
| 4.12 | XSS strings in text fields | NOTE | Stored and returned verbatim as JSON (`name_owner: "<script>…"`); no HTML rendering server-side. Consumers (web/dashboard) must escape — standard for a JSON API. |
| 4.13 | Admin order update catch-all leaked `$e->getMessage()` (SQL) | **FAIL→FIXED** | message appended only when `APP_DEBUG`; exception `report()`ed |

### 5) Business rules

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 5.1 | Refund on unpaid contract | PASS | `POST /api/admin/refundable-contracts {contract_id: <unpaid uuid>}` → 422 "لا يمكن إنشاء طلب استرجاع إلا لعقد مدفوع" |
| 5.2 | Refund negative amount / duplicate pending | PASS | 422 / 422 "العقد مسترجع بالفعل" |
| 5.3 | Refund amount > paid | **FAIL→FIXED** | 99 999 on an 849-SAR order was accepted → now 422 "مبلغ الاسترجاع (849 ر.س هو الحد الأقصى)…"; exact paid amount → 200 |
| 5.4 | Refund approval flags the contract (`accept_retrun_contract`, `…_employee_id`) | **FAIL→FIXED** | Those columns are intentionally not mass-assignable (`ContractMassAssignmentTest`), so `syncContractAfterRefundDecision()`'s `$contract->update([...])` silently dropped them → approvals never marked the contract. Now `forceFill()->save()` (like `SetReturnContractAcceptanceAction`). `RefundableContractFlowTest` passes. |
| 5.5 | Test-mode payment marks paid exactly once, one Payment row, converts the lead | PASS | 2× `GET /payment/{uuid}`: 1st `test_mode:true`, 2nd `already_paid:true` + same payment id; `Payment::where(uuid)->count() == 1`; lead `status: paid`, `converted_at` set |
| 5.6 | Cannot double-pay a paid contract | PASS | as above (`already_paid`, no 2nd row) |
| 5.7 | Lead capture idempotent per `contract_uuid`, requires auth, never downgrades paid | PASS | 2 posts → 1 row updated; unauth 401; posting for a paid contract → `status: paid` |
| 5.8 | Lead conversion can never break payment completion | **FAIL→FIXED** | `Lead::markPaidForContractUuid()` threw `no such table: leads` when the migration was absent (failed 6 payment tests) → wrapped in try/catch + warning log |
| 5.9 | OTP test-mode stores the code; verification works; replay blocked | PASS | signup → `sms_logs.sms_id='test-mode'`, message contains the 4-digit code; wrong code 400 (attempts counter), right code 200, replay 400 "انتهت صلاحية رمز التحقق" |
| 5.10 | OTP throttling | PASS | resend: 409 ×4 (cooldown) then 429; verify brute force: 400 ×4 then 429/lock |
| 5.11 | API throttle 60/min per user | PASS | observed 429s during the instrument-type sweep (see 2.11) |

### 6) Migrations / seeders

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 6.1 | `migrate:fresh --seed` on a TEMP sqlite copy | PASS | `DB_DATABASE=<tmp>/fresh.sqlite php artisan migrate:fresh --seed --force` → exit 0; 245 contracts, 14 users, 6 employees (incl. `admin@aqdi.com` role 1), 7 periods |
| 6.2 | Production lookup-only seed (DEPLOY.md §4 list) + API boot on the near-empty DB | PASS | server on :8001 against `empty.sqlite`: all 41 parameterless public v2 GETs → 200/401/404 only; admin login 200; every parameterless admin GET (110 routes) → 2xx **except** 6.3 |
| 6.3 | Dashboard analytics on sqlite / empty DB | **FAIL→FIXED** | `GET /api/admin/analytics`, `/dashboard-analytics`, `/analytics/clients/*`, `/analytics/top-customers/*`, `/analytics/employees/most-*` → **500** `HAVING clause on a non-aggregate query` (`withCount()+having()` without GROUP BY is a MySQL-only extension). Counts → `whereHas`; top-N queries → `groupBy(<pk>)`. All 200 on both DBs. |
| 6.4 | Migrations additive only | PASS | no schema change was needed in this pass |

### 7) Config / deploy

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 7.1 | `.env.example` contains every `env()` key referenced in `config/` + `app/` | **FAIL→FIXED** | 205 referenced vs 73 present → 138 missing (Google/Meta/TikTok/Snap/Twitter ads, SEO crawl, OTP HTTP limits, admin token TTLs, AWS/Redis/Pusher/Telescope…). Appended, grouped, with the config defaults (keys whose blank value would override a non-empty default are commented). `comm` diff now 0. |
| 7.2 | `.env.example` used Laravel-11 names `CACHE_STORE` / `BROADCAST_CONNECTION` that Laravel 10 `config/cache.php`/`broadcasting.php` never read | **FAIL→FIXED** | renamed to `CACHE_DRIVER` / `BROADCAST_DRIVER` |
| 7.3 | No `env()` outside `config/` (config:cache safety) | PASS | grep clean |
| 7.4 | `config:cache && route:cache` | PASS | both "cached successfully", 597 routes, `/api/v2/app-status` 200 while cached; then cleared |
| 7.5 | Dockerfile: extensions / upload limits | **FAIL→FIXED** | pdo_mysql, gd, zip, intl, bcmath, opcache OK; php-fpm defaults (2 M / 8 M) would 413 the 10 MB uploads the API allows → `uploads.ini` (20 M / 25 M) |
| 7.6 | Image contents | **FAIL→FIXED** | no `.dockerignore` → local `.env`, `database/*.sqlite`, `vendor/`, logs baked into the image. Added `.dockerignore`. |
| 7.7 | nginx can serve uploaded files | **FAIL→FIXED** | nginx bind-mounts host `./public`, but `storage:link` runs inside the app container and the `storage` volume was not mounted into nginx → `/storage/*` (PoA, endowment docs, invoices…) 404. Added `location /storage/ { alias …storage/app/public/; }` (php denied) + read-only `storage` volume in the `web` service. |
| 7.8 | `APP_KEY` ordering | **FAIL→FIXED (docs)** | compose/DEPLOY told to run `key:generate` *after* `up` — but the entrypoint runs `config:cache` on boot, so the cached config had no key. Docs now generate the key before first boot and say to `restart app` after `.env` edits. |
| 7.9 | DEPLOY.md claims | **FAIL→FIXED (docs)** | "AdminSeeder creates the dashboard login" (was false, see 3.7) and "no secret key ⇒ test-mode" (now refused in production) corrected; `BACKEND_FIXES_LOG.md` note updated |

### 8) Code quality

| # | Check | Result | Evidence |
|---|-------|--------|----------|
| 8.1 | `php -l` on every file in `app/ config/ database/ routes/` | PASS | 0 errors |
| 8.2 | `dd()/dump()/var_dump()/print_r()/ray()` | PASS | none |
| 8.3 | Hard-coded keys / URLs | PASS | none (`sk_/pk_/AKIA/Bearer` grep clean; one `localhost:3000` is a last-resort fallback behind two config keys) |
| 8.4 | N+1 on touched list/detail endpoints (`nplus1.php`, DB query log) | **FAIL→FIXED / NOTE** | `GET /api/v2/contracts` 57 → **10** queries (2 `contract_status_histories` queries per row: `statusHistories` now eager-loaded and used by `ContractStatusHistoryService::timeline()`); `/api/v2/contracts/{id}` 10, `/api/admin/orders/{id}` 30, `/api/admin/leads` 5 — fine. Pre-existing and left alone (NOTE-7): `/api/admin/orders/completed` 73, `/api/admin/users` **335** (payments lookup per contract), `/api/admin/analytics` 149 (per-month counts). |
| 8.5 | PHPUnit suite | **FAIL→FIXED / NOTE** | before: 9 failures. Fixed 4 in scope (alias `instanceof`, `leads` table missing, refund approve, stale `?lang=ar` expectation) and pinned `PAYMENTS_DRIVER=moyasar` in `phpunit.xml` so the payment tests no longer depend on the developer's `.env`. Now **236 tests, 2377 assertions, 5 failures** — all pre-existing and outside this scope (`UsersAliasTest` expects a `Users/Routes/web.php`; 4 marketing-attribution tests). |

---

## B. New bugs found & fixed (severity)

| Sev | Bug | Fix (file) |
|-----|-----|-----------|
| **Critical** | No dashboard login exists after the documented production seed (`AdminSeeder` writes `admins`, login reads `employees`) | `database/seeders/AdminSeeder.php`, `DEPLOY.md` |
| **Critical** | `GET /api/admin/leads` readable with a website User token | `app/Modules/Leads/Routes/admin.php` |
| **Critical** | Every `POST /api/v2/contract/step2` returned 500 (undefined method) | `app/Modules/Contracts/Actions/Api/V2/SubmitContractStep2Action.php` |
| **High** | Lead-capture IDOR: any user reads another user's owner name / phone via `contract_uuid` | `app/Modules/Leads/Controllers/LeadController.php` |
| **High** | Implicit payment test-mode (missing key) could mark contracts paid for free via the public payment endpoint in production | `app/Services/MoyasarPaymentService.php::resolveTestMode`, docs |
| **High** | Standard-duration contracts priced from `contract_periods.price` (1400/2500 in seed) instead of DocFee (249/349) — website/server mismatch | `app/Support/DocFee.php`, `app/Support/ContractPricing.php` |
| **High** | `instanceof App\Models\Employee` false for alias-only classes → admins got 403 on refund create (and 20 other sites at risk) | `app/Providers/AppServiceProvider.php` |
| **High** | Refund approval never set `accept_retrun_contract` / employee id on the contract (mass-assignment silently dropped) | `app/Services/Admin/RefundableContractService.php` |
| **High** | Dashboard analytics endpoints 500 on sqlite / PostgreSQL (`HAVING` without `GROUP BY`) | `app/Services/Admin/DashboardAnalyticsService.php`, `app/Services/Admin/Analytics/{User,Employee}AnalyticsService.php` |
| **High** | nginx could not serve uploaded files in the Docker deploy; local `.env`/sqlite baked into the image; `APP_KEY` generated after `config:cache` | `docker-compose.prod.yml`, `docker/nginx.conf`, `.dockerignore`, `DEPLOY.md` |
| **Medium** | Meter fees never charged for v2 contracts (ownership on units, read from contract) | `SubmitContractStep5Action.php`, `app/Support/MeterFees.php` |
| **Medium** | Admin errors returned HTTP 200 / success:true (`apiResponse($d,$m,false,404)` call shape) | `app/Shared/Responses/Responser.php` |
| **Medium** | Admin edit-order: enum → 500/SQL leak, negative amounts, `step:"abc"`, `image_instrument:"../../.env"`, `is_completed` editable | `app/Http/Requests/Admin/UpdateContractRequest.php`, `OrderController::update` |
| **Medium** | Admin edit-order rejected mobiles stored by v2 (`5XXXXXXXX` vs `^05`) | `UpdateContractRequest::normalizeMobiles()` |
| **Medium** | Step 4 500 on invalid `authorization_type` | `Step4Request.php` (v1 + v2), `ContractV2ValidationMessages.php` |
| **Medium** | `/api/v2/getContracts/{uuid}`, `/api/v2/search/{term}` 500 (missing methods) | `app/Modules/Contracts/Controllers/Api/V2/ContractController.php` |
| **Medium** | v2 id / CR numbers not format-validated | `Step3Request.php`, `Step4Request.php`, messages |
| **Medium** | Deed images returned as raw private path / broken URL in `ContractResource` + `Step2Resource` | both resources |
| **Medium** | Refund amount could exceed the paid amount | `RefundableContractService::createRefundRequest`, `lang/{ar,en}/api.php` |
| **Medium** | Lead conversion exception aborted payment completion when `leads` table absent | `app/Models/Lead.php` |
| **Medium** | php-fpm upload limits below what the API validates (413) | `Dockerfile` |
| **Low** | `Guarantee_amount` / `deposit` not returned on wizard resume | `Step6Resource.php` |
| **Low** | Coupon endpoint before/after totals excluded VAT / meter fees | `CouponController.php` |
| **Low** | Test-mode payment response missing the documented money breakdown | `MoyasarPaymentService::simulateSuccessfulPayment` |
| **Low** | `copy_of_the_owner_record` upload without size/file rule; negative rent accepted | `Step4Request.php`, `Step6Request.php` |
| **Low** | v2 422 responses had no per-field `errors` | `BaseApiV2Request.php` |
| **Low** | `.env.example` missing 138 referenced keys; `CACHE_STORE`/`BROADCAST_CONNECTION` never read by Laravel 10 | `.env.example` |
| **Low** | `/api/v2/contracts` list ran 2 extra queries per row | `ListUserContractsAction.php`, `ContractStatusHistoryService.php` |
| **Low** | Payment tests depended on the developer's `.env` test-mode; stale `?lang=ar` expectation | `phpunit.xml`, `tests/Feature/PaymentFailedDoesNotCompleteContractTest.php` |

## C. Still open / needs owner decision

- **NOTE-1** `contract_periods.price` (dashboard "مدة الطلب") is no longer used for charging anywhere; the DocFee constants (249/150, 349/500) are the single source. Either hide the price column in the dashboard or make the DocFee amounts configurable from settings.
- **NOTE-2** `GET /api/v2/payment/{uuid}`, `/payment/result/{uuid}`, `/status/result/{uuid}` are unauthenticated (the website calls them without a token) and the contract `uuid` is a 6-digit number (`random_int(100000, 999999)`, ~900 k space) behind a 20/min IP throttle: they disclose contract id, amounts and payment method to anyone who enumerates, and in *explicit* test-mode (`PAYMENTS_DRIVER=test`, staging) they mark arbitrary contracts paid. Recommend a longer opaque uuid for new contracts or a signed payment link.
- **NOTE-3** VAT is computed on the full fee even when a coupon reduces it (VAT base before discount). Irrelevant while `vat_rate = 0`; confirm the intended base before enabling VAT.
- **NOTE-4** Endowment / trusteeship / inheritance / heirs-PoA / guardians-PoA / authorization / owner-record documents are still stored on the **public** disk (only the 3 deed images were made private). Same signed-URL treatment recommended.
- **NOTE-5** `Contract::payments()` relation (`belongsToMany(Payment)`) references a non-existent `contract_payment` pivot; unused, but any future call will crash. Payments are linked by `contract_uuid`.
- **NOTE-6** Seeded demo contracts have no duration fields; their `total_price` now shows the DocFee for their period (249/349) while the seeded `payments.amount` is random (199/249). Expected for fake data; real production rows carry `contract_term_in_years` and are now priced consistently.
- **NOTE-7** Pre-existing N+1 in admin lists: `/api/admin/users` (335 queries / 20 rows), `/api/admin/orders/completed` (73), `/api/admin/analytics` (149 per-month counts). Not touched (broad refactor); consider eager-loading the latest successful payment per contract.
- **NOTE-8** 5 pre-existing PHPUnit failures outside this scope: `UsersAliasTest` (expects `app/Modules/Users/Routes/web.php`), `MarketingAttributionTest` ×2, `MarketingContentTest`, `MarketingTrackingMissingSchemaTest`.
- **NOTE-9** Admin edit-order can still set `step`, `contract_status_id`, `employee_id`, `file` etc. by design (generic column update, admin-only, now validated). `is_completed` was excluded because it is payment-derived — confirm the dashboard does not rely on toggling it manually.
- **NOTE-10** The `#30/#31` "representative capacity document mandatory" rule remains unenforced (as the previous pass documented); data is stored and returned for all instrument types.
- **QA data left in the local sqlite:** contracts 262-285 (users 1/2, several paid in test-mode), leads 20-21, refund requests 16-18, employee #6 `admin@aqdi.com` (legit seed row). Another session was writing to the same DB concurrently (employee #7 `qa-admin@aqdi.com`, tokens named `e2e`/`admin-employee`).
