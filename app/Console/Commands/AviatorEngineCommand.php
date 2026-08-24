<?php

namespace App\Console\Commands;

use App\Services\AviatorRoundService;
use Illuminate\Console\Command;
use Throwable;

class AviatorEngineCommand extends Command
{
    protected $signature = 'aviator:engine {--sleep-ms=} {--heartbeat-ms=}';
    protected $description = 'Run the Aviator round engine loop';

    public function handle(AviatorRoundService $service): int
    {
        $this->info('Aviator engine started.');

        $lastHeartbeatAt = 0;

        while (true) {
            try {
                $sleepMs = $this->option('sleep-ms') !== null ? max(50, (int) $this->option('sleep-ms')) : $service->engineSleepMs();

                $heartbeatMs = $this->option('heartbeat-ms') !== null ? max(100, (int) $this->option('heartbeat-ms')) : $service->publicHeartbeatMs();

                $nowMs = (int) floor(microtime(true) * 1000);
                $forcePublicHeartbeat = ($nowMs - $lastHeartbeatAt) >= $heartbeatMs;

                if ($forcePublicHeartbeat) { 
                    $lastHeartbeatAt = $nowMs;
                }

                $service->tick($forcePublicHeartbeat);
            } catch (Throwable $e) {
                report($e);
                usleep(500000);
            }

            usleep($sleepMs * 1000);
        }

        return self::SUCCESS;
    }
}