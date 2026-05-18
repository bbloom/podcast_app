<?php

// =============================================================================
// DeployHookController
//
// CRUD for deploy hooks. Hooks are polymorphic — they can belong to any
// triggerable model. Currently supports PodcastShow and ListModel (digest lists).
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Enums\OutputType;
use MediaPlatform\Podcasts\Shows\Models\PodcastShow;
use MediaPlatform\StaticSiteDeployHooks\Enums\DeployHookProvider;
use MediaPlatform\StaticSiteDeployHooks\Models\DeployHook;
use MediaPlatform\StaticSiteDeployHooks\Requests\DeployHookRequest;
use MediaPlatform\StaticSiteDeployHooks\Services\DeployHookTriggerService;
use MediaPlatform\API\v1\Models\ApiControl;

class DeployHookController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return all podcast shows belonging to the authenticated user.
     */
    private function userShows()
    {
        return PodcastShow::where('user_id', auth()->id())
            ->orderBy('title')
            ->get();
    }

    /**
     * Return all static site digest lists belonging to the authenticated user.
     */
    private function userLists()
    {
        return ListModel::where('user_id', auth()->id())
            ->where('output_type', OutputType::StaticSite->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve the triggerable model from the request and verify ownership.
     * Returns the model if owned by the authenticated user.
     * Redirects with a friendly error if the type is unsupported or the
     * model does not belong to the authenticated user.
     *
     * Supports: podcast_show, digest_list.
     */
    private function resolveAndAuthorizeTriggerable(string $type, int $id): Model|RedirectResponse
    {
        $triggerable = match ($type) {
            'podcast_show' => PodcastShow::find($id),
            'digest_list'  => ListModel::find($id),
            default        => null,
        };

        if (! $triggerable) {
            return redirect()
                ->route('deploy_hooks.index')
                ->with('error', match ($type) {
                    'podcast_show' => 'The selected show could not be found.',
                    'digest_list'  => 'The selected list could not be found.',
                    default        => 'Unsupported triggerable type.',
                });
        }

        if ($triggerable->user_id !== auth()->id()) {
            return redirect()
                ->route('deploy_hooks.index')
                ->with('error', 'You do not have permission to access that item.');
        }

        return $triggerable;
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Display all deploy hooks belonging to the authenticated user's triggerables.
     */
    public function index(): View
    {
        $userId = auth()->id();

        $showIds = PodcastShow::where('user_id', $userId)->pluck('id');
        $listIds = ListModel::where('user_id', $userId)
            ->where('output_type', OutputType::StaticSite->value)
            ->pluck('id');

        $hooks = DeployHook::where(function ($q) use ($showIds) {
                $q->where('triggerable_type', 'podcast_show')
                  ->whereIn('triggerable_id', $showIds);
            })
            ->orWhere(function ($q) use ($listIds) {
                $q->where('triggerable_type', 'digest_list')
                  ->whereIn('triggerable_id', $listIds);
            })
            ->with('triggerable')
            ->orderBy('label')
            ->get();

        return view('media_platform.static_site_deploy_hooks.index', compact('hooks'));
    }

    /**
     * Show the form for creating a new deploy hook.
     * Accepts optional query parameters to pre-fill the triggerable:
     *   ?triggerable_type=digest_list&triggerable_id=5&redirect_to=lists.show
     */
    public function create(Request $request): View
    {
        $shows     = $this->userShows();
        $lists     = $this->userLists();
        $providers = DeployHookProvider::cases();

        $prefillType = $request->query('triggerable_type');
        $prefillId   = $request->query('triggerable_id');
        $redirectTo  = $request->query('redirect_to');

        return view('media_platform.static_site_deploy_hooks.create', compact(
            'shows', 'lists', 'providers', 'prefillType', 'prefillId', 'redirectTo'
        ));
    }

    /**
     * Persist a new deploy hook.
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

        // If a redirect_to route was provided (e.g. from the list wizard),
        // redirect there instead of to the hook show page.
        $redirectTo = $request->input('redirect_to');

        if ($redirectTo && \Illuminate\Support\Facades\Route::has($redirectTo)) {
            // For routes that need a parameter (like lists.show), use the triggerable ID.
            try {
                return redirect()->route($redirectTo, $triggerable)
                    ->with('success', 'Deploy hook created successfully.');
            } catch (\Throwable) {
                // Fall through to default redirect if route needs different params.
            }
        }

        return redirect()
            ->route('deploy_hooks.show', $hook)
            ->with('success', 'Deploy hook created successfully.');
    }

    /**
     * Display a single deploy hook.
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

        return view('media_platform.static_site_deploy_hooks.show', [
            'hook'      => $deploy_hook,
            'apiStatus' => ApiControl::getStatus(),
        ]);
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
        $lists     = $this->userLists();
        $providers = DeployHookProvider::cases();

        return view('media_platform.static_site_deploy_hooks.edit', [
            'hook'      => $deploy_hook,
            'shows'     => $shows,
            'lists'     => $lists,
            'providers' => $providers,
        ]);
    }

    /**
     * Persist updates to a deploy hook.
     */
    public function update(DeployHookRequest $request, DeployHook $deploy_hook): RedirectResponse
    {
        $triggerable = $this->resolveAndAuthorizeTriggerable(
            $request->triggerable_type,
            $request->triggerable_id
        );

        if ($triggerable instanceof RedirectResponse) {
            return $triggerable;
        }

        $data = [
            'triggerable_type' => $request->triggerable_type,
            'triggerable_id'   => $request->triggerable_id,
            'label'            => $request->label,
            'provider'         => $request->provider,
            'enabled'          => $request->boolean('enabled'),
        ];

        // Only update the URL if a new one was provided.
        if ($request->filled('url')) {
            $data['url'] = $request->url;
        }

        $deploy_hook->update($data);

        return redirect()
            ->route('deploy_hooks.show', $deploy_hook)
            ->with('success', 'Deploy hook updated successfully.');
    }

    /**
     * Show the delete confirmation page.
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