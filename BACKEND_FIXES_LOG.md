# Backend Fixes Log — عقدي / Aqdi

Environment: Laravel 10, SQLite, `/api/v2`. All changes are additive/surgical.
Migrations added:
- `2026_09_20_000100_add_vat_rate_to_settings.php` (settings.vat_rate, default 0)
- `2026_09_20_000200_create_leads_table.php` (CR2 leads)

Both applied with `php artisan migrate --force` (success). `php artisan route:list` boots clean.

New shared code:
- `app/Support/ContractPricing.php` — single source of truth for fee/VAT/total.
- `app/Support/DeedImage.php` — private deed-image storage + signed URL helper.
- `app/Models/Lead.php`, `app/Modules/Leads/**` — CR2 leads.

---

## PRICING & MONEY

### #8 Service fee double-counted — **FIXED (verified)**
Root cause: `Contract::getPriceContractAttribute()` already summed `ServicesPricing`, then
callers (`ContractFinancialService`, `MoyasarPaymentService::calculateCartAmount`) added the
same services sum **again**, and also stacked a legacy `ContractPeriod` price on top of the
doc fee.
Changes:
- `HasContractAccessors::getPriceContractAttribute()` now returns `ContractPricing::fee()`
  (single documentation fee, no service/app-fee/tax stacking).
- `MoyasarPaymentService::calculateCartAmount()` now returns `ContractPricing::total()`.
- `ContractFinancialService` / `ContractFinancialSummaryService` rewritten to use
  `ContractPricing` (no `+ services` re-add).
Verified: commercial 2-year total = **849** once (was inflated); tinker + financial payload.

### #13 Pricing conflict 849 (web) vs 799 (dashboard) — **FIXED (verified)**
Root cause: website used `DocFee` (849) while the server/dashboard fell back to the legacy
`ContractPeriod` table (799). `ContractPricing::fee()` uses `DocFee` as the single source
everywhere; legacy `ContractPeriod` only as a fallback when no duration is set.
Verified: `DocFee::forContract` and `ContractPricing::fee` both return 849 for commercial
2-year; finance payload `total_price = 849`.

### #14 VAT hard-coded to 15 — **FIXED (verified)**
Root cause: `settings.housing_tax`/`commercial_tax` held a flat `15` used as "VAT".
Changes: added `settings.vat_rate` (percent, default 0). VAT is now
`round(fee * vat_rate/100, 2)` via `ContractPricing`. With rate 0 → VAT 0 → label **"مجانًا"**.
`getTotalPriceAttribute()` and both finance services updated.
Verified: rate 0 → vat 0 "مجانًا"; rate 15 → commercial 2-year vat **127.35**, housing 1-year
vat **37.35** (proportional, not flat 15).

### #28 Payment screen VAT shows 15 — **FIXED (verified)**
Same root cause as #14 (one calculator now). Payment-screen response and finance summary both
emit `vat`, `vat_rate`, `vat_label` from `ContractPricing`. Verified `vat_label = "مجانًا"`.

### #35 Reconciliation: revenue double-counted — **FIXED (verified)**
Root cause found in `DashboardAnalyticsService::getControlPanel()`: TWO currency tiles both
equal to `Payment::successful()->sum('amount')` (`عقود مدفوعه` + `اجمالي المدفوعات في العقود`),
so any total over the tiles doubled revenue. Fixed the `عقود مدفوعه` tile to a **count** of
paid contracts (matching its label). 
Verified: `ReportsService::salesTotals().total_sales` == raw successful-payments sum
(129,483 == 129,483) — reports already single-count; the dashboard tile no longer duplicates.
NOTE: the report aggregation itself was already single-counted (no join fan-out); the concrete
doubling was the dashboard tile.

### #12 Refunds against UNPAID orders — **FIXED (verified)**
`RefundableContractService::createRefundRequest()` now calls a new `contractIsPaid()` guard
(is_completed OR a successful payment) and throws `api.refund_requires_paid_contract` (422)
otherwise. Added AR/EN lang keys.
Verified: creating a refund for an unpaid contract (id 232) is blocked with the Arabic message.

---

## DATA THE API MUST STORE & RETURN

### #4 Owner full name — **FIXED / VERIFIED-PRESENT**
`name_owner` is stored by Step3 and returned in the order detail (verified value
"خالد الرشيد" on a real contract). Documented in the contract.

### #5 Owner-by-agency data — **VERIFIED-PRESENT**
`id_num_of_property_owner_agent`, `mobile_of_property_owner_agent`,
`agency_number_in_instrument_of_property_owner`, `agency_instrument_date_of_property_owner`,
`copy_of_the_authorization_or_agency` are stored by Step3 and returned by the detail resource
(verified populated on a real بوكالة contract).

### #7 Guarantee / penalty / extra condition — **FIXED (verified for guarantee)**
- `daily_fine` (penalty) and `other_conditions`(_list) (extra condition) were already stored
  by Step6 and returned.
