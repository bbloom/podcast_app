<?php

namespace MediaPlatform\API\v1\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;
use MediaPlatform\Digest\Publishing\Models\PublishedDigest;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the API management dashboard.
     * Shows the current API on/off status, a summary of all clients,
     * and any pending static site fetches.
     */
    public function __invoke(Request $request)
    {
        if (! auth()->user()->can('admin')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        $control = ApiControl::instance();
        $clients = ApiClient::orderBy('label')->get();

        // Find published digests where the deploy hook was fired but the
        // static site has not yet fetched the data. These indicate a build
        // is pending — the API should not be disabled until they complete.
        $pendingFetches = PublishedDigest::whereNotNull('deploy_hook_fired_at')
            ->whereNull('api_fetched_at')
            ->join('lists', 'published_digests.list_id', '=', 'lists.id')
            ->select([
                'published_digests.id',
                'published_digests.slug',
                'published_digests.deploy_hook_fired_at',
                'lists.name as list_name',
            ])
            ->orderByDesc('published_digests.deploy_hook_fired_at')
            ->limit(10)
            ->get();

        return view('media_platform.api.v1.dashboard', compact('control', 'clients', 'pendingFetches'));
    }
}