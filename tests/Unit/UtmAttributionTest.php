<?php

namespace Tests\Unit;

use App\Support\Marketing\UtmAttribution;
use Illuminate\Http\Request;
use Tests\TestCase;

class UtmAttributionTest extends TestCase
{
    public function test_normalizes_platform_aliases_and_click_ids(): void
    {
        $fromFacebook = UtmAttribution::fromArray(['utm_source' => 'Facebook']);
        $this->assertSame('meta', $fromFacebook->utm_source);

        $fromGclid = UtmAttribution::fromArray(['gclid' => 'abc']);
        $this->assertSame('google', $fromGclid->utm_source);
        $this->assertSame('abc', $fromGclid->gclid);

        $fromTiktokClick = UtmAttribution::fromRequest(Request::create('/', 'GET', [
            'ttclid' => 'tt-1',
        ]));
        $this->assertSame('tiktok', $fromTiktokClick->utm_source);
    }

    public function test_build_query_uses_canonical_source(): void
    {
        $query = UtmAttribution::buildQuery('x', 'X - Promotion', 'lease', 'ad-1');

        $this->assertStringContainsString('utm_source=twitter', $query);
        $this->assertStringContainsString('utm_medium=cpc', $query);
        $this->assertStringContainsString('utm_campaign=X+-+Promotion', $query);
        $this->assertStringContainsString('utm_term=lease', $query);
    }

    public function test_empty_payload_is_empty(): void
    {
        $this->assertTrue((new UtmAttribution)->isEmpty());
        $this->assertFalse(UtmAttribution::fromArray(['utm_source' => 'google'])->isEmpty());
    }
}
