<?php

namespace Tests\Feature\Admin;

use App\Models\AppVersion;
use App\Models\GeneralSetting;
use App\Services\AppStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppStatusServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'general_settings.website_status' => ['label_ar' => 'حالة الموقع', 'default' => true],
            'general_settings.mobile_status' => ['label_ar' => 'حالة التطبيق', 'default' => true],
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMinimalSchema();
        app(AppStatusService::class)->ensureCatalog();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('general_settings');

        parent::tearDown();
    }

    public function test_website_and_mobile_default_to_open(): void
    {
        $service = app(AppStatusService::class);
        $payload = $service->publicPayload();

        $this->assertTrue($payload['website']['is_open']);
        $this->assertTrue($payload['mobile']['is_open']);
        $this->assertFalse($payload['ios']['force_update']);
        $this->assertFalse($payload['android']['force_update']);
    }

    public function test_closing_website_and_mobile_flips_is_open(): void
    {
        $service = app(AppStatusService::class);

        $updated = $service->update([
            'website' => ['is_open' => false],
            'mobile' => ['is_open' => false],
        ]);

        $this->assertFalse($updated['website']['is_open']);
        $this->assertFalse($updated['mobile']['is_open']);
        $this->assertFalse($service->isWebsiteOpen());
        $this->assertFalse($service->isMobileOpen());
        $this->assertFalse(GeneralSetting::isEnabled('website_status'));
        $this->assertFalse(GeneralSetting::isEnabled('mobile_status'));
    }

    public function test_website_payload_reports_closed_with_message(): void
    {
        $service = app(AppStatusService::class);
        $service->update(['website' => ['is_open' => false]]);

        $payload = $service->websitePayload();

        $this->assertFalse($payload['is_open']);
        $this->assertNotEmpty($payload['message']);
        $this->assertNotEmpty($payload['message_ar']);

        $request = \Illuminate\Http\Request::create('/api/v2/settings', 'GET', [], [], [], [
            'HTTP_X_CLIENT' => 'website',
        ]);
        $this->assertTrue($service->isWebsiteClient($request));

        $mobile = \Illuminate\Http\Request::create('/api/v2/settings', 'GET', ['platform' => 'ios']);
        $this->assertFalse($service->isWebsiteClient($mobile));
    }

    public function test_force_update_when_current_version_is_below_min(): void
    {
        $service = app(AppStatusService::class);
        $service->update([
            'ios' => [
                'latest_version' => '2.0.0',
                'min_version' => '1.5.0',
                'force_update' => false,
                'store_url' => 'https://apps.apple.com/app/aqdi',
            ],
        ]);

        $payload = $service->publicPayload('ios', '1.4.0');

        $this->assertTrue($payload['update']['force_update']);
        $this->assertFalse($payload['update']['optional_update']);
        $this->assertSame('1.4.0', $payload['current_version']);
        $this->assertSame('ios', $payload['platform']);
        $this->assertSame('https://apps.apple.com/app/aqdi', $payload['ios']['store_url']);
    }

    public function test_optional_update_when_current_is_between_min_and_latest(): void
    {
        $service = app(AppStatusService::class);
        $service->update([
            'android' => [
                'latest_version' => '3.2.0',
                'min_version' => '3.0.0',
                'force_update' => false,
            ],
        ]);

        $payload = $service->publicPayload('android', '3.1.0');

        $this->assertFalse($payload['update']['force_update']);
        $this->assertTrue($payload['update']['optional_update']);
    }

    public function test_admin_force_update_flag_overrides_current_version(): void
    {
        $service = app(AppStatusService::class);
        $service->update([
            'ios' => [
                'latest_version' => '1.0.0',
                'min_version' => '1.0.0',
                'force_update' => true,
            ],
        ]);

        $payload = $service->publicPayload('ios', '1.0.0');

        $this->assertTrue($payload['update']['force_update']);
        $this->assertSame(AppVersion::PLATFORM_IOS, $payload['ios']['platform']);
    }

    private function createMinimalSchema(): void
    {
        Schema::create('general_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_ar');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('app_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('platform')->unique();
            $table->string('latest_version')->nullable();
            $table->string('min_version')->nullable();
            $table->boolean('force_update')->default(false);
            $table->string('store_url')->nullable();
            $table->text('message_ar')->nullable();
            $table->text('message_en')->nullable();
            $table->timestamps();
        });
    }
}
