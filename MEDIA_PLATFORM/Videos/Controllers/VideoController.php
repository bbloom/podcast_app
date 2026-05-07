<?php

namespace MediaPlatform\Videos\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\Videos\Models\Video;
use MediaPlatform\Videos\Requests\VideoRequest;

/**
 * CRUD controller for videos (index, show, edit, update, deleteConfirm, destroy).
 * Create/store is handled by the Create Video wizard controllers.
 */
class VideoController extends Controller
{
    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    /**
     * Display all videos belonging to the authenticated user.
     */
    public function index(): View
    {
        $videos = Video::forUser(auth()->id())
            ->orderByDesc('id')
            ->paginate(config('admin.pagination_index'));

        return view('media_platform.videos.index', compact('videos'));
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    /**
     * Display a single video.
     */
    public function show(Video $video): View|RedirectResponse
    {
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('videos.index')
                ->with('error', 'That video could not be found.');
        }

        return view('media_platform.videos.show', compact('video'));
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    /**
     * Show the form for editing a video.
     */
    public function edit(Video $video): View|RedirectResponse
    {
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('videos.index')
                ->with('error', 'That video could not be found.');
        }

        return view('media_platform.videos.edit', compact('video'));
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    /**
     * Persist updates to a video.
     */
    public function update(VideoRequest $request, Video $video): RedirectResponse
    {
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('videos.index')
                ->with('error', 'That video could not be found.');
        }

        $video->update($request->validated());

        return redirect()
            ->route('videos.show', $video)
            ->with('success', 'Video updated successfully.');
    }

    // -------------------------------------------------------------------------
    // deleteConfirm
    // -------------------------------------------------------------------------

    /**
     * Show the delete confirmation page.
     */
    public function deleteConfirm(Video $video): View|RedirectResponse
    {
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('videos.index')
                ->with('error', 'That video could not be found.');
        }

        return view('media_platform.videos.delete_confirm', compact('video'));
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    /**
     * Delete a video.
     */
    public function destroy(Video $video): RedirectResponse
    {
        if ($video->user_id !== auth()->id()) {
            return redirect()->route('videos.index')
                ->with('error', 'That video could not be found.');
        }

        $video->delete();

        return redirect()
            ->route('videos.index')
            ->with('success', 'Video deleted successfully.');
    }
}