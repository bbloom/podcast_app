<?php

// tests/Feature/Processing/ContentAlreadyProcessedTest.php

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use App\Models\User;
use MediaPlatform\Digest\Processing\Models\ContentAlreadyProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->list = ListModel::factory()->forUser($this->user)->create();

    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => 1,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $this->listSourceId = DB::table('list_sources')->value('id');
});

// ============================================================================
// findBookmark
// ============================================================================

it('returns null when no bookmark exists', function () {
    expect(ContentAlreadyProcessed::findBookmark($this->listSourceId))->toBeNull();
});

it('returns the bookmark when one exists', function () {
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSourceId,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/item-1',
        'processed_at'   => now(),
    ]);

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSourceId);

    expect($bookmark)->not->toBeNull();
    expect($bookmark->source_url)->toBe('https://example.com/item-1');
});

// ============================================================================
// rotateBookmark
// ============================================================================

it('inserts a bookmark when none exists', function () {
    ContentAlreadyProcessed::rotateBookmark(
        listSourceId: $this->listSourceId,
        userId:       $this->user->id,
        sourceUrl:    'https://example.com/item-1',
    );

    expect(ContentAlreadyProcessed::where('list_source_id', $this->listSourceId)->count())->toBe(1);
    expect(ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url)
        ->toBe('https://example.com/item-1');
});

it('deletes old bookmark and inserts new one on rotation', function () {
    // Insert initial bookmark.
    ContentAlreadyProcessed::create([
        'list_source_id' => $this->listSourceId,
        'user_id'        => $this->user->id,
        'source_url'     => 'https://example.com/item-1',
        'processed_at'   => now()->subDay(),
    ]);

    // Rotate to a new bookmark.
    ContentAlreadyProcessed::rotateBookmark(
        listSourceId: $this->listSourceId,
        userId:       $this->user->id,
        sourceUrl:    'https://example.com/item-2',
    );

    // Still only one row.
    expect(ContentAlreadyProcessed::where('list_source_id', $this->listSourceId)->count())->toBe(1);

    // Points to the new URL.
    expect(ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url)
        ->toBe('https://example.com/item-2');
});

it('sets processed_at to approximately now on rotation', function () {
    $before = now()->subSecond();

    ContentAlreadyProcessed::rotateBookmark(
        listSourceId: $this->listSourceId,
        userId:       $this->user->id,
        sourceUrl:    'https://example.com/item-1',
    );

    $bookmark = ContentAlreadyProcessed::findBookmark($this->listSourceId);

    expect($bookmark->processed_at->gte($before))->toBeTrue();
});

it('bookmarks are scoped per list_source — two sources have independent bookmarks', function () {
    // Insert a second list_source.
    DB::table('list_sources')->insert([
        'list_id'         => $this->list->id,
        'sourceable_id'   => 2,
        'sourceable_type' => 'youtube_channel',
        'enabled'         => true,
        'suspended'       => false,
        'processing_mode' => 'description',
        'search_terms'    => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $listSourceId2 = DB::table('list_sources')->orderByDesc('id')->value('id');

    ContentAlreadyProcessed::rotateBookmark($this->listSourceId,  $this->user->id, 'https://example.com/source1-item');
    ContentAlreadyProcessed::rotateBookmark($listSourceId2,        $this->user->id, 'https://example.com/source2-item');

    expect(ContentAlreadyProcessed::findBookmark($this->listSourceId)->source_url)
        ->toBe('https://example.com/source1-item');

    expect(ContentAlreadyProcessed::findBookmark($listSourceId2)->source_url)
        ->toBe('https://example.com/source2-item');

    expect(ContentAlreadyProcessed::count())->toBe(2);
});