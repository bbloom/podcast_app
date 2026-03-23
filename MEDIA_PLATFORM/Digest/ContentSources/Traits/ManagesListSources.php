<?php

namespace MediaPlatform\Digest\ContentSources\Traits;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * ManagesListSources
 *
 * Shared trait used by YoutubeChannelWizardController, PodcastWizardController,
 * and TextBasedRssFeedWizardController to provide attach / update / detach
 * functionality for list_source rows.
 *
 * Each controller that uses this trait must implement:
 *   - sourceRouteParam(): string  — the route parameter name (e.g. 'youtubeChannel')
 *   - sourceShowRoute(): string   — the named route for the source's show page
 *   - detachConfirmView(): string — the blade view for the detach confirmation page
 *
 * The concrete controller methods (attachList, updateListSource, detachConfirm,
 * detach) receive the resolved source model as their first argument via Laravel's
 * route model binding — the trait methods accept it as a generic Model.
 */
trait ManagesListSources
{
    // =========================================================================
    // Attach
    // =========================================================================

    /**
     * Attach the source to a list by creating a list_source row.
     *
     * Validates that:
     *   - a list_id is provided and belongs to the authenticated user
     *   - the combination of source + list does not already exist (the DB has a
     *     unique constraint, but we catch it here for a friendly error message)
     *
     * On success, redirects back to the source's show page with a flash message.
     */
    protected function handleAttach(Request $request, Model $source, string $showRoute): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'list_id'         => ['required', 'integer'],
            'processing_mode' => ['required', 'in:description,summary,search'],
            'search_terms'    => ['nullable', 'string', 'max:500'],
        ]);

        $listId = (int) $request->input('list_id');

        // Confirm the list belongs to the authenticated user
        $list = ListModel::where('id', $listId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $list) {
            return back()->withErrors(['list_id' => 'Invalid list selected.']);
        }

        // Check for an existing attachment — surface a friendly error rather
        // than letting the unique constraint throw a DB exception
        $alreadyAttached = ListSource::where('list_id', $listId)
            ->where('sourceable_id', $source->id)
            ->where('sourceable_type', $source->getMorphClass())
            ->exists();

        if ($alreadyAttached) {
            return back()->withErrors(['list_id' => "This source is already attached to \"{$list->name}\"."]);
        }

        $mode = $request->input('processing_mode');

        ListSource::create([
            'list_id'         => $listId,
            'sourceable_id'   => $source->id,
            'sourceable_type' => $source->getMorphClass(),
            'enabled'         => true,
            'suspended'       => false,
            'processing_mode' => $mode,
            // Only store search_terms when in search mode — null it out otherwise
            'search_terms'    => $mode === 'search' ? $request->input('search_terms') : null,
        ]);

        return redirect()->route($showRoute, $source)
            ->with('success', "Attached to list \"{$list->name}\" successfully.");
    }

    // =========================================================================
    // Update (processing mode + search terms)
    // =========================================================================

    /**
     * Update the processing_mode and search_terms on an existing list_source row.
     *
     * Guards:
     *   - The list_source must belong to this source (sourceable_id + type match)
     *   - The list_source's list must belong to the authenticated user
     */
    protected function handleUpdateListSource(Request $request, Model $source, ListSource $listSource, string $showRoute): \Illuminate\Http\RedirectResponse
    {
        // Verify ownership — the list that owns this list_source must belong to the user
        abort_if($listSource->list->user_id !== auth()->id(), 403);

        // Verify the list_source actually belongs to this source
        abort_if(
            $listSource->sourceable_id !== $source->id ||
            $listSource->sourceable_type !== $source->getMorphClass(),
            403
        );

        $request->validate([
            'processing_mode' => ['required', 'in:description,summary,search'],
            'search_terms'    => ['nullable', 'string', 'max:500'],
        ]);

        $mode = $request->input('processing_mode');

        $listSource->update([
            'processing_mode' => $mode,
            // Clear search_terms when switching away from search mode
            'search_terms'    => $mode === 'search' ? $request->input('search_terms') : null,
        ]);

        return redirect()->route($showRoute, $source)
            ->with('success', 'Processing settings updated.');
    }

    // =========================================================================
    // Detach confirm
    // =========================================================================

    /**
     * Show the detach confirmation page.
     *
     * Passes both the source model and the list_source (with its list eager-loaded)
     * so the view can clearly name which list will be affected.
     */
    protected function handleDetachConfirm(Model $source, ListSource $listSource, string $confirmView): \Illuminate\View\View
    {
        // Verify ownership
        abort_if($listSource->list->user_id !== auth()->id(), 403);

        // Verify the list_source belongs to this source
        abort_if(
            $listSource->sourceable_id !== $source->id ||
            $listSource->sourceable_type !== $source->getMorphClass(),
            403
        );

        // Eager-load the list so the view can name it
        $listSource->load('list');

        return view($confirmView, [
            'source'     => $source,
            'listSource' => $listSource,
        ]);
    }

    // =========================================================================
    // Detach (destroy)
    // =========================================================================

    /**
     * Delete the list_source row.
     *
     * The FK cascade on list_sources → summaries means all related summary
     * rows are automatically deleted. The FK cascade on list_sources →
     * list_source_tracking also cleans up tracking rows.
     */
    protected function handleDetach(Model $source, ListSource $listSource, string $showRoute): \Illuminate\Http\RedirectResponse
    {
        // Verify ownership
        abort_if($listSource->list->user_id !== auth()->id(), 403);

        // Verify the list_source belongs to this source
        abort_if(
            $listSource->sourceable_id !== $source->id ||
            $listSource->sourceable_type !== $source->getMorphClass(),
            403
        );

        $listName = $listSource->list->name;

        $listSource->delete();

        return redirect()->route($showRoute, $source)
            ->with('success', "Detached from list \"{$listName}\".");
    }
}