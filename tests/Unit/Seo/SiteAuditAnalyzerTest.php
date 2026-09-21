<?php

namespace Tests\Unit\Seo;

use App\Services\Seo\SiteAuditAnalyzer;
use App\Support\SeoCrawlIssueType;
use Tests\TestCase;

class SiteAuditAnalyzerTest extends TestCase
{
    public function test_flags_missing_duplicate_slow_404_and_alt(): void
    {
        $home = $this->page('https://aqdi.sa', '/', [
            'title' => 'Home',
            'meta_description' => 'Home desc',
            'h1_count' => 1,
            'outbound_urls' => ['https://aqdi.sa/about-us', 'https://aqdi.sa/commercial', 'https://aqdi.sa/residential', 'https://aqdi.sa/missing'],
            'broken_links' => [[
                'url' => 'https://aqdi.sa/missing',
                'path' => 'missing/',
                'status_code' => 404,
            ]],
        ]);
        $about = $this->page('https://aqdi.sa/about-us', 'about-us/', [
            'title' => 'About',
            'meta_description' => 'About Aqdi',
            'h1_count' => 1,
            'outbound_urls' => ['https://aqdi.sa'],
        ]);
        $commercial = $this->page('https://aqdi.sa/commercial', 'commercial/', [
            'title' => 'Units',
            'meta_description' => null,
            'h1_count' => 1,
            'outbound_urls' => ['https://aqdi.sa'],
        ]);
        $residential = $this->page('https://aqdi.sa/residential', 'residential/', [
            'title' => 'Units',
            'meta_description' => 'Home desc',
            'h1_count' => 0,
            'images_missing_alt' => 4,
            'load_time_ms' => 3800,
            'outbound_urls' => ['https://aqdi.sa'],
        ]);
        $missing = $this->page('https://aqdi.sa/missing', 'missing/', [
            'status_code' => 404,
            'is_html' => true,
            'is_indexable' => false,
            'title' => null,
            'meta_description' => null,
        ]);

        $analyzer = new SiteAuditAnalyzer;
        $result = $analyzer->analyze([$home, $about, $commercial, $residential, $missing], 3000, 1);
        $types = array_column($result['issues'], 'type');

        $this->assertContains(SeoCrawlIssueType::Page404->value, $types);
        $this->assertContains(SeoCrawlIssueType::BrokenLink->value, $types);
        $this->assertContains(SeoCrawlIssueType::MissingDescription->value, $types);
        $this->assertContains(SeoCrawlIssueType::DuplicateTitle->value, $types);
        $this->assertContains(SeoCrawlIssueType::DuplicateDescription->value, $types);
        $this->assertContains(SeoCrawlIssueType::MissingH1->value, $types);
        $this->assertContains(SeoCrawlIssueType::SlowPage->value, $types);
        $this->assertContains(SeoCrawlIssueType::ImagesMissingAlt->value, $types);

        $slow = collect($result['issues'])->firstWhere('type', SeoCrawlIssueType::SlowPage->value);
        $this->assertSame('residential/', $slow['path']);
        $this->assertStringContainsString('3.8', $slow['message_en']);

        $duplicate = collect($result['issues'])->firstWhere('type', SeoCrawlIssueType::DuplicateTitle->value);
        $this->assertNotNull($duplicate['details']['other_path']);

        $summary = $analyzer->summarize([$home, $about, $commercial, $residential, $missing], $result['issues'], $result['inbound']);
        $this->assertSame(4, $summary['indexed_pages']);
        $this->assertSame(2, $summary['broken_pages']);
        $this->assertGreaterThan(0, $summary['on_page_issues']);
        $this->assertSame(1, $summary['healthy_pages']);
    }

    public function test_weak_internal_links_skip_homepage(): void
    {
        $home = $this->page('https://aqdi.sa', '/', [
            'title' => 'Home',
            'meta_description' => 'd',
            'h1_count' => 1,
            'outbound_urls' => ['https://aqdi.sa/faq'],
        ]);
        $faq = $this->page('https://aqdi.sa/faq', 'faq/', [
            'title' => 'FAQ',
            'meta_description' => 'faq',
            'h1_count' => 1,
            'outbound_urls' => ['https://aqdi.sa'],
        ]);

        $analyzer = new SiteAuditAnalyzer;
        $result = $analyzer->analyze([$home, $faq], 3000, 2);
        $weak = collect($result['issues'])->where('type', SeoCrawlIssueType::WeakInternalLinks->value);

        $this->assertCount(1, $weak);
        $this->assertSame('faq/', $weak->first()['path']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function page(string $url, string $path, array $overrides = []): array
    {
        return array_merge([
            'url' => $url,
            'path' => $path,
            'status_code' => 200,
            'load_time_ms' => 200,
            'content_type' => 'text/html',
            'title' => 'Title',
            'meta_description' => 'Desc',
            'h1s' => ['H1'],
            'h1_count' => 1,
            'image_count' => 0,
            'images_missing_alt' => 0,
            'outbound_urls' => [],
            'is_html' => true,
            'is_indexable' => true,
            'failed' => false,
            'broken_links' => [],
        ], $overrides);
    }
}
