<?php

return [
    'printing_enabled' => filter_var(env('POS_HARDWARE_PRINTING_ENABLED', false), FILTER_VALIDATE_BOOL),
    'allowed_hosts' => array_values(array_unique(array_filter(array_map(
        static fn ($host) => strtolower(trim($host)), explode(',', env('POS_QZ_ALLOWED_HOSTS', 'localhost,127.0.0.1'))
    )))),
    'enabled' => filter_var(env('POS_HARDWARE_ENABLED', false), FILTER_VALIDATE_BOOL),
];
