<?php

// tests/Feature/MEDIA_PLATFORM/Digest/Processing/ProcessListTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\Processing;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Youtube\Models\YoutubeChannel;
use MediaPlatform\Digest\Processing\Jobs\ProcessList;
use MediaPlatform\Digest\Processing\Services\ProcessingGate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessListTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeListWithYoutubeSource(User $user): ListModel
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

    private function runProcessList(ListModel $list): void
    {
        (new ProcessList($list))->handle(app(ProcessingGate::class));
    }

    // =========================================================================
    // GROUP 1: Happy path
    // =========================================================================

    #[Test]
    public function dispatches_a_batch_when_the_list_has_enabled_sources(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        $this->runProcessList($list);

        Bus::assertBatched(fn ($batch) => count($batch->jobs) === 1);
    }

    #[Test]
    public function lock_is_released_after_successful_dispatch(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        $this->runProcessList($list);

        $lock = Cache::lock('process-list-' . $list->id, 600);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function lock_is_released_even_when_an_exception_is_thrown_inside_process(): void
    {
        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        $gate = Mockery::mock(ProcessingGate::class);
        $gate->shouldReceive('subsystemForSourceType')->andThrow(new \RuntimeException('simulated failure'));

        try {
            (new ProcessList($list))->handle($gate);
        } catch (\RuntimeException) {
            // expected
        }

        $lock = Cache::lock('process-list-' . $list->id, 600);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    // =========================================================================
    // GROUP 2: Duplicate prevention
    // =========================================================================

    #[Test]
    public function second_job_is_skipped_when_lock_is_already_held(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        $heldLock = Cache::lock('process-list-' . $list->id, 600);
        $heldLock->get();

        $this->runProcessList($list);

        Bus::assertNothingBatched();

        $heldLock->release();
    }

    #[Test]
    public function second_job_runs_normally_after_first_lock_is_released(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        $this->runProcessList($list);
        $this->runProcessList($list);

        Bus::assertBatchCount(2);
    }

    // =========================================================================
    // GROUP 3: Empty / no sources
    // =========================================================================

    #[Test]
    public function does_not_dispatch_a_batch_when_the_list_has_no_sources(): void
    {
        Bus::fake();

        $list = ListModel::factory()->forUser(User::factory()->create())->create();

        $this->runProcessList($list);

        Bus::assertNothingBatched();
    }

    #[Test]
    public function does_not_dispatch_a_batch_when_all_sources_are_disabled(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        DB::table('list_sources')->where('list_id', $list->id)->update(['enabled' => false]);

        $this->runProcessList($list);

        Bus::assertNothingBatched();
    }

    #[Test]
    public function does_not_dispatch_a_batch_when_all_sources_are_suspended(): void
    {
        Bus::fake();

        $list = $this->makeListWithYoutubeSource(User::factory()->create());

        DB::table('list_sources')->where('list_id', $list->id)->update(['suspended' => true]);

        $this->runProcessList($list);

        Bus::assertNothingBatched();
    }

    #[Test]
    public function lock_is_released_when_the_list_has_no_sources(): void
    {
        Bus::fake();

        $list = ListModel::factory()->forUser(User::factory()->create())->create();

        $this->runProcessList($list);

        $lock = Cache::lock('process-list-' . $list->id, 600);
        $this->assertTrue($lock->get());
        $lock->release();
    }
}