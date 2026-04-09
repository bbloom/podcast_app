<?php

namespace MediaPlatform\API\v1\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Models\ApiControl;

class DashboardController extends Controller
{
    /**
     * Display the API management dashboard.
     * Shows the current API on/off status and a summary of all clients.
     */
    public function __invoke(Request $request)
    {
        if (! auth()->user()->can('admin')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        $control = ApiControl::instance();
        $clients = ApiClient::orderBy('label')->get();

        return view('media_platform.api.v1.dashboard', compact('control', 'clients'));
    }
}