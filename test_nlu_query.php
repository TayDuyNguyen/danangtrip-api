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

echo "1. Running NLU:\n";
$understanding = $queryUnderstanding->understand($question, $locale);
// Simulate hybrid NLU trigger if low confidence
$aiExtracted = app(App\Services\Chat\ChatAiProviderService::class)->extractEntitiesWithAi($question, $locale, $understanding, $understanding['intent'] ?? 'location');
if ($aiExtracted) {
    $understanding = array_merge($understanding, $aiExtracted);
}

echo "Understanding:\n";
print_r($understanding);

echo "\n2. Running Search:\n";
$knowledge = $knowledgeSearch->search(
    $question,
    $understanding['intent'] ?? 'location',
    8,
    $understanding
);

echo "SQL Location count: " . $knowledge['sql_results']['locations']->count() . "\n";
foreach ($knowledge['sql_results']['locations'] as $loc) {
    echo " - ID: {$loc->id} | Name: {$loc->name} | Address: {$loc->address}\n";
}

echo "\n3. Recommendations:\n";
$recommendations = $recommendationBuilder->build(
    $knowledge['sql_results'],
    $knowledge['vector_results'],
    $understanding,
    5
);

foreach ($recommendations as $rec) {
    echo " - Type: {$rec['type']} | Name: " . ($rec['data']['name'] ?? $rec['data']['title']) . "\n";
}

echo "\n4. Aligned Context Sent to AI:\n";
$alignedContext = $knowledgeSearch->buildAlignedContext(
    $recommendations,
    $knowledge['sql_results'],
    $knowledge['vector_results'],
    $understanding['intent'] ?? 'location',
    12
);
echo json_encode($alignedContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
