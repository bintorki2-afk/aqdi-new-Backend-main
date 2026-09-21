<?php

namespace App\Support;

enum SeoCrawlIssueType: string
{
    case DuplicateDescription = 'duplicate_description';
    case MissingTitle = 'missing_title';
    case DuplicateTitle = 'duplicate_title';
    case NonIndexable = 'non_indexable';
    case SlowPage = 'slow_page';
    case Page404 = 'page_404';
    case BrokenLink = 'broken_link';
    case WeakInternalLinks = 'weak_internal_links';
    case MissingH1 = 'missing_h1';
    case ImagesMissingAlt = 'images_missing_alt';
    case MissingDescription = 'missing_description';

    /**
     * Card order matching the Arabic dashboard grid (right-to-left, then down).
     *
     * @return list<string>
     */
    public static function dashboardOrder(): array
    {
        return [
            self::DuplicateDescription->value,
            self::MissingTitle->value,
            self::DuplicateTitle->value,
            self::NonIndexable->value,
            self::SlowPage->value,
            self::Page404->value,
            self::BrokenLink->value,
            'healthy_pages',
            self::WeakInternalLinks->value,
            self::MissingH1->value,
            self::ImagesMissingAlt->value,
            self::MissingDescription->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function onPageTypes(): array
    {
        return [
            self::MissingTitle->value,
            self::DuplicateTitle->value,
            self::MissingDescription->value,
            self::DuplicateDescription->value,
            self::MissingH1->value,
            self::SlowPage->value,
            self::WeakInternalLinks->value,
            self::ImagesMissingAlt->value,
        ];
    }

    public function severity(): string
    {
        return match ($this) {
            self::MissingTitle, self::Page404, self::BrokenLink => 'high',
            self::MissingDescription, self::DuplicateTitle, self::MissingH1, self::SlowPage, self::NonIndexable => 'medium',
            self::DuplicateDescription, self::WeakInternalLinks, self::ImagesMissingAlt => 'low',
        };
    }

    public function labelAr(): string
    {
        return trans('seo_crawl.categories.'.$this->value, [], 'ar');
    }

    public function labelEn(): string
    {
        return trans('seo_crawl.categories.'.$this->value, [], 'en');
    }
}
