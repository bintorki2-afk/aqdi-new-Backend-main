<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\Admin\ReportsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsProfitsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'sms_logs',
            'operating_expenses',
            'refundable_contracts',
            'coupon_usages',
            'payments',
            'contracts',
            'contract_periods',
            'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_profits_deducts_ejar_gateway_and_messaging_costs(): void
    {
        Setting::query()->create([
            'moyasar_mada_percent' => 1.75,
            'moyasar_credit_percent' => 2.50,
            'moyasar_fixed_fee' => 1.00,
            'marketing_budget' => 0,
        ]);

        DB::table('contracts')->insert([
            'uuid' => 111111,
            'contract_type' => 'housing',
            'is_delete' => 0,
            'step' => 6,
            'is_completed' => 1,
            'total_months' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::query()->create([
            'name' => 'housing paid',
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'contract_uuid' => '111111',
            'tran_currency' => 'SAR',
            'payment_method' => 'creditcard',
            'payment_brand' => 'mada',
            'status' => 'success',
        ]);

        SmsLog::query()->create([
            'phone_number' => '00966500000000',
            'message' => 'otp',
            'type' => 'login',
            'cost' => 0.15,
            'sent_at' => now(),
        ]);

        $result = app(ReportsService::class)->profits([
            'key' => 'all',
            'label_ar' => 'كل الفترات',
            'range' => null,
            'date_from' => null,
            'date_to' => null,
        ], false)->resolve();

        $labels = array_column($result['pnl'], 'label');
        $byLabel = collect($result['pnl'])->keyBy('label');

        $this->assertSame([
            'دخل العملاء (المحصّل)',
            'مبالغ مسترجعة',
            'صافي الإيراد',
            'رسوم منصة إيجار',
            'رسوم بوابة الدفع (موياسر)',
            'تكاليف الرسائل',
            'إجمالي الربح',
            'مصاريف الإعلانات',
            'مصاريف تشغيلية',
            'صافي الربح',
        ], $labels);

        $this->assertSame(200, $byLabel['دخل العملاء (المحصّل)']['value']);
        $this->assertSame(0, $byLabel['مبالغ مسترجعة']['value']);
        $this->assertSame(200, $byLabel['صافي الإيراد']['value']);
        $this->assertSame(-125, $byLabel['رسوم منصة إيجار']['value']);
        $this->assertSame(-4.5, $byLabel['رسوم بوابة الدفع (موياسر)']['value']);
        $this->assertSame(-0.15, $byLabel['تكاليف الرسائل']['value']);
        $this->assertSame(70.35, $byLabel['إجمالي الربح']['value']);
        $this->assertSame(70.35, $result['kpis']['gross_profit']);
        $this->assertSame(125, $result['kpis']['ejar_platform_fees']);
        $this->assertSame(4.5, $result['kpis']['gateway_fee']);
        $this->assertSame(0.15, $result['kpis']['messaging_cost']);

        $housingFirst = collect($result['service_profitability'])->firstWhere('service', 'توثيق سكني - سنة أولى');
        $this->assertNotNull($housingFirst);
        $this->assertSame(249, $housingFirst['revenue']);
        $this->assertSame(125, $housingFirst['ejar_fee']);
        $this->assertSame(6.23, $housingFirst['gateway_fee']);
        $this->assertSame(117.77, $housingFirst['profit']);
        $this->assertSame(47, $housingFirst['margin_percent']);

        $housingUnit = collect($result['unit_economics'])->firstWhere('service', 'توثيق سكني - سنة أولى');
        $this->assertNotNull($housingUnit);
        $this->assertSame(249, $housingUnit['customer_pays']);
        $this->assertSame(125, $housingUnit['ejar_fee']);
        $this->assertSame(7.23, $housingUnit['moyasar_fee']);
        $this->assertSame(116.77, $housingUnit['margin']);
        $this->assertFalse($housingUnit['low_margin']);

        $housingExtra = collect($result['unit_economics'])->firstWhere('service', 'سكني - سنة إضافية');
        $this->assertTrue($housingExtra['low_margin']);
        $this->assertSame(14, $housingExtra['margin_percent']);

        $meterRow = collect($result['unit_economics'])->firstWhere('service', 'نقل العداد');
        $this->assertNotNull($meterRow);
        $this->assertSame(0, $meterRow['customer_pays']);
        $this->assertSame(0, $meterRow['moyasar_fee']);
    }

    public function test_one_day_period_prorates_fixed_monthly_costs_and_exposes_finance_kpis(): void
    {
        Setting::query()->create([
            'moyasar_mada_percent' => 1.75,
            'moyasar_credit_percent' => 2.50,
            'moyasar_fixed_fee' => 1.00,
            'marketing_budget' => 15000,
            'operating_budget' => 10000,
            'monthly_salaries' => 30000,
            'meter_transfer_fee' => 10,
        ]);

        DB::table('contracts')->insert([
            'uuid' => 222222,
            'contract_type' => 'housing',
            'is_delete' => 0,
            'step' => 6,
            'is_completed' => 1,
            'total_months' => 12,
            'electricity_meter_ownership' => 'tenant',
            'water_meter_ownership' => 'tenant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::query()->create([
            'name' => 'today paid',
            'amount' => 2743,
            'payment_date' => now()->toDateString(),
            'contract_uuid' => '222222',
            'tran_currency' => 'SAR',
            'payment_method' => 'creditcard',
            'payment_brand' => 'visa',
            'status' => 'success',
        ]);

        $today = now();
        $result = app(ReportsService::class)->profits([
            'key' => 'today',
            'label_ar' => 'اليوم',
            'range' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'date_from' => $today->toDateString(),
            'date_to' => $today->toDateString(),
        ], true)->resolve();

        $this->assertSame(1, $result['kpis']['proration_days']);
        $this->assertSame(30, $result['kpis']['proration_month_days']);
        $this->assertSame(500, $result['kpis']['ad_spend']);
        $this->assertSame(333.33, collect($result['pnl'])->firstWhere('label', 'مصاريف تشغيلية')['value'] * -1);
        $this->assertSame(-1000, collect($result['pnl'])->firstWhere('label', 'الرواتب')['value']);

        $this->assertSame(1, $result['kpis']['paid_contracts_count']);
        $this->assertSame(2, $result['collected_breakdown']['meter_units']);
        $this->assertSame(20, $result['collected_breakdown']['meter_transfers']);
        $this->assertSame(2723, $result['collected_breakdown']['documentation']);
        $this->assertSame(1, $result['source_summary']['items'][0]['orders_count']);
        $this->assertSame(2, collect($result['source_summary']['items'])->firstWhere('key', 'meter_transfers')['units_count']);

        $contribution = 2743 - 125 - round(2743 * 0.025 + 1, 2);
        $this->assertSame(round($contribution, 2), $result['kpis']['operating_profit_per_contract']);
        $this->assertSame(500, $result['kpis']['cac']);
        $this->assertGreaterThan(0, $result['kpis']['monthly_break_even_contracts']);

        $meterRow = collect($result['unit_economics'])->firstWhere('service', 'نقل العداد');
        $this->assertSame(10, $meterRow['customer_pays']);
        $this->assertSame(100, $meterRow['margin_percent']);
        $this->assertFalse($meterRow['low_margin']);
    }

    public function test_profit_settings_expose_moyasar_rate_fields_and_alias_credit_percent(): void
    {
        $service = app(ReportsService::class);

        $defaults = $service->profitSettings(false)->resolve();
        $this->assertSame(1.75, $defaults['moyasar_mada_percent']);
        $this->assertSame(2.5, $defaults['moyasar_credit_percent']);
        $this->assertSame(2.5, $defaults['moyasar_fee_percent']);
        $this->assertSame(1.0, $defaults['moyasar_fixed_fee']);
        $this->assertSame(30, $defaults['proration_month_days']);
        $this->assertSame(0.0, $defaults['meter_transfer_fee']);

        $updated = $service->updateProfitSettings([
            'moyasar_mada_percent' => 1.80,
            'moyasar_fee_percent' => 2.75,
            'moyasar_fixed_fee' => 1.00,
            'meter_transfer_fee' => 10,
        ], false)->resolve();

        $this->assertSame(1.8, $updated['moyasar_mada_percent']);
        $this->assertSame(2.75, $updated['moyasar_credit_percent']);
        $this->assertSame(2.75, $updated['moyasar_fee_percent']);
        $this->assertSame(1.0, $updated['moyasar_fixed_fee']);
        $this->assertSame(10.0, $updated['meter_transfer_fee']);
    }

    private function createMinimalSchema(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('moyasar_fee_percent', 5, 2)->nullable();
            $table->decimal('moyasar_mada_percent', 5, 2)->nullable();
            $table->decimal('moyasar_credit_percent', 5, 2)->nullable();
            $table->decimal('moyasar_fixed_fee', 8, 2)->nullable();
            $table->decimal('marketing_budget', 12, 2)->nullable();
            $table->decimal('monthly_salaries', 12, 2)->nullable();
            $table->decimal('operating_budget', 12, 2)->nullable();
            $table->decimal('meter_transfer_fee', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('contract_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('period')->nullable();
            $table->string('note_ar')->nullable();
            $table->string('contract_type')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('uuid')->unique();
            $table->string('contract_type')->nullable();
            $table->unsignedTinyInteger('is_delete')->default(0);
            $table->unsignedInteger('step')->default(6);
            $table->unsignedTinyInteger('is_completed')->default(1);
            $table->unsignedBigInteger('contract_term_in_years')->nullable();
            $table->string('duration_preset')->nullable();
            $table->unsignedTinyInteger('duration_years')->nullable();
            $table->unsignedTinyInteger('duration_months')->nullable();
            $table->unsignedSmallInteger('total_months')->nullable();
            $table->string('electricity_meter_ownership')->nullable();
            $table->string('water_meter_ownership')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('contract_uuid');
            $table->string('tran_currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_brand')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone_number');
            $table->text('message');
            $table->string('sms_id')->nullable();
            $table->string('type');
            $table->decimal('cost', 10, 4)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operating_expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('refundable_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('admin_confirmed')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('contract_uuid')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }
}
