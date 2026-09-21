<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiNotFoundDoesNotLeakDebugTest extends TestCase
{
    public function test_missing_api_route_returns_generic_json_when_debug_is_on(): void
    {
        config(['app.debug' => true]);

        $response = $this->getJson('/api/this-route-does-not-exist');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'code' => 404,
            ]);

        $this->assertArrayNotHasKey('file', $response->json());
        $this->assertArrayNotHasKey('trace', $response->json());
        $this->assertStringNotContainsString('vendor/laravel', $response->getContent());
        $this->assertStringNotContainsString('Exception', $response->getContent());
    }
}
