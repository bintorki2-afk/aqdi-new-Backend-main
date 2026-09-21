<?php

namespace Tests\Unit;

use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_every_screen_maps_to_a_configured_section(): void
    {
        $sections = array_keys(config('permissions.sections'));
        $screens = config('permissions.screens');

        $this->assertNotEmpty($screens);

        foreach ($screens as $screen => $section) {
            $this->assertContains(
                $section,
                $sections,
                "Screen [{$screen}] maps to unknown section [{$section}]."
            );
        }
    }

    public function test_duplicate_screens_point_at_a_canonical_screen(): void
    {
        $screens = config('permissions.screens');

        foreach (config('permissions.duplicate_screens') as $duplicate => $canonical) {
            $this->assertArrayHasKey(
                $canonical,
                $screens,
                "Duplicate [{$duplicate}] must resolve to a canonical screen in permissions.screens."
            );
            $this->assertArrayNotHasKey(
                $duplicate,
                $screens,
                "Duplicate [{$duplicate}] must not have its own catalog gate."
            );
        }
    }
}
