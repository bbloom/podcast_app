<?php

namespace Tests\Feature\MEDIA_PLATFORM\Digest\ContentSources;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Services\SftpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardFlowTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);
    }

    // =========================================================================
    // List wizard — session / redirect_to behaviour
    // =========================================================================

    public function test_list_wizard_step1_stores_redirect_to_from_query_param(): void
    {
        $this->get(route('lists.create.step1', ['redirect_to' => 'youtube.channels.create.step4']))
            ->assertOk()
            ->assertSessionHas('list_wizard.redirect_to', 'youtube.channels.create.step4');
    }

    public function test_list_wizard_step1_renders_normally_without_redirect_to(): void
    {
        $this->get(route('lists.create.step1'))
            ->assertOk()
            ->assertSessionMissing('list_wizard.redirect_to');
    }

    public function test_list_wizard_step6_submit_redirects_back_to_source_wizard(): void
    {
        $this->withSession([
            'list_wizard' => [
                'name'               => 'Test List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '06:00',
                'output_type'        => 'email',
                'redirect_to'        => 'youtube.channels.create.step4',
            ],
        ])->post(route('lists.create.step6.submit'))
          ->assertRedirect(route('youtube.channels.create.step4'))
          ->assertSessionHas('success');
    }

    public function test_list_wizard_step6_submit_redirects_to_step7_without_redirect_to(): void
    {
        $this->withSession([
            'list_wizard' => [
                'name'               => 'Normal List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'weekly',
                'schedule_day'       => 1,
                'schedule_time'      => '09:00',
                'output_type'        => 'email',
            ],
        ])->post(route('lists.create.step6.submit'))
          ->assertRedirect()
          ->assertSee('', ''); // just confirm redirect happened

        // Verify it redirects to step7 (with a list query param)
        $response = $this->withSession([
            'list_wizard' => [
                'name'               => 'Normal List 2',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'weekly',
                'schedule_day'       => 1,
                'schedule_time'      => '09:00',
                'output_type'        => 'email',
            ],
        ])->post(route('lists.create.step6.submit'));

        $this->assertStringContainsString('lists/create/step7', $response->headers->get('Location'));
    }

    public function test_list_wizard_redirect_to_ignores_invalid_route_names(): void
    {
        $response = $this->withSession([
            'list_wizard' => [
                'name'               => 'Test List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '06:00',
                'output_type'        => 'email',
                'redirect_to'        => 'some.nonexistent.route',
            ],
        ])->post(route('lists.create.step6.submit'));

        $this->assertStringContainsString('lists/create/step7', $response->headers->get('Location'));
    }

    public function test_list_wizard_redirect_to_works_for_podcast_wizard(): void
    {
        $this->withSession([
            'list_wizard' => [
                'name'               => 'Podcast List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '08:00',
                'output_type'        => 'email',
                'redirect_to'        => 'digest-podcasts.create.step3',
            ],
        ])->post(route('lists.create.step6.submit'))
          ->assertRedirect(route('digest-podcasts.create.step3'))
          ->assertSessionHas('success');
    }

    public function test_list_wizard_redirect_to_works_for_text_feed_wizard(): void
    {
        $this->withSession([
            'list_wizard' => [
                'name'               => 'RSS List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '07:00',
                'output_type'        => 'email',
                'redirect_to'        => 'text_based_rss_feeds.create.step3',
            ],
        ])->post(route('lists.create.step6.submit'))
          ->assertRedirect(route('text_based_rss_feeds.create.step3'))
          ->assertSessionHas('success');
    }

    public function test_list_wizard_clears_session_after_redirect_to(): void
    {
        $response = $this->withSession([
            'list_wizard' => [
                'name'               => 'Test List',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '06:00',
                'output_type'        => 'email',
                'redirect_to'        => 'youtube.channels.create.step4',
            ],
        ])->post(route('lists.create.step6.submit'));

        $response->assertSessionMissing('list_wizard');
    }

    // =========================================================================
    // Output Destination wizard — session / redirect_to behaviour
    // =========================================================================

    public function test_od_wizard_step1_stores_redirect_to_from_query_param(): void
    {
        $this->get(route('output_destinations.create.step1', ['redirect_to' => 'lists.create.step4']))
            ->assertOk()
            ->assertSessionHas('od_wizard.redirect_to', 'lists.create.step4');
    }

    public function test_od_wizard_step1_renders_normally_without_redirect_to(): void
    {
        $this->get(route('output_destinations.create.step1'))
            ->assertOk()
            ->assertSessionMissing('od_wizard.redirect_to');
    }

    public function test_od_wizard_step8_submit_redirects_back_to_list_wizard(): void
    {
        $this->withSession([
            'od_wizard' => [
                'name'        => 'My Server',
                'type'        => 'sftp',
                'host'        => 'sftp.example.com',
                'port'        => 22,
                'username'    => 'deploy',
                'auth_type'   => 'password',
                'password'    => 'secret',
                'path'        => '/public_html',
                'base_url'    => 'https://example.com',
                'test_passed' => true,
                'redirect_to' => 'lists.create.step4',
            ],
        ])->post(route('output_destinations.create.step8.submit'))
          ->assertRedirect(route('lists.create.step4'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('output_destinations', [
            'user_id' => $this->user->id,
            'name'    => 'My Server',
            'host'    => 'sftp.example.com',
        ]);
    }

    public function test_od_wizard_step8_submit_redirects_to_step9_without_redirect_to(): void
    {
        $this->withSession([
            'od_wizard' => [
                'name'        => 'My Server',
                'type'        => 'sftp',
                'host'        => 'sftp.example.com',
                'port'        => 22,
                'username'    => 'deploy',
                'auth_type'   => 'password',
                'password'    => 'secret',
                'path'        => '/public_html',
                'base_url'    => null,
                'test_passed' => true,
            ],
        ])->post(route('output_destinations.create.step8.submit'))
          ->assertRedirect(route('output_destinations.create.step9'));
    }

    public function test_od_wizard_redirect_to_ignores_invalid_route_names(): void
    {
        $this->withSession([
            'od_wizard' => [
                'name'        => 'My Server',
                'type'        => 'sftp',
                'host'        => 'sftp.example.com',
                'port'        => 22,
                'username'    => 'deploy',
                'auth_type'   => 'password',
                'password'    => 'secret',
                'path'        => '/var/www',
                'base_url'    => null,
                'test_passed' => true,
                'redirect_to' => 'totally.fake.route',
            ],
        ])->post(route('output_destinations.create.step8.submit'))
          ->assertRedirect(route('output_destinations.create.step9'));
    }

    public function test_od_wizard_clears_session_after_redirect_to(): void
    {
        $response = $this->withSession([
            'od_wizard' => [
                'name'        => 'My Server',
                'type'        => 'sftp',
                'host'        => 'sftp.example.com',
                'port'        => 22,
                'username'    => 'deploy',
                'auth_type'   => 'password',
                'password'    => 'secret',
                'path'        => '/public_html',
                'base_url'    => null,
                'test_passed' => true,
                'redirect_to' => 'lists.create.step4',
            ],
        ])->post(route('output_destinations.create.step8.submit'));

        $response->assertSessionMissing('od_wizard');
    }

    // =========================================================================
    // Full chain: Source wizard → List wizard → back to source wizard
    // =========================================================================

    public function test_youtube_to_list_wizard_and_back_full_chain(): void
    {
        $this->get(route('lists.create.step1', ['redirect_to' => 'youtube.channels.create.step4']))
            ->assertOk()
            ->assertSessionHas('list_wizard.redirect_to', 'youtube.channels.create.step4');

        $this->post(route('lists.create.step1.submit'), [
            'name'     => 'My YouTube List',
            'timezone' => 'America/Toronto',
        ])->assertRedirect(route('lists.create.step2'));

        $this->post(route('lists.create.step2.submit'), [
            'schedule_frequency' => 'daily',
            'schedule_time'      => '08:00',
        ])->assertRedirect(route('lists.create.step3'));

        $this->post(route('lists.create.step3.submit'), [
            'output_type' => 'email',
        ])->assertRedirect(route('lists.create.step6'));

        $this->post(route('lists.create.step6.submit'))
            ->assertRedirect(route('youtube.channels.create.step4'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lists', [
            'user_id' => $this->user->id,
            'name'    => 'My YouTube List',
        ]);
    }

    // =========================================================================
    // Full chain: List wizard → OD wizard → back to List wizard
    //
    // SFTP wizard step numbering:
    //   Step 1 — Name                (shared)
    //   Step 2 — Type (sftp/wp)      (shared)
    //   Step 3 — Host & port
    //   Step 4 — Username
    //   Step 5 — Authentication
    //   Step 6 — Path & base URL
    //   Step 7 — Test connection
    //   Step 8 — Confirm & save
    //   Step 9 — Done                (shared)
    // =========================================================================

    public function test_list_wizard_to_od_wizard_and_back_full_chain(): void
    {
        $this->get(route('output_destinations.create.step1', ['redirect_to' => 'lists.create.step4']))
            ->assertOk()
            ->assertSessionHas('od_wizard.redirect_to', 'lists.create.step4');

        $this->post(route('output_destinations.create.step1.submit'), [
            'name' => 'My Web Server',
        ])->assertRedirect(route('output_destinations.create.step2'));

        $this->post(route('output_destinations.create.step2.submit'), [
            'type' => 'sftp',
        ])->assertRedirect(route('output_destinations.create.step3'));

        $this->post(route('output_destinations.create.step3.submit'), [
            'host' => 'sftp.example.com',
            'port' => 22,
        ])->assertRedirect(route('output_destinations.create.step4'));

        $this->post(route('output_destinations.create.step4.submit'), [
            'username' => 'deploy',
        ])->assertRedirect(route('output_destinations.create.step5'));

        $this->post(route('output_destinations.create.step5.submit'), [
            'auth_type' => 'password',
            'password'  => 'secret123',
        ])->assertRedirect(route('output_destinations.create.step6'));

        $this->post(route('output_destinations.create.step6.submit'), [
            'path'     => '/var/www/digests',
            'base_url' => 'https://example.com/digests',
        ])->assertRedirect(route('output_destinations.create.step7'));

        // Simulate a passing connection test via the session.
        session()->put('od_wizard.test_passed', true);

        $this->post(route('output_destinations.create.step7.submit'))
            ->assertRedirect(route('output_destinations.create.step8'));

        $this->post(route('output_destinations.create.step8.submit'))
            ->assertRedirect(route('lists.create.step4'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('output_destinations', [
            'user_id' => $this->user->id,
            'name'    => 'My Web Server',
            'host'    => 'sftp.example.com',
        ]);
    }

    // =========================================================================
    // List wizard — webpage path (steps 4 → 5 → 6) with existing destination
    // =========================================================================

    public function test_list_wizard_webpage_path_with_existing_destination(): void
    {
        $destination = OutputDestination::factory()->forUser($this->user)->create();

        $this->post(route('lists.create.step1.submit'), [
            'name'     => 'Webpage List',
            'timezone' => 'America/Toronto',
        ]);

        $this->post(route('lists.create.step2.submit'), [
            'schedule_frequency' => 'weekly',
            'schedule_day'       => 1,
            'schedule_time'      => '09:00',
        ]);

        $this->post(route('lists.create.step3.submit'), [
            'output_type' => 'webpage',
        ])->assertRedirect(route('lists.create.step4'));

        $this->post(route('lists.create.step4.submit'), [
            'output_destination_id' => $destination->id,
        ])->assertRedirect(route('lists.create.step5'));

        $this->post(route('lists.create.step5.submit'), [
            'notify_by_email' => '0',
        ])->assertRedirect(route('lists.create.step6'));

        $response = $this->post(route('lists.create.step6.submit'));
        $this->assertStringContainsString('lists/create/step7', $response->headers->get('Location'));

        $this->assertDatabaseHas('lists', [
            'user_id'     => $this->user->id,
            'name'        => 'Webpage List',
            'output_type' => 'webpage',
        ]);
    }

    // =========================================================================
    // List wizard — static site path (steps 3 → 4-static-site → 6 → 7)
    // =========================================================================

    public function test_list_wizard_static_site_path(): void
    {
        $this->post(route('lists.create.step1.submit'), [
            'name'     => 'Static Site List',
            'timezone' => 'America/Toronto',
        ]);

        $this->post(route('lists.create.step2.submit'), [
            'schedule_frequency' => 'daily',
            'schedule_time'      => '06:00',
        ]);

        $this->post(route('lists.create.step3.submit'), [
            'output_type' => 'static_site',
        ])->assertRedirect(route('lists.create.step4_static_site'));

        $this->post(route('lists.create.step4_static_site.submit'), [
            'notify_by_email' => '1',
        ])->assertRedirect(route('lists.create.step6'));

        $response = $this->post(route('lists.create.step6.submit'));

        // Should redirect to step7 with the new list's ID
        $response->assertRedirect();
        $this->assertStringContainsString('lists/create/step7', $response->headers->get('Location'));

        $this->assertDatabaseHas('lists', [
            'user_id'         => $this->user->id,
            'name'            => 'Static Site List',
            'output_type'     => 'static_site',
            'notify_by_email' => true,
            'retention_count' => 10,
        ]);
    }

    public function test_list_wizard_static_site_step7_shows_deploy_hook_cta(): void
    {
        $list = ListModel::factory()->forUser($this->user)->staticSite()->create();

        $this->get(route('lists.create.step7', ['list' => $list->id]))
            ->assertOk()
            ->assertSee('Add Deploy Hook');
    }

    public function test_list_wizard_static_site_step3_rejects_invalid_output_type(): void
    {
        $this->withSession([
            'list_wizard' => [
                'name'               => 'Test',
                'schedule_frequency' => 'daily',
                'schedule_time'      => '08:00',
            ],
        ])->post(route('lists.create.step3.submit'), [
            'output_type' => 'bob_put_a_dummy_value_here',
        ])->assertSessionHasErrors('output_type');
    }

    public function test_list_wizard_static_site_step4_guards_against_wrong_output_type(): void
    {
        // Trying to access static site step 4 when output_type is email should redirect
        $this->withSession([
            'list_wizard' => [
                'name'               => 'Test',
                'schedule_frequency' => 'daily',
                'schedule_time'      => '08:00',
                'output_type'        => 'email',
            ],
        ])->get(route('lists.create.step4_static_site'))
          ->assertRedirect(route('lists.create.step1'));
    }

    public function test_list_wizard_clears_session_after_static_site_creation(): void
    {
        $response = $this->withSession([
            'list_wizard' => [
                'name'               => 'Cleanup Test',
                'description'        => null,
                'timezone'           => 'America/Toronto',
                'schedule_frequency' => 'daily',
                'schedule_day'       => null,
                'schedule_time'      => '06:00',
                'output_type'        => 'static_site',
                'notify_by_email'    => true,
            ],
        ])->post(route('lists.create.step6.submit'));

        $response->assertSessionMissing('list_wizard');
    }
}