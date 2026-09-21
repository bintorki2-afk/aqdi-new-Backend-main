<?php

use App\Services\Marketing\AdSpend\GoogleAdsSpendProvider;
use App\Services\Marketing\AdSpend\MetaAdsSpendProvider;
use App\Services\Marketing\AdSpend\SnapchatAdsSpendProvider;
use App\Services\Marketing\AdSpend\TikTokAdsSpendProvider;
use App\Services\Marketing\AdSpend\TwitterAdsSpendProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | First-touch attribution
    |--------------------------------------------------------------------------
    */
    'attribution' => [
        'cookie' => env('ADS_ATTRIBUTION_COOKIE', 'aqdi_first_touch'),
        'session_key' => 'aqdi_first_touch',
        'cookie_minutes' => (int) env('ADS_ATTRIBUTION_COOKIE_MINUTES', 60 * 24 * 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical utm_source values for landing URLs
    |--------------------------------------------------------------------------
    |
    | Put this query string on every paid landing URL (and WhatsApp links):
    | ?utm_source={source}&utm_medium=cpc&utm_campaign={campaign}&utm_term={keyword}&utm_content={adset}
    |
    */
    'utm' => [
        'medium_default' => 'cpc',
        'sources' => [
            'google' => ['ar' => 'Google', 'en' => 'Google'],
            'meta' => ['ar' => 'إعلان مدفوع', 'en' => 'Paid Ad'],
            'tiktok' => ['ar' => 'TikTok', 'en' => 'TikTok'],
            'twitter' => ['ar' => 'X', 'en' => 'X'],
            'snapchat' => ['ar' => 'Snapchat', 'en' => 'Snapchat'],
            'whatsapp' => ['ar' => 'WhatsApp', 'en' => 'WhatsApp'],
            'paid' => ['ar' => 'إعلان مدفوع', 'en' => 'Paid Ad'],
            'direct' => ['ar' => 'مباشر', 'en' => 'Direct'],
        ],
        'aliases' => [
            'google' => 'google',
            'googleads' => 'google',
            'google_ads' => 'google',
            'adwords' => 'google',
            'meta' => 'meta',
            'facebook' => 'meta',
            'fb' => 'meta',
            'instagram' => 'meta',
            'ig' => 'meta',
            'tiktok' => 'tiktok',
            'tt' => 'tiktok',
            'twitter' => 'twitter',
            'x' => 'twitter',
            'snapchat' => 'snapchat',
            'snap' => 'snapchat',
            'whatsapp' => 'whatsapp',
            'wa' => 'whatsapp',
            'paid' => 'paid',
            'direct' => 'direct',
            '(direct)' => 'direct',
            '(none)' => 'direct',
        ],
        'click_ids' => [
            'gclid' => 'google',
            'fbclid' => 'meta',
            'ttclid' => 'tiktok',
            'twclid' => 'twitter',
            'sccid' => 'snapchat',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ad accounts — read-only credentials (no spend/create ads)
    |--------------------------------------------------------------------------
    */
    'platforms' => [
        'google' => [
            'label' => 'Google Ads',
            'utm_source' => 'google',
            'provider' => GoogleAdsSpendProvider::class,
            'credentials' => [
                'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
                'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
                'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
                'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
                'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
                'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
            ],
            'required' => [
                'developer_token',
                'client_id',
                'client_secret',
                'refresh_token',
                'customer_id',
            ],
        ],
        'meta' => [
            'label' => 'Meta (Facebook / Instagram / WhatsApp Ads)',
            'utm_source' => 'meta',
            'provider' => MetaAdsSpendProvider::class,
            'credentials' => [
                'access_token' => env('META_ADS_ACCESS_TOKEN'),
                'ad_account_id' => env('META_ADS_ACCOUNT_ID'),
                'app_id' => env('META_ADS_APP_ID'),
                'app_secret' => env('META_ADS_APP_SECRET'),
            ],
            'required' => [
                'access_token',
                'ad_account_id',
            ],
        ],
        'tiktok' => [
            'label' => 'TikTok Ads',
            'utm_source' => 'tiktok',
            'provider' => TikTokAdsSpendProvider::class,
            'credentials' => [
                'access_token' => env('TIKTOK_ADS_ACCESS_TOKEN'),
                'advertiser_id' => env('TIKTOK_ADS_ADVERTISER_ID'),
                'app_id' => env('TIKTOK_ADS_APP_ID'),
                'app_secret' => env('TIKTOK_ADS_APP_SECRET'),
            ],
            'required' => [
                'access_token',
                'advertiser_id',
            ],
        ],
        'snapchat' => [
            'label' => 'Snapchat Ads',
            'utm_source' => 'snapchat',
            'provider' => SnapchatAdsSpendProvider::class,
            'credentials' => [
                'client_id' => env('SNAPCHAT_ADS_CLIENT_ID'),
                'client_secret' => env('SNAPCHAT_ADS_CLIENT_SECRET'),
                'refresh_token' => env('SNAPCHAT_ADS_REFRESH_TOKEN'),
                'ad_account_id' => env('SNAPCHAT_ADS_ACCOUNT_ID'),
            ],
            'required' => [
                'client_id',
                'client_secret',
                'refresh_token',
                'ad_account_id',
            ],
        ],
        'twitter' => [
            'label' => 'X / Twitter Ads',
            'utm_source' => 'twitter',
            'provider' => TwitterAdsSpendProvider::class,
            'credentials' => [
                'account_id' => env('TWITTER_ADS_ACCOUNT_ID'),
                'bearer_token' => env('TWITTER_ADS_BEARER_TOKEN'),
                'consumer_key' => env('TWITTER_ADS_CONSUMER_KEY'),
                'consumer_secret' => env('TWITTER_ADS_CONSUMER_SECRET'),
                'access_token' => env('TWITTER_ADS_ACCESS_TOKEN'),
                'access_token_secret' => env('TWITTER_ADS_ACCESS_TOKEN_SECRET'),
            ],
            'required' => [
                'account_id',
                'bearer_token',
            ],
        ],
    ],
];
