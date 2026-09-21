<?php

namespace Tests\Unit;

use App\Services\TaqnyatSmsService;
use PHPUnit\Framework\TestCase;

class TaqnyatSmsServiceTest extends TestCase
{
    public function test_parses_cost_from_json_string(): void
    {
        $json = json_encode([
            'statusCode' => 201,
            'messageId' => '5452899970',
            'cost' => '0.1500',
            'currency' => 'SAR',
            'totalCount' => 1,
        ]);

        $this->assertSame(0.15, TaqnyatSmsService::parseCost($json));
    }

    public function test_parses_cost_from_array(): void
    {
        $this->assertSame(0.026, TaqnyatSmsService::parseCost(['statusCode' => 201, 'cost' => 0.026]));
    }

    public function test_returns_null_when_cost_missing_or_zero(): void
    {
        $this->assertNull(TaqnyatSmsService::parseCost(['statusCode' => 201]));
        $this->assertNull(TaqnyatSmsService::parseCost(['cost' => 0]));
        $this->assertNull(TaqnyatSmsService::parseCost('not-json'));
        $this->assertNull(TaqnyatSmsService::parseCost(null));
    }

    public function test_successful_response_uses_status_code(): void
    {
        $service = new TaqnyatSmsService;

        $this->assertTrue($service->isSuccessfulResponse(['statusCode' => 201, 'cost' => 0.1]));
        $this->assertFalse($service->isSuccessfulResponse(['statusCode' => 401]));
        $this->assertFalse($service->isSuccessfulResponse(null));
        $this->assertFalse($service->isSuccessfulResponse(''));
    }
}
