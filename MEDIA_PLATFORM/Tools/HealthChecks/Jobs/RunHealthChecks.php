<?php

namespace MediaPlatform\Tools\HealthChecks\Jobs;

use MediaPlatform\Tools\HealthChecks\Services\HealthCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunHealthChecks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 120;

    public function handle(HealthCheckService $service): void
    {
        Log::info('RunHealthChecks: Starting health checks.');

        $results = $service->runAll();

        $passed = collect($results)->where('status', 'pass')->count();
        $failed = collect($results)->where('status', 'fail')->count();

        Log::info("RunHealthChecks: Complete. {$passed} passed, {$failed} failed.");
    }
}
