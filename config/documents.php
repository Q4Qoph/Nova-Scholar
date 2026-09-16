<?php

return [
    'tika_url' => env('TIKA_URL', 'http://127.0.0.1:9998'),
    'tika_timeout_seconds' => (int) env('TIKA_TIMEOUT_SECONDS', 90),
];
