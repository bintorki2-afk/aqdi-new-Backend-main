<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Payment;
use App\Services\MoyasarPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentFailedDoesNotCompleteContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.moyasar.secret_key' => 'test_secret',
            'services.moyasar.base_url' => 'https://api.moyasar.com',
            'services.moyasar.payment_frontend_url' => 'https://frontend.test',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');
        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'contracts',
            'payments',
            'contract_paid_by_employees',
            'employees',
            'payment_messages',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function createMinimalSchema(): void
    {
        foreach ([
            'contracts',
            'payments',
            'contract_paid_by_employees',
            'employees',
            'payment_messages',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_paid_by_employees', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_uuid')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('customer_mobile', 32)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('contract_uuid')->nullable();
            $table->string('tran_currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_brand')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->string('contract_type')->nullable();
            $table->unsignedBigInteger('contract_term_in_years')->nullable();
            $table->string('app_or_web')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function test_failed_payment_redirect_does_not_complete_contract(): void
    {
        $contract = Contract::query()->create(['is_completed' => false]);
        $uuid = (string) $contract->uuid;

        Http::fake([
            "https://api.moyasar.com/v1/payments/pay_failed_{$uuid}" => Http::response([
                'id' => "pay_failed_{$uuid}",
                'status' => 'failed',
                'amount' => 57400,
                'currency' => 'SAR',
                'metadata' => ['contract_uuid' => $uuid],
                'source' => ['type' => 'creditcard'],
            ], 200),
            'https://api.moyasar.com/v1/invoices*' => Http::response(['invoices' => []], 200),
            'https://api.moyasar.com/v1/payments*' => Http::response(['payments' => []], 200),
        ]);

        $response = $this->getJson("/api/status/error/{$uuid}?id=pay_failed_{$uuid}&status=failed");

        $response->assertStatus(400);
        $this->assertFalse((bool) $contract->fresh()->is_completed);
        $this->assertDatabaseHas('payments', [
            'contract_uuid' => $uuid,
            'status' => 'failed',
        ]);
        $this->assertFalse(
            Payment::query()->where('contract_uuid', $uuid)->where('status', 'success')->exists()
        );
    }

    public function test_payment_result_after_failure_is_not_paid(): void
    {
        $contract = Contract::query()->create(['is_completed' => true]);
        $uuid = (string) $contract->uuid;

        Http::fake([
            "https://api.moyasar.com/v1/payments/pay_failed_again_{$uuid}" => Http::response([
                'id' => "pay_failed_again_{$uuid}",
                'status' => 'failed',
                'amount' => 57400,
                'currency' => 'SAR',
                'metadata' => ['contract_uuid' => $uuid],
                'source' => ['type' => 'creditcard'],
            ], 200),
            'https://api.moyasar.com/v1/invoices*' => Http::response(['invoices' => []], 200),
            'https://api.moyasar.com/v1/payments*' => Http::response(['payments' => []], 200),
        ]);

        $response = $this->getJson("/api/payment/result/{$uuid}?id=pay_failed_again_{$uuid}&status=failed");

        $response->assertOk();
        $response->assertJsonPath('data.paid', false);
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.is_completed', false);
        $this->assertFalse((bool) $contract->fresh()->is_completed);
    }

    public function test_failed_invoice_is_not_upgraded_by_other_paid_invoice(): void
    {
        $contract = Contract::query()->create(['is_completed' => false]);
        $uuid = (string) $contract->uuid;

        Http::fake([
            'https://api.moyasar.com/v1/payments/inv_failed_current' => Http::response([], 404),
            'https://api.moyasar.com/v1/invoices/inv_failed_current' => Http::response([
                'id' => 'inv_failed_current',
                'status' => 'initiated',
                'amount' => 57400,
                'currency' => 'SAR',
                'created_at' => now()->toIso8601String(),
                'metadata' => ['contract_uuid' => $uuid],
                'description' => 'Contract '.$uuid,
                'payments' => [
                    [
                        'id' => 'pay_nested_failed',
                        'status' => 'failed',
                        'amount' => 57400,
                        'currency' => 'SAR',
                        'created_at' => now()->toIso8601String(),
                        'source' => ['type' => 'creditcard'],
                    ],
                ],
            ], 200),
            'https://api.moyasar.com/v1/invoices*' => Http::response([
                'invoices' => [
                    [
                        'id' => 'inv_old_paid_other',
                        'status' => 'paid',
                        'amount' => 10000,
                        'currency' => 'SAR',
                        'created_at' => now()->subDay()->toIso8601String(),
                        'metadata' => ['contract_uuid' => $uuid],
                        'description' => 'Contract '.$uuid,
                        'payments' => [
                            [
                                'id' => 'pay_old_paid',
                                'status' => 'paid',
                                'amount' => 10000,
                                'currency' => 'SAR',
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://api.moyasar.com/v1/payments*' => Http::response(['payments' => []], 200),
        ]);

        app(MoyasarPaymentService::class)->processIpn(new Request([
            'id' => 'inv_failed_current',
            'status' => 'failed',
        ]), $uuid);

        $payload = app(MoyasarPaymentService::class)->paymentStatusPayload(
            $uuid,
            'error',
            'inv_failed_current',
            'inv_failed_current'
        );

        $this->assertFalse($payload['payment_confirmed']);
        $this->assertFalse((bool) $contract->fresh()->is_completed);
        $this->assertSame('failed', $payload['sync']['status'] ?? null);
    }

    public function test_stale_completed_contract_can_request_payment_url_after_failed_attempt(): void
    {
        $contract = Contract::query()->create([
            'is_completed' => true,
            'contract_type' => 'electronic',
            'contract_term_in_years' => 1,
        ]);
        $uuid = (string) $contract->uuid;

        Payment::query()->create([
            'amount' => 574,
            'contract_uuid' => $uuid,
            'tran_currency' => 'SAR',
            'status' => 'failed',
            'payment_date' => now(),
        ]);

        Http::fake([
            'https://api.moyasar.com/v1/invoices' => Http::response([
                'id' => 'inv_retry_ok',
                'url' => 'https://moyasar.test/i/inv_retry_ok',
                'status' => 'initiated',
            ], 201),
        ]);

        $result = app(MoyasarPaymentService::class)->requestPaymentRedirectUrl($uuid, 574);

        $this->assertSame('https://moyasar.test/i/inv_retry_ok?lang=ar', $result['payment_url']);
        $this->assertFalse((bool) $contract->fresh()->is_completed);
    }

    public function test_full_failed_flow_then_retry_is_not_blocked_by_completed_message(): void
    {
        $contract = Contract::query()->create(['is_completed' => false]);
        $uuid = (string) $contract->uuid;

        Http::fake([
            "https://api.moyasar.com/v1/payments/pay_card_fail_{$uuid}" => Http::response([
                'id' => "pay_card_fail_{$uuid}",
                'status' => 'failed',
                'amount' => 57400,
                'currency' => 'SAR',
                'metadata' => ['contract_uuid' => $uuid],
                'source' => ['type' => 'creditcard'],
            ], 200),
            'https://api.moyasar.com/v1/invoices' => Http::response([
                'id' => 'inv_after_fail',
                'url' => 'https://moyasar.test/i/inv_after_fail',
                'status' => 'initiated',
            ], 201),
            'https://api.moyasar.com/v1/invoices*' => Http::response(['invoices' => []], 200),
            'https://api.moyasar.com/v1/payments*' => Http::response(['payments' => []], 200),
        ]);

        // 1) User lands on failed payment page
        $this->getJson("/api/status/error/{$uuid}?id=pay_card_fail_{$uuid}&status=failed")
            ->assertStatus(400);

        $this->assertFalse((bool) $contract->fresh()->is_completed);

        // 2) Frontend checks result
        $this->getJson("/api/payment/result/{$uuid}?id=pay_card_fail_{$uuid}")
            ->assertOk()
            ->assertJsonPath('data.paid', false)
            ->assertJsonPath('data.is_completed', false);

        // 3) Retry payment must NOT return "العقد مكتمل، فشل التعديل"
        $result = app(MoyasarPaymentService::class)->requestPaymentRedirectUrl($uuid, 574);
        $this->assertArrayHasKey('payment_url', $result);
        $this->assertFalse((bool) $contract->fresh()->is_completed);
    }
}
