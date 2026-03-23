<?php

namespace MediaPlatform\Tools\HealthChecks\Console\Commands;

use MediaPlatform\Tools\HealthChecks\Services\HealthCheckService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Run all health checks and display results';

    public function handle(HealthCheckService $service): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        $results = $service->runAll();

        foreach ($results as $result) {
            if ($result['status'] === 'pass') {
                $this->line("  <fg=green>✓</> {$result['message']}");
            } else {
                $tier = $result['tier'] ?? '?';
                $this->line("  <fg=red>✗</> [Tier {$tier}] {$result['title']}");
                $this->line("    <fg=gray>{$result['message']}</>");
            }
        }

        $this->newLine();

        $passed = collect($results)->where('status', 'pass')->count();
        $failed = collect($results)->where('status', 'fail')->count();

        if ($failed === 0) {
            $this->info("All {$passed} checks passed.");
        } else {
            $this->warn("{$passed} passed, {$failed} failed.");
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
