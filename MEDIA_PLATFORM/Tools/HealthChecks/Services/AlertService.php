<?php

namespace MediaPlatform\Tools\HealthChecks\Services;

use MediaPlatform\Tools\HealthChecks\Models\AdminAlert;
use MediaPlatform\Tools\HealthChecks\Notifications\AdminAlertNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Send email notifications for any unnotified Tier 2/3 alerts.
     */
    public function sendPendingNotifications(): void
    {
        $alerts = AdminAlert::needsNotification()->get();

        if ($alerts->isEmpty()) {
            return;
        }

        $adminEmail = config('admin.admin_email');

        if (! $adminEmail) {
            Log::warning('AlertService: ADMIN_EMAIL not configured. Cannot send alert notifications.');
            return;
        }

        $admin = User::where('email', $adminEmail)->first();

        if (! $admin) {
            Log::warning("AlertService: Admin user with email {$adminEmail} not found.");
            return;
        }

        foreach ($alerts as $alert) {
            try {
                $admin->notify(new AdminAlertNotification($alert));
                $alert->markNotified();
                Log::info("AlertService: Notification sent for alert '{$alert->title}' (Tier {$alert->tier}).");
            } catch (\Throwable $e) {
                Log::error("AlertService: Failed to send notification for alert {$alert->id}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Auto-resolve all unresolved Tier 1 and Tier 2 alerts for a given category.
     */
    public function autoResolveFor(string $category): void
    {
        $alerts = AdminAlert::where('category', $category)
            ->where('is_resolved', false)
            ->whereIn('tier', [1, 2])
            ->get();

        foreach ($alerts as $alert) {
            $alert->autoResolve();
            Log::info("AlertService: Auto-resolved alert '{$alert->title}' (Tier {$alert->tier}, category: {$category}).");
        }
    }
}
