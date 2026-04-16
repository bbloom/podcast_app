<?php

// =============================================================================
// DeployHookController
//
// CRUD for deploy hooks. Hooks are polymorphic — they can belong to any
// triggerable model. Currently used by PodcastShow.
//
// Ownership is checked on every action by resolving the triggerable model
// and confirming it belongs to the authenticated user.
//
// No service class — this is a straightforward database CRUD with no
// external calls. Triggering logic lives in DeployHookTriggerService.
//
// Path: MEDIA_PLATFORM/StaticSiteDeployHooks/Controllers/
// =============================================================================

namespace MediaPlatform\StaticSiteDeployHooks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Requests\DeployHookRequest;

class DeployHookController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return all podcast shows belonging to the authenticated user,
     * ordered by title. Used to populate the show dropdown in create/edit forms.
     */
    private function userShows()
    {
        return PodcastShow::where('user_id', auth()->id())
            ->orderBy('title')
            ->get();
    }

    /**
     * Resolve the triggerable model from the request and verify ownership.
     * Returns the model if owned by the authenticated user, or aborts with 403.
     *
     * Currently only supports PodcastShow. Extend this method when additional
     * triggerable types are added (e.g. Digest ListModel).
     */
    private function resolveAndAuthorizeTriggerable(string $type, int $id): PodcastShow
    {
        $triggerable = match ($type) {
            'podcast_show' => PodcastShow::findOrFail($id),
            default        => abort(422, 'Unsupported triggerable type.'),
        };

        abort_if($triggerable->user_id !== auth()->id(), 403);

        return $triggerable;
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Display all deploy hooks belonging to the authenticated user's triggerables,
     * ordered by label.
     */
    public function index(): View
    {
        // Fetch hooks whose triggerable is a PodcastShow owned by this user.
        // As new triggerable types are added, extend this query accordingly.
        $hooks = DeployHook::where('triggerable_type', 'podcast_show')
            ->whereIn('triggerable_id',
                PodcastShow::where('user_id', auth()->id())->pluck('id')
            )
            ->with('triggerable')
            ->orderBy('label')
            ->get();

        return view('media_platform.static_site_deploy_hooks.index', compact('hooks'));
    }

    /**
     * Show the form for creating a new deploy hook.
     */
    public function create(): View
    {
        $shows     = $this->userShows();
        $providers = DeployHookProvider::cases();

        return view('media_platform.static_site_deploy_hooks.create', compact('shows', 'providers'));
    }

    /**
     * Persist a new deploy hook.
     * Resolves and authorises the triggerable before creating.
     */
    public function store(DeployHookRequest $request): RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $request->triggerable_type,
            $request->triggerable_id
        );

        $hook = DeployHook::create([
            'triggerable_type' => $request->triggerable_type,
            'triggerable_id'   => $request->triggerable_id,
            'label'            => $request->label,
            'provider'         => $request->provider,
            'url'              => $request->url,
            'enabled'          => $request->boolean('enabled'),
        ]);

        return redirect()
            ->route('deploy_hooks.show', $hook)
            ->with('success', 'Deploy hook created successfully.');
    }

    /**
     * Display a single deploy hook.
     * Ownership check via the triggerable model.
     */
    public function show(DeployHook $deploy_hook): View
    {
        $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.show', ['hook' => $deploy_hook]);
    }

    /**
     * Show the form for editing a deploy hook.
     */
    public function edit(DeployHook $deploy_hook): View
    {
        $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        $shows     = $this->userShows();
        $providers = DeployHookProvider::cases();

        return view('media_platform.static_site_deploy_hooks.edit', [
            'hook'      => $deploy_hook,
            'shows'     => $shows,
            'providers' => $providers,
        ]);
    }

    /**
     * Persist updates to a deploy hook.
     * Re-validates triggerable ownership in case it changed.
     */
    public function update(DeployHookRequest $request, DeployHook $deploy_hook): RedirectResponse
    {
        // Ownership check on the existing hook.
        $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        // Ownership check on the (possibly changed) triggerable.
        $this->resolveAndAuthorizeTriggerable(
            $request->triggerable_type,
            $request->triggerable_id
        );

        // Only overwrite the encrypted URL if a new one was submitted.
        // If left blank, the existing URL is preserved.
        $data = [
            'triggerable_type' => $request->triggerable_type,
            'triggerable_id'   => $request->triggerable_id,
            'label'            => $request->label,
            'provider'         => $request->provider,
            'enabled'          => $request->boolean('enabled'),
        ];

        if ($request->filled('url')) {
            $data['url'] = $request->url;
        }

        $deploy_hook->update($data);

        return redirect()
            ->route('deploy_hooks.show', $deploy_hook)
            ->with('success', 'Deploy hook updated successfully.');
    }

    /**
     * Show the delete confirmation page for a deploy hook.
     */
    public function deleteConfirm(DeployHook $deploy_hook): View
    {
        $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.delete_confirm', ['hook' => $deploy_hook]);
    }

    /**
     * Delete a deploy hook permanently.
     */
    public function destroy(DeployHook $deploy_hook): RedirectResponse
    {
        $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        $deploy_hook->delete();

        return redirect()
            ->route('deploy_hooks.index')
            ->with('success', 'Deploy hook deleted successfully.');
    }
}