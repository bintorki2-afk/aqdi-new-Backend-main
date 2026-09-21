<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\ContractStatus;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\ReaEstatType;
use App\Models\ReaEstatUsage;
use App\Models\TenantRole;
use App\Models\UnitType;
use App\Models\UsageUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds contracts with realistic Saudi-style data (owners, tenants, addresses, financials, unit details).
 * Requires: regions/cities, contract periods, payment types, property types/usages, unit types/usages, users.
 */
class ContractRealDataSeeder extends Seeder
{
    /** Three fully-filled completed contracts for manual / API testing. */
    private const COUNT = 3;

    public function run(): void
    {
        if (User::query()->doesntExist()) {
            $this->command?->warn('ContractRealDataSeeder skipped: no users. Run UserSeeder first.');

            return;
        }

        if (City::query()->doesntExist()) {
            $this->command?->warn('ContractRealDataSeeder skipped: no cities. Run CitySeeder first.');

            return;
        }

        $cities = City::query()->get();
        $userIds = User::query()->pluck('id')->all();
        $tenantRoleId = Schema::hasTable('tenant_roles') && TenantRole::query()->exists()
            ? TenantRole::query()->value('id')
            : null;

        $streetSets = [
            'housing' => [
                'طريق الملك فهد', 'شارع العليا', 'طريق الأمير محمد بن سلمان', 'شارع التحلية',
                'طريق الملك عبدالعزيز', 'شارع الأمير سلطان', 'حي الياسمين', 'حي النرجس',
            ],
            'commercial' => [
                'طريق الملك خالد', 'شارع التخصصي', 'طريق الأمير تركي', 'طريق الملك عبدالله',
                'شارع الأمير ماجد', 'طريق الدمام السريع', 'طريق الملك فهد التجاري',
            ],
        ];

        $neighborhoods = [
            'العليا', 'الملز', 'النسيم', 'الروضة', 'السليمانية', 'الورود', 'الرحمانية',
            'الشاطئ', 'الروابي', 'الفيصلية', 'الخبر الشمالية', 'الكورنيش', 'البلد', 'النسيم الغربي',
        ];

        $ownerNames = [
            ['فهد', 'القحطاني'], ['نورة', 'العنزي'], ['خالد', 'الرشيد'], ['لينا', 'العبيد'],
            ['عبدالله', 'المنصور'], ['هناء', 'البقمي'], ['فيصل', 'الدوسري'], ['ريم', 'الحارثي'],
        ];

        $tenantNames = [
            ['محمد', 'السبيعي'], ['أمل', 'الجهني'], ['طارق', 'القرني'], ['دانة', 'العسيري'],
            ['بندر', 'الخالدي'], ['شهد', 'اليامي'], ['نايف', 'الماجد'], ['جواهر', 'السبيعي'],
        ];

        /** One housing (full parties + tenant agent), one commercial (tenant as party + authorization), one housing endowment. */
        $scenarios = [
            [
                'contract_type' => 'housing',
                'instrument_type' => 'electronic',
                'ownership' => 'owner',
                'with_owner_agent' => true,
                'with_tenant_agent' => true,
                'deceased_owner' => false,
                'authorization_type' => 'owner_and_representative_of_record',
                'assign_tenant_role' => true,
            ],
            [
                'contract_type' => 'commercial',
                'instrument_type' => 'old_handwritten',
                'ownership' => 'tenant',
                'with_owner_agent' => false,
                'with_tenant_agent' => false,
                'deceased_owner' => false,
                'authorization_type' => 'agent_for_the_tenant',
                'assign_tenant_role' => false,
            ],
            [
                'contract_type' => 'housing',
                'instrument_type' => 'property_ownership_owner_is_endowment',
                'ownership' => 'owner',
                'with_owner_agent' => true,
                'with_tenant_agent' => false,
                'deceased_owner' => false,
                'authorization_type' => null,
                'assign_tenant_role' => true,
            ],
        ];

        for ($i = 0; $i < self::COUNT; $i++) {
            $scenario = $scenarios[$i];
            $city = $cities[$i % max(1, $cities->count())];
            $regionId = (int) $city->region_id;
            $contractType = $scenario['contract_type'];
            $completed = true;
            $step = 7;
            $ownership = $scenario['ownership'];
            $withAgent = $scenario['with_owner_agent'];
            $deceasedOwner = $scenario['deceased_owner'];
            $withTenantAgent = $scenario['with_tenant_agent'];
            $instrumentType = $scenario['instrument_type'];

            $period = ContractPeriod::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->offset($i % 3)
                ->first()
                ?? ContractPeriod::query()->where('contract_type', $contractType)->orderBy('id')->first();
            $paymentType = PaymentType::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->offset($i % 3)
                ->first()
                ?? PaymentType::query()->where('contract_type', $contractType)->orderBy('id')->first();
            $propertyType = ReaEstatType::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->first();
            $propertyUsage = ReaEstatUsage::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->first();
            $unitType = UnitType::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->first();
            $unitUsage = UsageUnit::query()
                ->where('contract_type', $contractType)
                ->orderBy('id')
                ->first();

            if (! $period || ! $paymentType || ! $propertyType || ! $propertyUsage || ! $unitType || ! $unitUsage) {
                $this->command?->warn('ContractRealDataSeeder: missing reference rows for '.$contractType.'. Skipping one contract.');

                continue;
            }

            $owner = $ownerNames[$i % count($ownerNames)];
            $tenant = $tenantNames[$i % count($tenantNames)];
            $ownerFull = $owner[0].' '.$owner[1];
            $tenantFull = $tenant[0].' '.$tenant[1];
            $streets = $streetSets[$contractType];

            $ownerNationalId = '1'.str_pad((string) (100000000 + $i * 791), 9, '0', STR_PAD_LEFT);
            $tenantNationalId = '2'.str_pad((string) (100000000 + $i * 503), 9, '0', STR_PAD_LEFT);

            $annualRent = $contractType === 'commercial'
                ? (string) (95000 + $i * 5000)
                : (string) (22000 + $i * 1500);

            $latBase = match ((int) $city->region_id) {
                1 => [24.7136, 46.6753],
                2 => [21.4858, 39.1925],
                5 => [26.4207, 50.0888],
                6 => [18.2164, 42.5044],
                default => [24.0, 45.0],
            };

            $latitude = round($latBase[0] + (($i + 1) * 0.002), 8);
            $longitude = round($latBase[1] + (($i + 1) * 0.002), 8);

            $hijriStart = sprintf('%02d-%02d-%d', 1 + $i, 4 + $i, 1446);
            $ownerHijriDob = sprintf('%02d-%02d-%d', 10 + $i, 6, 1378 + $i);
            $tenantHijriDob = sprintf('%02d-%02d-%d', 5 + $i, 9, 1408 + $i);

            $createdAt = Carbon::now()->subDays(12 + $i * 8)->subHours(3 + $i);

            $payload = [
                'contract_type' => $contractType,
                'user_id' => $userIds[$i % count($userIds)],
                'app_or_web' => ($i % 2 === 0) ? 'web' : 'app',
                'contract_ownership' => $ownership,
                'instrument_type' => $instrumentType,
                'status' => null,
                'instrument_number' => (string) (4300000000 + $i * 137 + 11),
                'instrument_history' => Carbon::create(2016 + $i, 3 + $i, 10 + $i)->toDateString(),
                'date_first_registration' => (string) (1422 + $i),
                'real_estate_registry_number' => 'رقم صك '.(900100 + $i),
                'number_of_units_in_realestate' => (string) (4 + $i * 2),
                'property_owner_is_deceased' => $deceasedOwner,
                'property_usages_id' => $propertyUsage->id,
                'property_city_id' => $city->id,
                'property_place_id' => $regionId,
                'property_type_id' => $propertyType->id,
                'neighborhood' => $neighborhoods[$i % count($neighborhoods)],
                'building_number' => (string) (1200 + $i * 111),
                'postal_code' => str_pad((string) (12345 + $i), 5, '0', STR_PAD_LEFT),
                'extra_figure' => '701'.$i,
                'number_of_floors' => (string) (3 + $i),
                'street' => $streets[$i % count($streets)],

                'property_owner_id_num' => $ownerNationalId,
                'property_owner_dob' => $ownerHijriDob,
                'property_owner_mobile' => '05'.str_pad((string) (10000000 + $i * 17), 8, '0', STR_PAD_LEFT),
                'property_owner_iban' => 'SA0380000000608010'.str_pad((string) (167500 + $i), 6, '0', STR_PAD_LEFT),

                'add_legal_agent_of_owner' => $withAgent,
                'id_num_of_property_owner_agent' => $withAgent ? '1'.str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT) : null,
                'dob_gregorian_of_property_owner_agent' => $withAgent ? Carbon::create(1980, 3, 15)->toDateString() : null,
                'dob_hijri_of_property_owner_agent' => $withAgent ? '10-06-1400' : null,
                'mobile_of_property_owner_agent' => $withAgent ? '05'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT) : null,
                'agency_number_in_instrument_of_property_owner' => $withAgent ? (string) (8800 + $i) : null,
                'agency_instrument_date_of_property_owner' => $withAgent ? Carbon::create(2022, 6, 1)->toDateString() : null,
                'agent_iban_of_property_owner' => $withAgent ? 'SA0380000000608020'.str_pad((string) (120000 + $i), 6, '0', STR_PAD_LEFT) : null,

                'tenant_id_num' => $tenantNationalId,
                'tenant_dob_gregorian' => Carbon::create(1988 + $i * 2, 4 + $i, 12 + $i)->toDateString(),
                'tenant_dob' => $tenantHijriDob,
                'tenant_mobile' => '05'.str_pad((string) (30000000 + $i * 13), 8, '0', STR_PAD_LEFT),
                'name_owner' => $ownerFull,

                'add_legal_agent_of_tenant' => $withTenantAgent,
                'id_num_of_property_tenant_agent' => $withTenantAgent ? '1'.str_pad((string) (210000000 + $i), 9, '0', STR_PAD_LEFT) : null,
                'dob_gregorian_of_property_tenant_agent' => $withTenantAgent ? Carbon::create(1985, 7, 20)->toDateString() : null,
                'dob_of_property_tenant_agent' => $withTenantAgent ? '15-03-1405' : null,
                'mobile_of_property_tenant_agent' => $withTenantAgent ? '05'.str_pad((string) (21000000 + $i), 8, '0', STR_PAD_LEFT) : null,
                'agency_number_in_instrument_of_property_tenant' => $withTenantAgent ? (string) (7700 + $i) : null,
                'agency_instrument_date_of_property_tenant' => $withTenantAgent ? Carbon::create(2023, 1, 10)->toDateString() : null,
                'tenant_entity' => 'person',
                'tenant_entity_unified_registry_number' => null,
                'tenant_entity_region_id' => null,
                'tenant_entity_city_id' => null,
                'authorization_type' => $scenario['authorization_type'],

                'unit_number' => (string) ($i % 48 + 101),
                'unit_type_id' => $unitType->id,
                'tootal_rooms' => $contractType === 'housing' ? (string) (3 + $i) : '0',
                'floor_number' => (string) (1 + $i),
                'unit_area' => (string) (95 + $i * 12),
                'electricity_meter_number' => 'EL-'.(100000 + $i),
                'water_meter_number' => 'WT-'.(200000 + $i),
                'number_of_unit_air_conditioners' => (string) (2 + $i),

                'contract_starting_date' => $hijriStart,
                'contract_term_in_years' => $period->id,
                'annual_rent_amount_for_the_unit' => $annualRent,
                'payment_type_id' => $paymentType->id,
                'daily_fine' => (string) (100 + $i * 25),
                'sub_delay' => true,
                'other_conditions' => 'يُمنع التأجير من الباطن دون موافقة خطية من المؤجر. الصيانة الدورية للمكيفات على المستأجر.',
                'premium_membership_for_free' => true,
                'deposit' => (string) (3000 + $i * 500),
                'Guarantee_amount' => (string) (2000 + $i * 400),
                'contract_period_id' => $period->id,
                'real_id' => null,
                'real_units_id' => null,
                'unit_usage_id' => $unitUsage->id,

                'client_account_holder_name' => $tenantFull,
                'bank_account_number' => str_pad((string) (3000000000000 + $i), 24, '0', STR_PAD_LEFT),

                'The_number_of_the_toilet' => (string) (2 + $i),
                'The_number_of_halls' => (string) (1 + $i),
                'The_number_of_kitchens' => '1',
                'Gasmeter' => 'G-'.(5000 + $i),
                'Number_parking_spaces' => (string) $i,

                'rating' => 5,
                'rating_note' => 'خدمة ممتازة وسرعة في إصدار العقد.',
                'Services' => true,
                'step' => $step,
                'is_completed' => $completed,
                'is_delete' => false,
                'is_real' => true,
                'is_review' => $i === 1,
                'notes' => 'بيانات تجريبية كاملة للاختبار — عقد إيجار '.$propertyType->name_ar.' في '.$city->name_ar.' (#'.($i + 1).').',
                'tenant_roles' => (bool) ($scenario['assign_tenant_role'] && $tenantRoleId),
                'tenant_role_id' => ($scenario['assign_tenant_role'] && $tenantRoleId) ? $tenantRoleId : null,
                'text_additional_terms' => 'يُسمح باصطحاب حيوان أليف صغير بشرط عدم الإزعاج.',
                'additional_terms' => true,
                'age_of_the_property' => 5 + $i * 3,
                'number_of_units_per_floor' => (string) (4 + $i),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name_real_estate' => $contractType === 'commercial' ? 'مجمع الأعمال — مبنى '.($i + 1) : 'عمارة الاختبار — '.$city->name_ar,
            ];

