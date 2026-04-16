<?php

// =============================================================================
// DeployHookController
//
// CRUD for deploy hooks. Each hook belongs to a podcast show owned by the
// authenticated user. Ownership is checked on every action.
//
// No service class — this is a straightforward database CRUD with no
// external calls. Triggering logic will be added in a future iteration.
//
// Path: MEDIA_PLATFORM/PodcastStudio/PostProduction/DeployHooks/Controllers/
// =============================================================================

namespace MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Enums\DeployHookProvider;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Models\DeployHook;
use MediaPlatform\PodcastStudio\PostProduction\DeployHooks\Requests\DeployHookRequest;

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

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Display all deploy hooks belonging to the authenticated user's shows,
     * ordered by show title then label.
     */
    public function index(): View
    {
        $hooks = DeployHook::whereHas('show', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('show')
            ->orderBy('label')
            ->get();

        return view('media_platform.podcast_studio.post_production.deploy_hooks.index', compact('hooks'));
    }

    /**
     * Show the form for creating a new deploy hook.
     */
    public function create(): View
    {
        $shows     = $this->userShows();
        $providers = DeployHookProvider::cases();

        return view('media_platform.podcast_studio.post_production.deploy_hooks.create', compact('shows', 'providers'));
    }

    /**
     * Persist a new deploy hook.
     * Verifies the selected show belongs to the authenticated user.
     */
    public function store(DeployHookRequest $request): RedirectResponse
    {
        // Ownership: ensure the selected show belongs to this user.
        $show = PodcastShow::findOrFail($request->validated()['podcast_show_id']);
        abort_if($show->user_id !== auth()->id(), 403);

        $hook = DeployHook::create([
            'podcast_show_id' => $request->podcast_show_id,
            'label'           => $request->label,
            'provider'        => $request->provider,
            'url'             => $request->url,
            'enabled'         => $request->boolean('enabled'),
        ]);

        return redirect()
            ->route('deploy_hooks.show', $hook)
            ->with('success', 'Deploy hook created successfully.');
    }

    /**
     * Display a single deploy hook.
     * Ownership check: only the owning user may view their hook.
     */
    public function show(DeployHook $deploy_hook): View
    {
        abort_if($deploy_hook->show->user_id !== auth()->id(), 403);

        $deploy_hook->load('show');

        return view('media_platform.podcast_studio.post_production.deploy_hooks.show', ['hook' => $deploy_hook]);
    }

    /**
     * Show the form for editing a deploy hook.
     */
    public function edit(DeployHook $deploy_hook): View
    {
        abort_if($deploy_hook->show->user_id !== auth()->id(), 403);

        $shows     = $this->userShows();
        $providers = DeployHookProvider::cases();

        return view('media_platform.podcast_studio.post_production.deploy_hooks.edit', [
            'hook'      => $deploy_hook,
            'shows'     => $shows,
            'providers' => $providers,
        ]);
    }

    /**
     * Persist updates to a deploy hook.
     * Re-validates show ownership in case the show is changed.
     */
    public function update(DeployHookRequest $request, DeployHook $deploy_hook): RedirectResponse
    {
        abort_if($deploy_hook->show->user_id !== auth()->id(), 403);

        // Ownership: ensure the (possibly changed) show still belongs to this user.
        $show = PodcastShow::findOrFail($request->validated()['podcast_show_id']);
        abort_if($show->user_id !== auth()->id(), 403);

        // Only overwrite the encrypted URL if a new one was submitted.
        // If left blank, the existing URL is preserved.
        $data = [
            'podcast_show_id' => $request->podcast_show_id,
            'label'           => $request->label,
            'provider'        => $request->provider,
            'enabled'         => $request->boolean('enabled'),
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
        abort_if($deploy_hook->show->user_id !== auth()->id(), 403);

        $deploy_hook->load('show');

        return view('media_platform.podcast_studio.post_production.deploy_hooks.delete_confirm', ['hook' => $deploy_hook]);
    }

    /**
     * Delete a deploy hook permanently.
     */
    public function destroy(DeployHook $deploy_hook): RedirectResponse
    {
        abort_if($deploy_hook->show->user_id !== auth()->id(), 403);

        $deploy_hook->delete();

        return redirect()
            ->route('deploy_hooks.index')
            ->with('success', 'Deploy hook deleted successfully.');
    }
}