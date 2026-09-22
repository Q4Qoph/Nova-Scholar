<?php

return [
    'driver' => env('AI_DRIVER', 'groq'),

    'school_enabled' => (bool) env('AI_SCHOOL_ENABLED', false),

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'timeout_seconds' => (int) env('GROQ_TIMEOUT_SECONDS', 30),
    ],
];
