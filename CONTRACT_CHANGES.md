# API Contract Changes — عقدي / Aqdi backend

This is the contract the **website** and **dashboard** code against. All keys below are the
exact JSON key names returned/accepted by the API. Base path: `/api/v2` (public/app) and
`/api/admin` (dashboard). VAT is currently **OFF** (`settings.vat_rate = 0`) so every VAT
amount is `0` and is displayed as **"مجانًا"**.

---

## 0. Money model (single source of truth)

All money now comes from ONE calculator: `App\Support\ContractPricing`.

```
fee    = documentation fee (App\Support\DocFee rules; legacy ContractPeriod as fallback)
vat    = round(fee * settings.vat_rate / 100, 2)          # rate 0 now → vat 0
total  = max(0, fee + vat + meter_fees_total - coupon)
```

- No more `application_fees` / flat `housing_tax` / `commercial_tax` (15) stacking.
- The **same** `total` is returned by the finance endpoint, the payment screen, the invoice
  and the dashboard order detail.
- Multi-year commercial example: 2 years → fee `849` (349 first year + 500 each extra), vat `0`,
  total `849`. (Previously the dashboard computed `799` from the legacy table — gone.)

New setting:

| table | column | type | default |
|-------|--------|------|---------|
| `settings` | `vat_rate` | decimal(5,2) percent | `0` |

---

## 1. Finance / price summary

`GET /api/v2/financial/{uuid}`
`GET /api/v2/finance-summary/{uuid}`
(v1 equivalent: `GET /api/financial/{uuid}`)

**Response (new / changed keys in bold):**

```jsonc
{
  "price_details": {
    "contract_period_price": 849,     // = documentation fee (single source)
    "application_fees": 0,            // deprecated, always 0
    "tax": 0,                        // kept for back-compat, now == vat (proportional)
    "vat": 0,                        // NEW proportional VAT amount
    "vat_rate": 0,                   // NEW percent from settings
    "vat_label": "مجانًا",           // NEW ("مجانًا" when 0, else "<amount> ر.س")
    "electricity_meter_fee": 0,
    "water_meter_fee": 0,
    "doc_fee": 849,                  // v2 only, when duration present
    "billable_years": 2,
    "total_months": 24,
    "duration_preset": "2_years",
    "duration_years": null,
    "duration_months": null
  },
  "services": [ ... ],               // informational only, NOT added to total
  "fee": 849,                        // NEW
  "vat": 0,                          // NEW
  "vat_rate": 0,                     // NEW
  "vat_label": "مجانًا",             // NEW
  "meter_fees_total": 0,
  "total_price": 849,                // = fee + vat + meter - coupon
  "coupon": 50,                      // only when a coupon is applied
  "total_price_after_coupon": 799    // only when a coupon is applied
}
```

---

## 2. Payment screen

`POST /api/v2/payment/...` (Moyasar init — test/simulation path unchanged)

**Response gains** the single-source money breakdown (existing `doc_fee`, `meter_*` kept):

```jsonc
{
  "cart_amount": 849,     // == total below
  "doc_fee": 849,
  "meter_fees_total": 0,
  "fee": 849,             // NEW
  "vat": 0,               // NEW
  "vat_rate": 0,          // NEW
  "vat_label": "مجانًا",  // NEW
  "coupon": 0,            // NEW
  "total": 849            // NEW — the charged amount
}
```

The amount actually charged to Moyasar (`calculateCartAmount`) is now
`ContractPricing::total()` — no double-counting.

---

## 3. Create-contract flow (what the website must send / gets stored)

### Step 1 — `POST /api/v2/contract/step1`
Deed/instrument images now stored on a **private** disk (see §6). Otherwise unchanged.
Accepts (already): `instrument_number` (= رقم الصك), `image_instrument`,
`image_instrument_from_the_front`, `image_instrument_from_the_back`,
`copy_of_the_endowment_registration_certificate`, `copy_of_the_trusteeship_deed`,
`Image_inheritance_certificate`, `copy_power_of_attorney_from_heirs_to_agent`,
`copy_of_guardians_power_of_attorney_for_agent`, `Image_from_the_agency`,
`property_owner_is_deceased`.

**Step1 response now returns ALL uploaded documents** (previously only some):
`image_instrument`, `image_instrument_from_the_front`, `image_instrument_from_the_back`
(as signed URLs), `copy_of_the_endowment_registration_certificate`,
`copy_of_the_trusteeship_deed`, **`Image_inheritance_certificate`**,
**`copy_power_of_attorney_from_heirs_to_agent`**,
**`copy_of_guardians_power_of_attorney_for_agent`**, **`Image_from_the_agency`**,
**`property_owner_is_deceased`**.

### Step 3 — `POST /api/v2/contract/step3` (owner / owner-by-agency)
Stored & returned: `name_owner` (owner full name), `property_owner_id_num`,
`property_owner_mobile`, `property_owner_iban`, `add_legal_agent_of_owner`,
and when بوكالة (`add_legal_agent_of_owner = 1`):
`id_num_of_property_owner_agent` (agent id), `mobile_of_property_owner_agent` (agent phone),
`agency_number_in_instrument_of_property_owner` (power-of-attorney number),
`agency_instrument_date_of_property_owner` (PoA date),
`copy_of_the_authorization_or_agency` (PoA/agency document).

