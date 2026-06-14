<?php

$csv = static fn (string $key): array => array_values(array_filter(
    array_map('trim', explode(',', (string) env($key, ''))),
    static fn (string $value): bool => $value !== ''
));

return [
    'enabled' => (bool) env('CHATBOT_ENABLED', true),
    'provider_order' => $csv('CHATBOT_PROVIDER_ORDER') ?: ['gemini', 'groq', 'openrouter'],
    'cache_driver' => env('CHATBOT_CACHE_DRIVER', 'database'),
    'cache_ttl_seconds' => (int) env('CHATBOT_CACHE_TTL_SECONDS', 86400),
    'vector_enabled' => (bool) env('CHATBOT_VECTOR_ENABLED', false),
    'vector_min_similarity' => (float) env('CHATBOT_VECTOR_MIN_SIMILARITY', 0.68),
    'vector_candidate_limit' => (int) env('CHATBOT_VECTOR_CANDIDATE_LIMIT', 80),
    'vector_context_limit' => (int) env('CHATBOT_VECTOR_CONTEXT_LIMIT', 5),
    'max_context_items' => (int) env('CHATBOT_MAX_CONTEXT_ITEMS', 5),
    'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 2000),
    'temperature' => (float) env('CHATBOT_TEMPERATURE', 0.3),
    'timeout_seconds' => (int) env('CHATBOT_TIMEOUT_SECONDS', 25),
    'max_retries' => (int) env('CHATBOT_MAX_RETRIES', 8),
    'nlu' => [
        'confidence_threshold' => (float) env('CHATBOT_NLU_CONFIDENCE_THRESHOLD', 0.8),
        'weights' => [
            'destination' => 35,
            'price' => 25,
            'people' => 20,
            'date' => 20,
        ],
    ],
    'failover_status_codes' => array_map('intval', $csv('AI_FAILOVER_STATUS_CODES') ?: [429, 500, 502, 503, 504]),
    'key_cooldown_seconds' => (int) env('AI_KEY_COOLDOWN_SECONDS', 3600),
    'limits' => [
        'guest_daily' => (int) env('CHATBOT_GUEST_DAILY_LIMIT', 10),
        'user_daily' => (int) env('CHATBOT_USER_DAILY_LIMIT', 30),
        'admin_daily' => (int) env('CHATBOT_ADMIN_DAILY_LIMIT', 100),
    ],
    'embedding' => [
        'provider_order' => $csv('CHATBOT_EMBEDDING_PROVIDER_ORDER') ?: ['gemini', 'openai'],
        'max_input_chars' => (int) env('CHATBOT_EMBEDDING_MAX_INPUT_CHARS', 6000),
        'timeout_seconds' => (int) env('CHATBOT_EMBEDDING_TIMEOUT_SECONDS', 30),
        'gemini_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'gemini_output_dimensionality' => (int) env('GEMINI_EMBEDDING_OUTPUT_DIMENSIONALITY', 768),
        'openai_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],
    'providers' => [
        'gemini' => [
            'keys' => $csv('GEMINI_API_KEYS'),
            'model' => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
        'groq' => [
            'keys' => $csv('GROQ_API_KEYS'),
            'model' => env('GROQ_CHAT_MODEL', 'llama-3.3-70b-versatile'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],
        'openrouter' => [
            'keys' => $csv('OPENROUTER_API_KEYS'),
            'model' => env('OPENROUTER_CHAT_MODEL', 'openrouter/free'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'site_url' => env('OPENROUTER_SITE_URL'),
            'app_name' => env('OPENROUTER_APP_NAME', 'DanangTrip'),
        ],
        'openai' => [
            'keys' => $csv('OPENAI_API_KEYS'),
            'model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
    ],
];