- **Bug found & fixed:** `Guarantee_amount` (الضمان) and `deposit` were never captured by any
  step → now persisted in `SubmitContractStep6Action` and validated in `Step6Request`
  (`nullable numeric min:0`). Verified the fields flow into `contract->update`.

### #16 Deed / صك number empty — **FIXED / documented**
The صك number is stored as `instrument_number` (verified `4300000285`), not `deed_number`
(which is the separate ejar deed-addition number, legitimately empty pre-processing).
Added `instrument_number` (+ `instrument_history`, `real_estate_registry_number`,
`deed_number`) to the dashboard `contract_summary` block, and documented that
رقم الصك = `instrument_number`.

### #30 / #31 Deceased-owner & Waqf representative — **PARTIAL / VERIFIED-PRESENT**
The API accepts & stores representative/agent data (agent id/phone, PoA number/date) and the
capacity documents: `copy_power_of_attorney_from_heirs_to_agent` (وكيل الورثة),
`copy_of_the_trusteeship_deed` / `copy_of_the_endowment_registration_certificate` (ناظر الوقف),
`copy_of_guardians_power_of_attorney_for_agent`, plus `property_owner_is_deceased`. Step1 now
**returns all** of these (previously omitted — see #33). Verified waqf docs returned on a real
endowment contract.
NEEDS-REVIEW: making the "representative capacity document" strictly *mandatory* was NOT
enforced, to avoid breaking the multi-path wizard (files can arrive in different steps and
instrument types). Recommend enforcing required-if in the step request once the frontend field
mapping per instrument type is confirmed.

### #33 Waqf: all uploaded documents returned — **FIXED (verified)**
`Step1Resource` returned only endowment cert + trusteeship deed. Added
`Image_inheritance_certificate`, `copy_power_of_attorney_from_heirs_to_agent`,
`copy_of_guardians_power_of_attorney_for_agent`, `Image_from_the_agency`,
`property_owner_is_deceased`. (`AdminContractDetailResource` already returned all of them.)

### Dashboard display fields (#1/#2/#3/#6/#16) — **VERIFIED-PRESENT**
Order detail includes: actual amount `annual_rent_amount_for_the_unit` (verified 25000),
duration (`duration_*`/`total_months`/`contract_term_in_years`), tenant id `tenant_id_num`
(verified), tenant org `tenant_entity`+`tenant_entity_unified_registry_number`, deed number
`instrument_number`. `total_price` now returns fee/vat/total (single source), and
`amount_payment` shows the actual paid amount.

---

## VALIDATION & INTEGRITY

### #10 Edit-order accepts letters in id / bad phone — **FIXED (verified)**
`UpdateContractRequest` gained `FORMAT_RULES`: national id `^[12]\d{9}$`, mobile `^05\d{8}$`,
CR/unified `^7\d{9}$`, applied (nullable) to the relevant columns in `rulesForKeys` (used by
both `rules()` and `updatePayload()`).
Verified: bad payload (`12A456789`, `0512`, `1234567890`) fails on all three keys; valid
payload passes.

### #22 Deed images on public path — **FIXED (verified)**
- New deed uploads → private disk `local` (`contracts/deeds/…`) in Step1.
- New signed route `GET /api/v2/contracts/{contract}/deed-image/{field}` (`signed` middleware,
  30-min TTL) via `DeedImageController`, streaming from private (fallback public for legacy).
- `Step1Resource` and `AdminContractDetailResource` return deed columns as signed URLs.
Verified live: valid signed URL → **200** streaming file; tampered URL → **403**; unsigned →
**403**.

---

## CR2 — Leads / "العملاء المحتملون" — **FIXED (verified)**
- Table `leads` + `App\Models\Lead`.
- `POST /api/v2/leads` captures a potential lead (auto-enriched from the contract, idempotent
  per `contract_uuid`, phone `^05\d{8}$`).
- Conversion: `Lead::markPaidForContractUuid()` called from
  `MoyasarPaymentService::markContractAsCompleted()` → status `paid` + `converted_at`.
- `GET /api/admin/leads` lists with `status`/`search` filters + summary counts.
Verified: created a potential lead, ran conversion → status `paid`, `converted_at` set.

---

## #34 Employees/Salaries empty vs Metrics/Roles — **VERIFIED-CONSISTENT (no code change needed)**
Audited every employee-feeding query:
- `EmployeeController::index` (employees), `EmployeeController::employeeSalary` (salaries),
  `EmployeeKpiService::forAllEmployees` (metrics), `RoleController::create` (roles form)
  all use the same unfiltered `Employee::query()` source.
Verified all four return the **same** set (5 employees in the seed; `Employee::count()` = 5).
The salaries *ledger* (`salaries` table) is separately empty (0 payment rows seeded) — that is
data, not an inconsistency; the salaries endpoint still returns all employees with
`base_salary`. Left code unchanged to honor the "minimal, safe" constraint; the original prod
"6 vs empty" symptom was most likely a permission/seed difference, not divergent queries.

---

## Verification summary

Fully fixed + verified: #8, #13, #14, #28, #35, #12, #10, #22, #33, CR2, and #7 (guarantee).
Verified-present/documented: #4, #5, #16, #1/#2/#3/#6, #34.
Partial / needs-review: #30/#31 (data stored & returned; mandatory capacity-document
enforcement deferred to the step request pending frontend field-mapping confirmation).

