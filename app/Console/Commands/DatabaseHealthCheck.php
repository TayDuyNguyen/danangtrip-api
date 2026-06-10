<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthCheck extends Command
{
    protected $signature = 'db:health-check
        {--connections=pgsql,pgsql_backup : Comma-separated database connections to test}
        {--samples=3 : Number of lightweight queries per connection}';

    protected $description = 'Check configured database connections and report simple latency metrics.';

    public function handle(): int
    {
        $connections = collect(explode(',', (string) $this->option('connections')))
            ->map(fn (string $connection) => trim($connection))
            ->filter()
            ->values();

        $samples = max(1, (int) $this->option('samples'));
        $rows = [];
        $hasFailure = false;

        foreach ($connections as $connection) {
            $latencies = [];
            $database = null;
            $serverTime = null;
            $status = 'ok';
            $error = null;

            try {
                DB::purge($connection);

                for ($i = 0; $i < $samples; $i++) {
                    $startedAt = microtime(true);
                    $result = DB::connection($connection)->selectOne(
                        'select current_database() as database_name, now() as server_time'
                    );
                    $latencies[] = (microtime(true) - $startedAt) * 1000;

                    $database = $result->database_name ?? $database;
                    $serverTime = $result->server_time ?? $serverTime;
                }
            } catch (Throwable $throwable) {
                $status = 'failed';
                $error = $throwable->getMessage();
                $hasFailure = true;
            }

            $rows[] = [
                'connection' => $connection,
                'status' => $status,
                'database' => $database ?? '-',
                'avg_ms' => $latencies ? round(array_sum($latencies) / count($latencies), 2) : '-',
                'max_ms' => $latencies ? round(max($latencies), 2) : '-',
                'server_time' => $serverTime ?? '-',
                'error' => $error ? str($error)->limit(90)->toString() : '-',
            ];
        }

        $this->table(
            ['connection', 'status', 'database', 'avg_ms', 'max_ms', 'server_time', 'error'],
            $rows
        );

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