### Step 6 — `POST /api/v2/contract/step6` (terms)
**Newly accepted & stored** (previously dropped):
`Guarantee_amount` (الضمان), `deposit`, `daily_fine` (الغرامة).
Already handled: `other_conditions` / `other_conditions_list` (الشرط الإضافي),
`additional_terms` / `text_additional_terms`, `annual_rent_amount_for_the_unit`,
`duration_preset` / `duration_years` / `duration_months` / `total_months` /
`contract_term_in_years`.

Validation: `Guarantee_amount|deposit|daily_fine` → `nullable numeric min:0`.

---

## 4. Order / contract detail (dashboard)

`GET /api/admin/orders/{id}` etc. → `AdminOrderDetailService::fullPayload`

Top-level response includes every contract column (via `toArray`) plus enriched relations.
Guaranteed-present keys the dashboard needs:

| Purpose | Key |
|---------|-----|
| Actual contract (rent) amount | `annual_rent_amount_for_the_unit` |
| Documentation fee / VAT / total | `total_price` = `{ fee, vat, vat_label, total_price, details{...} }` |
| Amount actually paid | `amount_payment` (number for paid, else "لم يتم الدفع") |
| Contract duration | `duration_years`, `duration_months`, `total_months`, `duration_preset`, `contract_term_in_years` (+ relation `.period`) |
| Individual tenant id | `tenant_id_num` |
| Tenant organization (مؤسسة) | `tenant_entity` (`person`/`company`), `tenant_entity_unified_registry_number`, `tenant_entity_region`, `tenant_entity_city` |
| Deed / صك number | `instrument_number` (رقم الصك, from step 1) — **now also in `contract_summary`** |
| Ejar deed number | `deed_number` (separate: set during ejar processing) |
| Owner full name | `name_owner` |
| Owner-by-agency | `id_num_of_property_owner_agent`, `mobile_of_property_owner_agent`, `agency_number_in_instrument_of_property_owner`, `agency_instrument_date_of_property_owner`, `copy_of_the_authorization_or_agency` |
| Guarantee / penalty / condition | `Guarantee_amount`, `daily_fine`, `other_conditions` / `other_conditions_list` |
| Deceased owner | `property_owner_is_deceased`, `Image_inheritance_certificate`, `copy_power_of_attorney_from_heirs_to_agent` |
| Waqf | `copy_of_the_endowment_registration_certificate`, `copy_of_the_trusteeship_deed`, `copy_of_guardians_power_of_attorney_for_agent` |
| Deed images (private) | `image_instrument`, `image_instrument_from_the_front`, `image_instrument_from_the_back` → **temporary signed URLs** |

`contract_summary` block now also carries: `instrument_number`, `instrument_history`,
`real_estate_registry_number`, `deed_number`.

---

## 5. Admin edit-order validation

`POST /api/admin/orders/{id}` / `PATCH /api/admin/contracts/{contractId}`
(`UpdateContractRequest`)

When present, these fields are now format-validated server-side:

| Field(s) | Rule |
|----------|------|
| `property_owner_id_num`, `tenant_id_num`, `id_num_of_property_owner_agent`, `id_num_of_property_tenant_agent` | `^[12]\d{9}$` |
| `property_owner_mobile`, `tenant_mobile`, `mobile_of_property_owner_agent`, `mobile_of_property_tenant_agent` | `^05\d{8}$` |
| `tenant_entity_unified_registry_number`, `real_estate_registry_number` | `^7\d{9}$` |

Invalid values → `422` with the field key in `errors`.

---

## 6. Deed / instrument images now private

- New deed uploads go to disk `local` under `contracts/deeds/…` (NOT the public disk).
- They are served ONLY through a temporary signed URL:
  `GET /api/v2/contracts/{contract}/deed-image/{field}` (middleware `signed`, TTL 30 min),
  `field ∈ {image_instrument, image_instrument_from_the_front, image_instrument_from_the_back}`.
- Resources (`Step1Resource`, `AdminContractDetailResource`) return those columns as the
  signed URL. Legacy files on the public disk are still streamed transparently.

---

## 7. Leads — "العملاء المحتملون" (CR2)

New table `leads`: `id, name, phone, contract_uuid, contract_id, amount, contract_type,
status ('potential'|'paid'), source, converted_at, created_at, updated_at`.

### Capture (payment screen reached, not paid)
`POST /api/v2/leads` (auth)

Request:
```jsonc
{ "name": "…", "phone": "05XXXXXXXX", "contract_uuid": "…", "contract_id": 123,
  "amount": 849, "contract_type": "commercial", "source": "web" }
```
- Missing `name`/`phone`/`amount`/`contract_type` are auto-filled from the contract.
- Idempotent per `contract_uuid` (updates the existing lead).
- `phone` (when sent) must match `^05\d{8}$`.

Response `201`: the lead row.

### Auto-conversion
On payment success (`markContractAsCompleted`) the matching lead flips to
`status = "paid"` with `converted_at` set.

### List (dashboard)
`GET /api/admin/leads?status=potential&search=…&per_page=20` (auth)

```jsonc
{ "summary": { "total": N, "potential": N, "paid": N },
  "items": [ { "id":1, "name":"…", "phone":"…", "contract_uuid":"…",
               "amount":849, "status":"potential", "created_at":"…" } ],
  "pagination": { ... } }
```

---

## 8. Dashboard analytics fix (reconciliation)

`GET /api/admin/analysis` control panel: the tile `عقود مدفوعه` is now a **count** of paid
contracts (was a duplicate of the payments-sum currency tile, which double-counted revenue).
The single revenue tile is `اجمالي المدفوعات في العقود`. Reports `total_sales`
(`GET /api/admin/reports/sales`) already equals the raw successful-payments sum.
