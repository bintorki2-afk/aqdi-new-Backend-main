<?php

namespace App\Services\Seo;

use RuntimeException;

class SeoCrawlStoppedException extends RuntimeException
{
    /**
     * @param  list<array<string, mixed>>  $pages
     */
    public function __construct(public array $pages = [])
    {
        parent::__construct('SEO crawl stopped.');
    }
}
