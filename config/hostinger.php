<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (seconds)
    |--------------------------------------------------------------------------
    |
    | Controls how long each Hostinger resource is cached locally.
    | Lower values increase freshness at the cost of more API calls.
    |
    */

    'cache_ttl' => [
        'vps_list'            => (int) env('HOSTINGER_CACHE_TTL_VPS_LIST', 86400),
        'os_templates'        => (int) env('HOSTINGER_CACHE_TTL_OS_TEMPLATES', 86400),
        'datacenters'         => (int) env('HOSTINGER_CACHE_TTL_DATACENTERS', 86400),
        'domain_availability' => (int) env('HOSTINGER_CACHE_TTL_DOMAIN_AVAILABILITY', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Quota Controls
    |--------------------------------------------------------------------------
    |
    | Hostinger enforces daily API call limits. These thresholds allow the
    | application to warn operators before the hard limit is hit.
    |
    */

    'quota' => [
        // Warning threshold: structured log emitted when daily calls cross this value
        'warn_at'    => (int) env('HOSTINGER_API_QUOTA_WARN_AT', 800),
        // Hard limit: 0 = disabled (warn-only). Set to enforce a 503 ceiling.
        'hard_limit' => (int) env('HOSTINGER_API_QUOTA_HARD_LIMIT', 0),
    ],

];
