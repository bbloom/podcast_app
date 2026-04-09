<?php

namespace MediaPlatform\API\v1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MediaPlatform\API\v1\Models\ApiControl;

class ApiControlController extends Controller
{
    /**
     * Enable the public API.
     */
    public function enable(Request $request)
    {
        if (! auth()->user()->can('admin')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        ApiControl::instance()->enable();

        return redirect()
            ->route('api_management.dashboard')
            ->with('success', 'API enabled. The endpoint is now accepting requests.')
        ;
    }

    /**
     * Disable the public API.
     */
    public function disable(Request $request)
    {
        if (! auth()->user()->can('admin')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        ApiControl::instance()->disable();

        return redirect()
            ->route('api_management.dashboard')
            ->with('success', 'API disabled. The endpoint is no longer accepting requests.')
        ;
    }
}