            $payload = $this->mergeOptionalFullTestFields($payload, $i, $city, $regionId, $tenantRoleId, $scenario);

            if (Schema::hasColumn('contracts', 'expiry_date')) {
                $payload['expiry_date'] = Carbon::now()->addYear()->addMonths($i)->toDateString();
            }

            $contract = Contract::query()->create($payload);

            Payment::query()->create([
                'name' => 'عقد توثيق',
                'amount' => 199 + $i * 50,
                'payment_date' => $createdAt->copy()->addDays(1 + $i)->toDateString(),
                'contract_uuid' => $contract->uuid,
                'tran_currency' => 'SAR',
                'payment_method' => ['mada', 'apple_pay', 'stc_pay'][$i % 3],
                'status' => 'success',
            ]);

            $contract->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        $this->command?->info('ContractRealDataSeeder: created '.self::COUNT.' completed contracts with full wizard-style fields.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    private function mergeOptionalFullTestFields(
        array $payload,
        int $i,
        City $city,
        int $regionId,
        ?int $tenantRoleId,
        array $scenario,
    ): array {
        $completedStatusId = Schema::hasTable('contract_statuses')
            ? ContractStatus::query()->where('name', 'مكتمل')->value('id')
            : null;

        if ($completedStatusId && Schema::hasColumn('contracts', 'contract_status_id')) {
            $payload['contract_status_id'] = (int) $completedStatusId;
        }

        if (Schema::hasColumn('contracts', 'notes_edits')) {
            $payload['notes_edits'] = 'ملاحظات التحرير التجريبية للعقد رقم '.($i + 1).'.';
        }

        $calendarDefaults = [
            'type_tenant_dob' => $i === 1 ? 'gregorian' : 'hijri',
            'type_dob_tenant_agent' => $scenario['with_tenant_agent'] ? 'hijri' : null,
            'type_contract_starting_date' => 'hijri',
            'type_instrument_history' => 'gregorian',
            'type_date_first_registration' => 'hijri',
            'type_agency_instrument_date_of_property_owner' => 'gregorian',
        ];
        foreach ($calendarDefaults as $col => $val) {
            if (Schema::hasColumn('contracts', $col) && $val !== null) {
                $payload[$col] = $val;
            }
        }

        if (Schema::hasColumn('contracts', 'type_dob')) {
            $payload['type_dob'] = 'hijri';
        }
        if (Schema::hasColumn('contracts', 'type_dob_property_owner')) {
            $payload['type_dob_property_owner'] = 'hijri';
        }
        if (Schema::hasColumn('contracts', 'type_dob_property_owner_agent') && ($payload['add_legal_agent_of_owner'] ?? false)) {
            $payload['type_dob_property_owner_agent'] = 'hijri';
        }

        if (($scenario['instrument_type'] ?? '') === 'property_ownership_owner_is_endowment') {
            if (Schema::hasColumn('contracts', 'copy_of_the_endowment_registration_certificate')) {
                $payload['copy_of_the_endowment_registration_certificate'] = 'seed-test/endowment-cert-placeholder.pdf';
            }
            if (Schema::hasColumn('contracts', 'copy_of_the_trusteeship_deed')) {
                $payload['copy_of_the_trusteeship_deed'] = 'seed-test/trusteeship-deed-placeholder.pdf';
            }
            if (Schema::hasColumn('contracts', 'is_multiple_trusteeship_deed_copy')) {
                $payload['is_multiple_trusteeship_deed_copy'] = false;
            }
        }

        if (($scenario['authorization_type'] ?? null) === 'agent_for_the_tenant') {
            if (Schema::hasColumn('contracts', 'city_of_the_tenant_legal_agent')) {
                $payload['city_of_the_tenant_legal_agent'] = $city->id;
            }
            if (Schema::hasColumn('contracts', 'region_of_the_tenant_legal_agent')) {
                $payload['region_of_the_tenant_legal_agent'] = $regionId;
            }
        }

        if (($scenario['assign_tenant_role'] ?? false) && $tenantRoleId && Schema::hasColumn('contracts', 'tenant_role_ids')) {
            $payload['tenant_role_ids'] = [$tenantRoleId];
            $payload['tenant_roles'] = true;
            $payload['tenant_role_id'] = $tenantRoleId;
        }

        return $payload;
    }
}
