<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/DispatchDueListsTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Jobs\DispatchDueLists;
use MediaPlatform\Digest\Processing\Jobs\ProcessList;
use MediaPlatform\Digest\Enums\OutputType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

/**
 * DispatchDueListsTest
 *
 * Tests DispatchDueLists::handle() — the scheduler entry point that decides
 * which lists are due and dispatches ProcessList for each one.
 *
 * APPROACH
 * ────────
 * We call (new DispatchDueLists())->handle() directly, with Carbon::setTestNow()
 * to control the current time. Bus::fake() intercepts ProcessList dispatches.
 *
 * TEST GROUPS
 * ───────────
 *   1.  Only enabled lists are considered
 *   2.  Daily schedule
 *   3.  Weekly schedule
 *   4.  Monthly schedule — including month-end edge cases
 *   5.  5-minute dispatch window
 *   6.  Timezone handling
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

/**
 * Create an enabled list with the given schedule fields, owned by a new user.
 */
function makeScheduledList(array $schedule): ListModel
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

function runDispatchDueLists(): void
{
    (new DispatchDueLists())->handle();
}

// =============================================================================
// GROUP 1: Enabled / disabled
// =============================================================================

it('dispatches ProcessList for an enabled list that is due', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'daily',
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('does not dispatch for a disabled list', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList([
        'enabled'            => false,
        'schedule_frequency' => 'daily',
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('dispatches only enabled lists when both enabled and disabled exist', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList(['enabled' => true,  'schedule_time' => '08:00', 'timezone' => 'UTC']);
    makeScheduledList(['enabled' => false, 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertDispatchedTimes(ProcessList::class, 1);
});

// =============================================================================
// GROUP 2: Daily schedule
// =============================================================================

it('dispatches a daily list within the 5-minute window', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:03:00 UTC');

    makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('does not dispatch a daily list before the window', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 07:59:00 UTC');

    makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('does not dispatch a daily list after the window has passed', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:06:00 UTC');

    makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('dispatches a daily list exactly at the scheduled time', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:00:00 UTC');

    makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('dispatches a daily list exactly at the end of the 5-minute window', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:04:59 UTC');

    makeScheduledList(['schedule_frequency' => 'daily', 'schedule_time' => '08:00', 'timezone' => 'UTC']);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

// =============================================================================
// GROUP 3: Weekly schedule
// =============================================================================

it('dispatches a weekly list on the correct day of the week', function () {
    Bus::fake();

    // 2026-03-25 is a Wednesday — ISO day 3.
    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'weekly',
        'schedule_day'       => 3, // Wednesday
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('does not dispatch a weekly list on the wrong day of the week', function () {
    Bus::fake();

    // 2026-03-25 is a Wednesday — ISO day 3.
    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'weekly',
        'schedule_day'       => 5, // Friday
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

// =============================================================================
// GROUP 4: Monthly schedule
// =============================================================================

it('dispatches a monthly list on the correct day of the month', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-15 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'monthly',
        'schedule_day'       => 15,
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('does not dispatch a monthly list on the wrong day of the month', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-10 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'monthly',
        'schedule_day'       => 15,
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('dispatches a monthly list scheduled for day 31 on the last day of a 30-day month', function () {
    Bus::fake();

    // April has 30 days — day 31 should clamp to day 30.
    Carbon::setTestNow('2026-04-30 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'monthly',
        'schedule_day'       => 31,
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('does not dispatch a monthly list scheduled for day 31 mid-month in a short month', function () {
    Bus::fake();

    // April 15 — day 31 is clamped to 30, so this should not fire.
    Carbon::setTestNow('2026-04-15 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'monthly',
        'schedule_day'       => 31,
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('dispatches a monthly list scheduled for day 29 on the last day of February in a non-leap year', function () {
    Bus::fake();

    // 2026 is not a leap year — February has 28 days. Day 29 clamps to 28.
    Carbon::setTestNow('2026-02-28 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'monthly',
        'schedule_day'       => 29,
        'schedule_time'      => '08:00',
        'timezone'           => 'UTC',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

// =============================================================================
// GROUP 5: 5-minute dispatch window
// =============================================================================

it('dispatches multiple lists with different times only if they are each due', function () {
    Bus::fake();

    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList(['schedule_time' => '08:00', 'timezone' => 'UTC']); // due
    makeScheduledList(['schedule_time' => '09:00', 'timezone' => 'UTC']); // not due
    makeScheduledList(['schedule_time' => '07:00', 'timezone' => 'UTC']); // not due

    runDispatchDueLists();

    Bus::assertDispatchedTimes(ProcessList::class, 1);
});

// =============================================================================
// GROUP 6: Timezone handling
// =============================================================================

it('evaluates schedule in the list timezone, not UTC', function () {
    Bus::fake();

    // UTC is 08:00. Asia/Tokyo (JST = UTC+9, no DST) is 17:00 — NOT 08:00.
    // A list scheduled for 08:00 Tokyo time should NOT be dispatched now.
    Carbon::setTestNow('2026-03-25 08:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'daily',
        'schedule_time'      => '08:00',
        'timezone'           => 'Asia/Tokyo',
    ]);

    runDispatchDueLists();

    Bus::assertNotDispatched(ProcessList::class);
});

it('dispatches when current UTC time matches schedule_time in the list timezone', function () {
    Bus::fake();

    // Asia/Tokyo is JST (UTC+9), no DST — offset is constant year-round.
    // To fire at 08:00 Tokyo time, UTC must be 23:00 the previous day.
    Carbon::setTestNow('2026-03-24 23:02:00 UTC');

    makeScheduledList([
        'schedule_frequency' => 'daily',
        'schedule_time'      => '08:00',
        'timezone'           => 'Asia/Tokyo',
    ]);

    runDispatchDueLists();

    Bus::assertDispatched(ProcessList::class);
});

it('dispatches multiple lists each in their own timezone when both are due', function () {
    Bus::fake();

    // UTC 23:02 on 2026-03-24.
    // UTC list due at 23:00 ✓. Tokyo (UTC+9) list due at 08:00 the next day ✓.
    Carbon::setTestNow('2026-03-24 23:02:00 UTC');

    makeScheduledList(['schedule_time' => '23:00', 'timezone' => 'UTC']);
    makeScheduledList(['schedule_time' => '08:00', 'timezone' => 'Asia/Tokyo']);

    runDispatchDueLists();

    Bus::assertDispatchedTimes(ProcessList::class, 2);
});