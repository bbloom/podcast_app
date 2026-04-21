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

// Podcast Studio — Management
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/Management/Routes/podcast_shows.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/Management/Routes/podcast_episodes.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/Management/Routes/podcast_links.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/Management/Routes/podcast_guests.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/PhpServerlessProjectSponsors/Routes/phpserverlessproject_sponsors.php';

require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PreProduction/Routes/create_episode.php';


require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/dashboard.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/upload_recording.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/auphonic_processing.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/upload_production_audio.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/generate_rss_feed.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/publish_on_website.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/PodcastStudio/PostProduction/Routes/regenerate_rss_feed.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/API/v1/Routes/web.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/StaticSiteDeployHooks/Routes/deploy_hooks.php';
require dirname(__DIR__) . '/MEDIA_PLATFORM/Tools/FooterLinks/Routes/footer_links.php';


// require dirname(__DIR__) . '/MEDIA_PLATFORM/Digest/Routes/debug_processing.php';
