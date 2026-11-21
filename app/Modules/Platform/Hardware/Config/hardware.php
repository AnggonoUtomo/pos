<?php

return [
    'enabled' => filter_var(env('POS_HARDWARE_ENABLED', false), FILTER_VALIDATE_BOOL),
];
