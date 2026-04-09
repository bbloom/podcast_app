<?php

namespace MediaPlatform\API\v1\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\API\v1\Models\ApiClient;
use MediaPlatform\API\v1\Requests\ApiClientRequest;

class ApiClientController extends Controller
{
    // -------------------------------------------------------------------------
    // Shared guard — redirects non-admins gracefully rather than throwing 403.
    // -------------------------------------------------------------------------

    /**
     * Redirect non-admin users back to the dashboard with a friendly message.
     * Returns null if the user is an admin (i.e. access is allowed).
     */
    private function denyIfNotAdmin()
    {
        if (! auth()->user()->can('admin')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Display all API clients.
     */
    public function index()
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        $clients = ApiClient::orderBy('label')->get();

        return view('media_platform.api.v1.api_clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new API client.
     */
    public function create()
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        return view('media_platform.api.v1.api_clients.create');
    }

    /**
     * Persist a new API client and generate its initial bearer token.
     * The plain-text token is shown once in a flash message and never stored.
     */
    public function store(ApiClientRequest $request)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        $client = ApiClient::create([
            'label'      => $request->label,
            'domain'     => $request->domain,
            'token_hash' => '',
            'is_active'  => $request->is_active,
        ]);

        $plainToken = $client->generateToken();

        return redirect()
            ->route('api_management.clients.show', $client)
            ->with('token', $plainToken)
        ;
    }

    /**
     * Display a single API client.
     */
    public function show(ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        return view('media_platform.api.v1.api_clients.show', ['client' => $api_client]);
    }

    /**
     * Show the form for editing an API client.
     */
    public function edit(ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        return view('media_platform.api.v1.api_clients.edit', ['client' => $api_client]);
    }

    /**
     * Persist updates to an API client.
     * Token is never touched during a standard update.
     */
    public function update(ApiClientRequest $request, ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        $api_client->update($request->validated());

        return redirect()
            ->route('api_management.clients.show', $api_client)
            ->with('success', 'Client updated successfully.')
        ;
    }

    /**
     * Show the delete confirmation page.
     */
    public function deleteConfirm(ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        return view('media_platform.api.v1.api_clients.delete_confirm', ['client' => $api_client]);
    }

    /**
     * Delete an API client permanently.
     */
    public function destroy(ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        $api_client->delete();

        return redirect()
            ->route('api_management.clients.index')
            ->with('success', 'Client deleted successfully.')
        ;
    }

    /**
     * Rotate the bearer token for an API client.
     * The new plain-text token is shown once in a flash message and never stored.
     * The old token is immediately invalidated.
     */
    public function rotateToken(ApiClient $api_client)
    {
        if ($deny = $this->denyIfNotAdmin()) return $deny;

        $plainToken = $api_client->generateToken();

        return redirect()
            ->route('api_management.clients.show', $api_client)
            ->with('token', $plainToken)
        ;
    }
}