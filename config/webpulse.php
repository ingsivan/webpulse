<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Limits
    |--------------------------------------------------------------------------
    |
    | Upper bounds for a single audit. They keep audits fast and stop a
    | visitor from turning WebPulse into a crawler against third parties.
    |
    */

    'max_links_checked' => (int) env('WEBPULSE_MAX_LINKS', 25),

    'max_images_checked' => (int) env('WEBPULSE_MAX_IMAGES', 15),

    'max_redirects' => 5,

    'connect_timeout' => 5,

    'timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */

    'slow_page_ms' => 2000,

    'very_slow_page_ms' => 4000,

    'heavy_image_bytes' => 300 * 1024,

    'heavy_html_bytes' => 500 * 1024,

    'user_agent' => 'WebPulseBot/1.0 (+https://github.com/ingsivan/webpulse)',

];
