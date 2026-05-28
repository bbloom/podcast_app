<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/DispatchDueListsTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Jobs\DispatchDueLists;
use MediaPlatform\Digest\Processing\Jobs\ProcessList;
use MediaPlatform\Digest\Enums\OutputType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DispatchDueListsTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeScheduledList(array $schedule): ListModel
    {
        $user = User::factory()->create();

        return ListModel::factory()->forUser($user)->create(array_merge([
            'enabled'            => true,
            'output_type'        => OutputType::Email,
            'schedule_frequency' => 'daily',
            'schedule_day'       => null,
            'schedule_time'      => '08:00',
            'timezone'           => 'UTC',
        ], $schedule));
    }

    private function runDispatchDueLists(): void
    {
        (new DispatchDueLists())->handle();
    }

    // =========================================================================
    // GROUP 1: Enabled / disabled
    // =========================================================================

    #[Test]
    public function dispatches_ProcessList_for_an_enabled_list_that_is_due(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_for_a_disabled_list(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC');

        $this->makeScheduledList(['enabled' => false, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_only_enabled_lists_when_both_enabled_and_disabled_exist(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC');

        $this->makeScheduledList(['enabled' => true,  'schedule_time' => '08:00', 'timezone' => 'UTC']);
        $this->makeScheduledList(['enabled' => false, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatchedTimes(ProcessList::class, 1);
    }

    // =========================================================================
    // GROUP 2: Daily schedule
    // =========================================================================

    #[Test]
    public function dispatches_a_daily_list_within_the_5_minute_window(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:03:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_a_daily_list_before_the_window(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 07:59:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_a_daily_list_after_the_window_has_passed(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:06:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_a_daily_list_exactly_at_the_scheduled_time(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:00:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_a_daily_list_exactly_at_the_end_of_the_5_minute_window(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:04:59 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    // =========================================================================
    // GROUP 3: Weekly schedule
    // =========================================================================

    #[Test]
    public function dispatches_a_weekly_list_on_the_correct_day_of_the_week(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC'); // Wednesday — ISO day 3

        $this->makeScheduledList(['schedule_frequency' => 'weekly', 'schedule_day' => 3, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_a_weekly_list_on_the_wrong_day_of_the_week(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC'); // Wednesday — ISO day 3

        $this->makeScheduledList(['schedule_frequency' => 'weekly', 'schedule_day' => 5, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    // =========================================================================
    // GROUP 4: Monthly schedule
    // =========================================================================

    #[Test]
    public function dispatches_a_monthly_list_on_the_correct_day_of_the_month(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-15 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'monthly', 'schedule_day' => 15, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_a_monthly_list_on_the_wrong_day_of_the_month(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-10 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'monthly', 'schedule_day' => 15, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_a_monthly_list_scheduled_for_day_31_on_the_last_day_of_a_30_day_month(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-04-30 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'monthly', 'schedule_day' => 31, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function does_not_dispatch_a_monthly_list_scheduled_for_day_31_mid_month_in_a_short_month(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-04-15 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'monthly', 'schedule_day' => 31, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_a_monthly_list_scheduled_for_day_29_on_the_last_day_of_February_in_a_non_leap_year(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-02-28 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'monthly', 'schedule_day' => 29, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    // =========================================================================
    // GROUP 5: 5-minute dispatch window
    // =========================================================================

    #[Test]
    public function dispatches_multiple_lists_with_different_times_only_if_they_are_each_due(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC');

        $this->makeScheduledList(['schedule_time' => '08:00', 'timezone' => 'UTC']); // due
        $this->makeScheduledList(['schedule_time' => '09:00', 'timezone' => 'UTC']); // not due
        $this->makeScheduledList(['schedule_time' => '07:00', 'timezone' => 'UTC']); // not due

        $this->runDispatchDueLists();

        Bus::assertDispatchedTimes(ProcessList::class, 1);
    }

    // =========================================================================
    // GROUP 6: Timezone handling
    // =========================================================================

    #[Test]
    public function evaluates_schedule_in_the_list_timezone_not_UTC(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-25 08:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'Asia/Tokyo']);

        $this->runDispatchDueLists();

        Bus::assertNotDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_when_current_UTC_time_matches_schedule_time_in_the_list_timezone(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-24 23:02:00 UTC');

        $this->makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'Asia/Tokyo']);

        $this->runDispatchDueLists();

        Bus::assertDispatched(ProcessList::class);
    }

    #[Test]
    public function dispatches_multiple_lists_each_in_their_own_timezone_when_both_are_due(): void
    {
        Bus::fake();
        Carbon::setTestNow('2026-03-24 23:02:00 UTC');

        $this->makeScheduledList(['schedule_time' => '23:00', 'timezone' => 'UTC']);
        $this->makeScheduledList(['schedule_time' => '08:00', 'timezone' => 'Asia/Tokyo']);

        $this->runDispatchDueLists();

        Bus::assertDispatchedTimes(ProcessList::class, 2);
    }
}