<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = explode(',', env('GEMINI_API_KEYS'))[0];
$model = env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash');
$baseUrl = rtrim(env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/');

$prompt = "Gợi ý cho tôi 3 địa điểm du lịch nổi tiếng nhất ở Đà Nẵng và giải thích lý do tại sao nên đi ngắn gọn.";

foreach ([50, 200, 1000] as $maxTokens) {
    echo "Testing with maxOutputTokens = $maxTokens:\n";
    $response = Illuminate\Support\Facades\Http::post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => $maxTokens,
        ],
    ]);

    if ($response->successful()) {
        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $finishReason = data_get($response->json(), 'candidates.0.finishReason', 'N/A');
        echo "Finish Reason: $finishReason\n";
        echo "Response: " . trim(str_replace("\n", " ", $text)) . "\n";
        echo "Total tokens: " . data_get($response->json(), 'usageMetadata.totalTokenCount', 0) . "\n";
    } else {
        echo "Failed: Status " . $response->status() . " - " . $response->body() . "\n";
    }
    echo "--------------------------------------------------\n";
    sleep(2); // avoid rate limit
}
