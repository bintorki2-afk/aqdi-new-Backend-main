<?php

namespace Tests;

use App\Support\Marketing\AttributionSchema;
use App\Support\SchemaCache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        SchemaCache::flush();
        AttributionSchema::flush();
    }
}
