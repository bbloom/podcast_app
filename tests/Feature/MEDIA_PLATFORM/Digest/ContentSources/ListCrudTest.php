<?php

// tests/Feature/MEDIA_PLATFORM/Digest/ContentSources/ListCrudTest.php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources;

use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'My List',
            'description'           => null,
            'timezone'              => 'America/Toronto',
            'enabled'               => '1',
            'schedule_frequency'    => 'daily',
            'schedule_day'          => null,
            'schedule_time'         => '08:00',
            'output_type'           => 'email',
            'output_destination_id' => null,
            'notify_by_email'       => '0',
        ], $overrides);
    }

    // =========================================================================
    // GROUP 1: index
    // =========================================================================

    #[Test]
    public function index_renders_for_authenticated_user(): void
    {
        ListModel::factory()->forUser($this->user)->count(3)->create();

        $this->get(route('lists.index'))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.lists.index')
             ->assertViewHas('lists');
    }

    #[Test]
    public function index_only_shows_the_current_users_lists(): void
    {
        $otherUser = User::factory()->create();
        ListModel::factory()->forUser($this->user)->create(['name' => 'My List']);
        ListModel::factory()->forUser($otherUser)->create(['name' => 'Their List']);

        $lists = $this->get(route('lists.index'))->viewData('lists');

        $this->assertCount(1, $lists);
        $this->assertSame('My List', $lists->first()->name);
    }

    // =========================================================================
    // GROUP 2: edit
    // =========================================================================

    #[Test]
    public function edit_renders_for_owner(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->get(route('lists.edit', $list))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.lists.edit')
             ->assertViewHas('list')
             ->assertViewHas('destinations');
    }

    #[Test]
    public function edit_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->create();

        $this->get(route('lists.edit', $list))->assertForbidden();
    }

    // =========================================================================
    // GROUP 2b: show
    // =========================================================================

    #[Test]
    public function show_renders_for_owner(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->get(route('lists.show', $list))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.lists.show')
             ->assertViewHas('list')
             ->assertViewHas('sources')
             ->assertViewHas('tracking');
    }

    #[Test]
    public function show_renders_static_site_sections_for_static_site_list(): void
    {
        $list = ListModel::factory()->forUser($this->user)->staticSite()->create();

        $this->get(route('lists.show', $list))
             ->assertOk()
             ->assertViewHas('deployHooks')
             ->assertViewHas('publishedDigests');
    }

    #[Test]
    public function show_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->create();

        $this->get(route('lists.show', $list))->assertForbidden();
    }

    #[Test]
    public function show_returns_404_for_missing_record(): void
    {
        $this->get(route('lists.show', 99999))->assertNotFound();
    }

    // =========================================================================
    // GROUP 3: update — happy paths
    // =========================================================================

    #[Test]
    public function update_saves_email_list_correctly(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'name'        => 'Updated Name',
            'output_type' => 'email',
        ]))->assertRedirect(route('lists.index'))
           ->assertSessionHas('success');

        $list->refresh();
        $this->assertSame('Updated Name', $list->name);
        $this->assertSame(OutputType::Email, $list->output_type);
        $this->assertNull($list->output_destination_id);
        $this->assertFalse($list->notify_by_email);
    }

    #[Test]
    public function update_saves_webpage_list_with_destination(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'           => 'webpage',
            'output_destination_id' => $dest->id,
            'notify_by_email'       => '1',
        ]))->assertRedirect(route('lists.index'));

        $list->refresh();
        $this->assertSame(OutputType::Webpage, $list->output_type);
        $this->assertSame($dest->id, $list->output_destination_id);
        $this->assertTrue($list->notify_by_email);
    }

    #[Test]
    public function update_saves_static_site_list_correctly(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'     => 'static_site',
            'notify_by_email' => '1',
            'retention_count' => 15,
        ]))->assertRedirect(route('lists.index'))
           ->assertSessionHas('success');

        $list->refresh();
        $this->assertSame(OutputType::StaticSite, $list->output_type);
        $this->assertNull($list->output_destination_id);
        $this->assertTrue($list->notify_by_email);
        $this->assertSame(15, $list->retention_count);
    }

    #[Test]
    public function update_clears_destination_and_notify_by_email_when_switching_to_email(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->webpage($dest->id)->create([
            'notify_by_email' => true,
        ]);

        $this->put(route('lists.update', $list), $this->basePayload(['output_type' => 'email']));

        $list->refresh();
        $this->assertNull($list->output_destination_id);
        $this->assertFalse($list->notify_by_email);
    }

    #[Test]
    public function update_clears_destination_when_switching_to_static_site(): void
    {
        $dest = OutputDestination::factory()->forUser($this->user)->create();
        $list = ListModel::factory()->forUser($this->user)->webpage($dest->id)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'     => 'static_site',
            'notify_by_email' => '0',
            'retention_count' => 5,
        ]));

        $list->refresh();
        $this->assertSame(OutputType::StaticSite, $list->output_type);
        $this->assertNull($list->output_destination_id);
        $this->assertSame(5, $list->retention_count);
    }

    // =========================================================================
    // GROUP 4: update — validation
    // =========================================================================

    #[Test]
    public function update_rejects_missing_name(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload(['name' => '']))
             ->assertSessionHasErrors('name');
    }

    #[Test]
    public function update_accepts_static_site_as_valid_output_type(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload(['output_type' => 'static_site']))
             ->assertSessionDoesntHaveErrors('output_type');
    }

    #[Test]
    public function update_rejects_invalid_schedule_frequency(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload(['schedule_frequency' => 'hourly']))
             ->assertSessionHasErrors('schedule_frequency');
    }

    #[Test]
    public function update_rejects_invalid_schedule_time_format(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload(['schedule_time' => '8am']))
             ->assertSessionHasErrors('schedule_time');
    }

    #[Test]
    public function update_returns_403_when_destination_belongs_to_another_user(): void
    {
        $otherUser = User::factory()->create();
        $dest      = OutputDestination::factory()->forUser($otherUser)->create();
        $list      = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'           => 'webpage',
            'output_destination_id' => $dest->id,
        ]))->assertForbidden();
    }

    #[Test]
    public function update_rejects_retention_count_below_1(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'     => 'static_site',
            'retention_count' => 0,
        ]))->assertSessionHasErrors('retention_count');
    }

    #[Test]
    public function update_rejects_retention_count_above_100(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->put(route('lists.update', $list), $this->basePayload([
            'output_type'     => 'static_site',
            'retention_count' => 101,
        ]))->assertSessionHasErrors('retention_count');
    }

    // =========================================================================
    // GROUP 5: update — ownership
    // =========================================================================

    #[Test]
    public function update_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->create();

        $this->put(route('lists.update', $list), $this->basePayload())->assertForbidden();
    }

    // =========================================================================
    // GROUP 6: confirmDelete
    // =========================================================================

    #[Test]
    public function confirmDelete_renders_for_owner(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->get(route('lists.delete.confirm', $list))
             ->assertOk()
             ->assertViewIs('media_platform.digest.content_sources.lists.delete-confirm')
             ->assertViewHas('list');
    }

    #[Test]
    public function confirmDelete_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->create();

        $this->get(route('lists.delete.confirm', $list))->assertForbidden();
    }

    // =========================================================================
    // GROUP 7: destroy
    // =========================================================================

    #[Test]
    public function destroy_deletes_list_and_redirects(): void
    {
        $list = ListModel::factory()->forUser($this->user)->create();

        $this->delete(route('lists.destroy', $list))
             ->assertRedirect(route('lists.index'))
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
    }

    #[Test]
    public function destroy_returns_403_for_non_owner(): void
    {
        $otherUser = User::factory()->create();
        $list      = ListModel::factory()->forUser($otherUser)->create();

        $this->delete(route('lists.destroy', $list))->assertForbidden();
    }
}