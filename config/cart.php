<?php

return [
    'ttl' => (int) env('CART_TTL', 259200),
    'key_prefix' => env('CART_KEY_PREFIX', 'cart:'),
];