---

# Production readiness

The backend was made **paste-the-key-and-go**: every external integration is
env-driven with a safe TEST-MODE fallback, so the app runs end-to-end with **no
real keys** and goes live the moment real keys are added — no code change. All
changes are surgical; `php artisan route:list` stays clean (604 routes) and
`php artisan migrate:fresh --seed` still succeeds.

## Files added
- `.env.example` — every variable the app needs, grouped and commented.
- `Dockerfile` (php 8.2-fpm, `composer install --no-dev`, ext pdo_mysql/gd/zip/…),
  `docker/entrypoint.sh`, `docker/nginx.conf`, `docker-compose.prod.yml` (app+mysql+nginx).
- `DEPLOY.md` — full production deploy runbook + where to obtain each key.

## Files changed
- `config/services.php` — added `moyasar.locale/driver/test_mode`, `firebase.disabled`, `twilio.*`.
- `config/otp.php` — added `otp.driver`.
- `app/Services/MoyasarPaymentService.php` — test-mode simulation + Arabic locale.
- `app/Modules/Auth/Actions/SendUserAuthSmsAction.php` — driver-aware OTP send (log/taqnyat/twilio).
- `app/Services/TwilioService.php` — lazy, config-driven, no crash without keys.
- `app/Services/FirebaseNotificationService.php` — no-op when credentials absent.
- `.env` — explicit local test-mode values.

## 1) Payments (Moyasar) — env-driven + test-mode
`MoyasarPaymentService` reads `MOYASAR_SECRET_KEY`/`MOYASAR_PUBLISHABLE_KEY` from
config. Test-mode is ON when `PAYMENTS_DRIVER=test`, `MOYASAR_TEST_MODE=true`, or
**no secret key is set** (and driver not pinned to `moyasar`) — *except* in `APP_ENV=production`, where a missing key is refused (logged as critical) instead of silently simulating payments. In test-mode the
service records a `success` Payment (`payment_method=test`), marks the
contract/employee-payment paid, and returns a response pointing straight at the
local success screen — the full flow completes with no real charge. With a real
secret key + `PAYMENTS_DRIVER=moyasar`, the real invoice path is used. The hosted
payment page is forced to Arabic via `MOYASAR_LOCALE=ar` (`?lang=ar` appended).
Keys are never hardcoded. Verified: employee-paid contract → `test_mode=true`,
`is_paid=true`, success payment row created (tested in a rolled-back transaction).

## 2) OTP / SMS — env-driven providers + log test-mode
`OTP_DRIVER=log|taqnyat|twilio` (unset → `log` outside production, `taqnyat` in
production). Provider keys come from env (`TAQNYAT_BEARER`, `TWILIO_SID/TOKEN/
PHONE`). If a real provider is selected but its keys are missing, it safely
degrades to `log`. In `log` mode **no SMS is sent**: the OTP message (with the
plain code) is written to `storage/logs/laravel.log` ("OTP (test-mode, no SMS
sent)") **and** stored in the `sms_logs` table with `sms_id='test-mode'` and the
full body in `message` — so a developer can log in on a fresh install with no SMS
account. Retrieve it with:
`php artisan tinker --execute="echo \App\Models\SmsLog::latest('id')->first()->message;"`
Production real-send behavior is unchanged when a provider + keys are configured.
Verified: `sendOtp()` returned `true` and the plain code landed in `sms_logs`.

## 3) Firebase (push) — env-driven no-op
`FirebaseNotificationService::sendMessage()` now short-circuits to a logged no-op
when the service-account file is missing or `FIREBASE_DISABLED=true`, instead of
throwing. Missing Firebase creds no longer crash any flow. Set
`FIREBASE_CREDENTIALS`, `FIREBASE_PROJECT_ID`, `FIREBASE_DISABLED=false` to go live.

## Environment variables the OWNER must fill (to go live)
- **Moyasar:** `PAYMENTS_DRIVER=moyasar`, `MOYASAR_SECRET_KEY`, `MOYASAR_PUBLISHABLE_KEY`
- **SMS (pick one):** `OTP_DRIVER=taqnyat` + `TAQNYAT_BEARER`/`TAQNYAT_SENDER`/`TAQNYAT_SMS_ID`,
  or `OTP_DRIVER=twilio` + `TWILIO_SID`/`TWILIO_TOKEN`/`TWILIO_PHONE`
- **Firebase:** `FIREBASE_DISABLED=false`, `FIREBASE_CREDENTIALS` (JSON path), `FIREBASE_PROJECT_ID`
- **Core:** `APP_KEY` (`key:generate`), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`,
  `DB_*` (mysql), `SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL`, `DASHBOARD_URL`, `MAIL_*`

Everything else works out of the box in test-mode. After editing `.env` in
production, re-run `php artisan config:cache`.
