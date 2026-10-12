<?php

return [
    'simulated' => (bool) env('WEB_ORDERS_SIMULATED', false),
    'enabled' => (bool) env('WEB_ORDERS_ENABLED', false),
    // Base URL including /api, without a trailing slash.
    'base_url' => env('WEB_ORDERS_ERP_URL'),
    'token' => env('WEB_ORDERS_TOKEN'),
    'source' => env('WEB_ORDERS_SOURCE', 'lumens-ecommerce'),
];
