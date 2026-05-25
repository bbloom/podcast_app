<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('home');
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/user_account.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/AA_Playtime/routes.php';


require dirname(__DIR__) . '/MEDIA_PLATFORM/Configuration/Routes/language_models.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/youtube.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/lists.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/output_destination_fix.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/podcasts.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/text_based_rss_feeds.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/AdHocPrompt/Routes/ad_hoc_prompt.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/HealthChecks/Routes/health_checks_routes.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/DatabaseBackup/Routes/routes.php';


// Podcast Shows
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Shows/Routes/podcast_shows.php';

// Podcast Links
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Links/Routes/podcast_links.php';

// Podcast Guests
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Guests/Routes/podcast_guests.php';

// Podcast Episodes Planning
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Planning/CRUD/Routes/podcast_episodes_planning.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/CRUD/Routes/podcast_episodes_planning.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/CreateEpisodeWizard/Routes/create_episode_wizard.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/EditThemeField/Routes/edit_theme_field.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/EditScriptField/Routes/edit_script_field.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/FinalizeScriptWizard/Routes/finalize_script_wizard.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/CRUD/Routes/podcast_episodes_planning_guests.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/CRUD/Routes/podcast_episodes_planning_links.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/PrepareForPublishingWizard/Routes/prepare_for_publishing_wizard.php';
require __DIR__.'/../MEDIA_PLATFORM/Podcasts/Planning/RecordingView/Routes/recording_view.php';

// Podcast Episodes Published
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/Routes/podcast_episodes.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/dashboard.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/upload_recording.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/auphonic_processing.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/upload_production_audio.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/generate_rss_feed.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/publish_on_website.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/build_confirmation.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Publishing/PostProduction/Routes/regenerate_rss_feed.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/API/v1/Routes/web.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/StaticSiteDeployHooks/Routes/deploy_hooks.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/FooterLinks/Routes/footer_links.php';





require dirname(__DIR__) . '/MEDIA_PLATFORM/Podcasts/Dashboard/Routes/podcasts_dashboard.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/PhpServerlessProjectSponsors/Routes/phpserverlessproject_sponsors.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Videos/Routes/videos.php';

// require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/debug_processing.php';
