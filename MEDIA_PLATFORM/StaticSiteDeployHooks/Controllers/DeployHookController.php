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
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;

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
     * Returns the model if owned by the authenticated user.
     * Redirects with a friendly error if the type is unsupported or the
     * model does not belong to the authenticated user.
     *
     * Currently only supports PodcastShow. Extend this method when additional
     * triggerable types are added (e.g. Digest ListModel).
     */
    private function resolveAndAuthorizeTriggerable(string $type, int $id): PodcastShow|RedirectResponse
    {
        if ($type !== 'podcast_show') {
            return redirect()
                ->route('deploy_hooks.index')
                ->with('error', 'Unsupported triggerable type.');
        }

        $triggerable = PodcastShow::find($id);

        if (! $triggerable) {
            return redirect()
                ->route('deploy_hooks.index')
                ->with('error', 'The selected show could not be found.');
        }

        if ($triggerable->user_id !== auth()->id()) {
            return redirect()
                ->route('deploy_hooks.index')
                ->with('error', 'You do not have permission to access that show.');
        }

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

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

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
    public function show(DeployHook $deploy_hook): View|RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.show', ['hook' => $deploy_hook]);
    }

    /**
     * Show the form for editing a deploy hook.
     */
    public function edit(DeployHook $deploy_hook): View|RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

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
        $existing = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($existing instanceof RedirectResponse) {
            return $existing;
        }

        // Ownership check on the (possibly changed) triggerable.
        $new = $this->resolveAndAuthorizeTriggerable(
            $request->triggerable_type,
            $request->triggerable_id
        );

        if ($new instanceof RedirectResponse) {
            return $new;
        }

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
    public function deleteConfirm(DeployHook $deploy_hook): View|RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.delete_confirm', ['hook' => $deploy_hook]);
    }

    /**
     * Delete a deploy hook permanently.
     */
    public function destroy(DeployHook $deploy_hook): RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        $deploy_hook->delete();

        return redirect()
            ->route('deploy_hooks.index')
            ->with('success', 'Deploy hook deleted successfully.');
    }

    // =========================================================================
    // Trigger
    // =========================================================================

    /**
     * Show the confirmation page before triggering this specific hook.
     */
    public function confirmTrigger(DeployHook $deploy_hook): View|RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        if (! $deploy_hook->enabled) {
            return redirect()
                ->route('deploy_hooks.show', $deploy_hook)
                ->with('error', 'This deploy hook is disabled and cannot be triggered.');
        }

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.trigger_confirm', [
            'hook' => $deploy_hook,
        ]);
    }

    /**
     * Fire this specific deploy hook via DeployHookTriggerService.
     * Stores the single result in the session and redirects to the result page.
     */
    public function executeTrigger(DeployHook $deploy_hook, DeployHookTriggerService $service): RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        if (! $deploy_hook->enabled) {
            return redirect()
                ->route('deploy_hooks.show', $deploy_hook)
                ->with('error', 'This deploy hook is disabled and cannot be triggered.');
        }

        $result = $service->trigger($deploy_hook);

        session(['deploy_hook.trigger_result' => $result]);

        return redirect()->route('deploy_hooks.trigger.result', $deploy_hook);
    }

    /**
     * Display the result of triggering this specific hook.
     * Reads from the session and clears it after display.
     */
    public function triggerResult(DeployHook $deploy_hook): View|RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $deploy_hook->triggerable_type,
            $deploy_hook->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        $result = session()->pull('deploy_hook.trigger_result');

        if (! $result) {
            return redirect()
                ->route('deploy_hooks.show', $deploy_hook)
                ->with('error', 'No trigger result found. Please trigger the build from the hook page.');
        }

        $deploy_hook->load('triggerable');

        return view('media_platform.static_site_deploy_hooks.trigger_result', [
            'hook'   => $deploy_hook,
            'result' => $result,
        ]);
    }
}