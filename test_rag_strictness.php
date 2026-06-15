<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$chatService = app(App\Services\Chat\ChatService::class);
$queryUnderstanding = app(App\Services\Chat\ChatQueryUnderstandingService::class);
$knowledgeSearch = app(App\Services\Chat\ChatKnowledgeSearchService::class);
$recommendationBuilder = app(App\Services\Chat\ChatRecommendationBuilderService::class);

$question = "quán cà phê view biển đẹp ở Đà Nẵng";
$locale = "vi";

// Step 1: NLU
$understanding = $queryUnderstanding->understand($question, $locale);

// Step 2: Search
$knowledge = $knowledgeSearch->search($question, $understanding['intent'] ?? 'location', 8, $understanding);

// Step 3: Recommendations
$recommendations = $recommendationBuilder->build($knowledge['sql_results'], $knowledge['vector_results'], $understanding, 5);

// Step 4: Aligned Context
$alignedContext = $knowledgeSearch->buildAlignedContext(
    $recommendations,
    $knowledge['sql_results'],
    $knowledge['vector_results'],
    $understanding['intent'] ?? 'location',
    12
);

// Step 5: AI completion
// We need to call the private method buildAiMessages or mock it.
// Let's use Reflection to call the private method buildAiMessages on $chatService!
$reflector = new ReflectionClass($chatService);
$method = $reflector->getMethod('buildAiMessages');
$method->setAccessible(true);
$messages = $method->invoke($chatService, $question, $locale, $understanding['intent'] ?? 'location', $alignedContext, $understanding);

echo "--- SYSTEM ---\n";
echo $messages[0]['content'] . "\n";


echo "\nCalling AI Completion...\n";
$aiProvider = app(App\Services\Chat\ChatAiProviderService::class);
$ai = $aiProvider->complete($messages);

echo "\nAI RESPONSE STATUS: " . ($ai['ok'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Provider: " . ($ai['provider'] ?? 'N/A') . " | Model: " . ($ai['model'] ?? 'N/A') . "\n";
echo "Answer:\n";
echo "==================================================\n";
echo $ai['text'] . "\n";
echo "==================================================\n";
