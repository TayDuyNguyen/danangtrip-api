<?php

namespace App\Console\Commands;

use App\Services\Chat\ChatService;
use App\Services\Chat\ChatSessionMemoryService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Throwable;

class TestChatBot extends Command
{
    protected $signature = 'chat:test {message} {--session=cli_test_session} {--clear : Clear session cache first}';

    protected $description = 'Run a message directly through ChatService and display full pipeline trace & results in terminal.';

    public function handle(ChatService $chatService, ChatSessionMemoryService $sessionMemory): int
    {
        $message = (string) $this->argument('message');
        $sessionId = (string) $this->option('session');
        $clear = (bool) $this->option('clear');

        if ($clear) {
            $sessionMemory->clearSession($sessionId);
            $this->info("Cleared session memory for ID: \"$sessionId\"");
        }

        $this->info("Sending message to chatbot: \"$message\"");
        $this->comment("Session ID: $sessionId");
        $this->line(str_repeat('─', 80));

        // Create a mock Request object to satisfy ChatService::send
        $request = Request::create('/api/chat', 'POST', [
            'message' => $message,
            'session_id' => $sessionId,
        ]);

        try {
            $response = $chatService->send([
                'message' => $message,
                'session_id' => $sessionId,
                'locale' => 'vi',
            ], $request);

            $this->info('Response received successfully!');
            $this->line(str_repeat('─', 80));

            $payload = $response['data'] ?? [];

            // Print intent and basic stats
            $intent = $payload['intent'] ?? 'unknown';
            $this->comment('Final classified intent: '.strtoupper($intent));
            $this->comment('Is in scope: '.(($payload['is_in_scope'] ?? true) ? 'YES' : 'NO'));

            if (isset($payload['clarification_step']) && $payload['clarification_step'] !== null) {
                $this->warn('⚠️  CLARIFICATION NEEDED: Bot requested clarification for: '.strtoupper($payload['clarification_step']));
            }

            $this->line('');
            $this->info('🤖 BOT ANSWER:');
            $this->line($payload['answer'] ?? '(No answer returned)');
            $this->line('');

            // Recommendations
            $recommendations = $payload['recommendations'] ?? [];
            $this->info('📦 RECOMMENDATIONS CARD DATA ('.count($recommendations).' items):');
            if (empty($recommendations)) {
                $this->line('   (None)');
            } else {
                foreach ($recommendations as $i => $rec) {
                    $type = $rec['type'] ?? 'unknown';
                    $name = $rec['data']['name'] ?? $rec['data']['title'] ?? '(No name)';
                    $id = $rec['data']['id'] ?? '-';
                    $this->line(sprintf('   [%d] Type: %-10s | ID: %-4s | Name: %s', $i + 1, strtoupper($type), $id, $name));
                }
            }

            // Map and UI state
            if (isset($payload['center'])) {
                $this->comment(sprintf('🗺️  Map Coordinates: Center [%.4f, %.4f], Zoom %d',
                    $payload['center'][0] ?? 0.0,
                    $payload['center'][1] ?? 0.0,
                    $payload['zoom'] ?? 12
                ));
            }

            // Debug metadata
            if (isset($payload['ai_metadata'])) {
                $this->comment('🧠 AI Metadata: Provider='.($payload['ai_metadata']['provider'] ?? 'N/A').' | Model='.($payload['ai_metadata']['model'] ?? 'N/A'));
            }

        } catch (Throwable $e) {
            $this->error('❌ Error occurred during pipeline execution:');
            $this->error('Message: '.$e->getMessage());
            $this->error('File: '.$e->getFile().':'.$e->getLine());
            $this->line('');
            $this->line('Trace:');
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
