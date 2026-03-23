<?php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Jobs\ProcessList;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ProcessListTest
 *
 * Feature tests for ProcessList::handle().
 *
 * APPROACH
 * ────────
 * Bus::fake() intercepts Bus::batch() so no real jobs are dispatched, but it
 * also intercepts dispatch_sync() — meaning the ProcessList job itself would
 * never execute if we used dispatch_sync(). Instead we call handle() directly:
 *
 *   (new ProcessList($list))->handle(app(ProcessingGate::class))
 *
 * This runs the job synchronously and in-process while Bus::fake() still
 * captures any Bus::batch() calls made inside it.
 *
 * Cache::lock() is exercised against the real cache driver (array driver in
 * the test environment) so lock behaviour is genuine, not stubbed.
 *
 * TEST GROUPS
 * ───────────
 *   1. Happy path — batch dispatched, lock acquired and released
 *   2. Duplicate prevention — second concurrent job skipped via lock
 *   3. Empty / no sources — job exits cleanly without dispatching a batch
 */

uses(RefreshDatabase::class);

// =============================================================================
// Helpers
// =============================================================================

/**
 * Build a list with one YouTube source attached via list_sources.
 */
function makeListWithYoutubeSource(User $user): ListModel
{
    $list    = ListModel::factory()->forUser($user)->create();
    $channel = YoutubeChannel::factory()->forUser($user)->create();

    DB::table('list_sources')->insert([
        'list_id'         => $list->id,
        'sourceable_id'   => $channel->id,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return $list;
}

function runProcessList(ListModel $list): void
{
    (new ProcessList($list))->handle(app(ProcessingGate::class));
}

// =============================================================================
// GROUP 1: Happy path
// =============================================================================

test('dispatches a batch when the list has enabled sources', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    runProcessList($list);

    Bus::assertBatched(fn ($batch) => count($batch->jobs) === 1);
});

test('lock is released after successful dispatch', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    runProcessList($list);

    // If the lock was properly released, we can acquire it immediately after.
    $lock = Cache::lock('process-list-' . $list->id, 600);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

test('lock is released even when an exception is thrown inside process()', function () {
    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    // Bind a ProcessingGate that throws, simulating an unexpected failure
    // inside the try block so we can verify the finally clause releases the lock.
    $gate = Mockery::mock(ProcessingGate::class);
    $gate->shouldReceive('subsystemForSourceType')->andThrow(new \RuntimeException('simulated failure'));

    try {
        (new ProcessList($list))->handle($gate);
    } catch (\RuntimeException) {
        // expected — we only care that the lock was released
    }

    $lock = Cache::lock('process-list-' . $list->id, 600);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

// =============================================================================
// GROUP 2: Duplicate prevention
// =============================================================================

test('second job is skipped when lock is already held', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    // Simulate a concurrent run by holding the lock before the job starts.
    $heldLock = Cache::lock('process-list-' . $list->id, 600);
    $heldLock->get();

    runProcessList($list);

    Bus::assertNothingBatched();

    $heldLock->release();
});

test('second job runs normally after first lock is released', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    // First run — acquires and releases the lock.
    runProcessList($list);

    // Second run — lock is free, should dispatch again.
    runProcessList($list);

    Bus::assertBatchCount(2);
});

// =============================================================================
// GROUP 3: Empty / no sources
// =============================================================================

test('does not dispatch a batch when the list has no sources', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create();

    runProcessList($list);

    Bus::assertNothingBatched();
});

test('does not dispatch a batch when all sources are disabled', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    DB::table('list_sources')->where('list_id', $list->id)->update(['enabled' => false]);

    runProcessList($list);

    Bus::assertNothingBatched();
});

test('does not dispatch a batch when all sources are suspended', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = makeListWithYoutubeSource($user);

    DB::table('list_sources')->where('list_id', $list->id)->update(['suspended' => true]);

    runProcessList($list);

    Bus::assertNothingBatched();
});

test('lock is released when the list has no sources', function () {
    Bus::fake();

    $user = User::factory()->create();
    $list = ListModel::factory()->forUser($user)->create();

    runProcessList($list);

    $lock = Cache::lock('process-list-' . $list->id, 600);
    expect($lock->get())->toBeTrue();
    $lock->release();
});