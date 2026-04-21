<?php

// =============================================================================
// FooterLinkController
//
// CRUD for footer links. Each footer link belongs to exactly one podcast show.
// Ownership is checked via user_id on every action.
//
// No service class — this is a straightforward database CRUD with no
// external calls.
//
// Path: MEDIA_PLATFORM/Tools/FooterLinks/Controllers/
// =============================================================================

namespace MediaPlatform\Tools\FooterLinks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use MediaPlatform\PodcastStudio\Management\Models\PodcastShow;
use MediaPlatform\Tools\FooterLinks\Models\FooterLink;
use MediaPlatform\Tools\FooterLinks\Requests\FooterLinkRequest;

class FooterLinkController extends Controller
{
    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Display all footer links belonging to the authenticated user.
     */
    public function index(): View
    {
        $footerLinks = FooterLink::where('user_id', auth()->id())
            ->with('podcastShow')
            ->orderBy('podcast_show_id')
            ->orderBy('link_order')
            ->paginate(20);

        return view('media_platform.tools.footer_links.index', compact('footerLinks'));
    }

    /**
     * Show the form for creating a new footer link.
     * Accepts an optional podcast_show_id query parameter to pre-select the show.
     */
    public function create(): View
    {
        $shows = PodcastShow::where('user_id', auth()->id())
            ->orderBy('title')
            ->get();

        $selectedShowId = request()->query('podcast_show_id');

        return view('media_platform.tools.footer_links.create', compact('shows', 'selectedShowId'));
    }

    /**
     * Persist a new footer link for the authenticated user.
     */
    public function store(FooterLinkRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Verify the selected show belongs to the authenticated user.
        $show = PodcastShow::find($data['podcast_show_id']);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('footer_links.create')
                ->with('error', 'The selected podcast show could not be found.');
        }

        $data['user_id'] = auth()->id();

        $footerLink = FooterLink::create($data);

        return redirect()
            ->route('footer_links.show', $footerLink)
            ->with('success', 'Footer link created successfully.');
    }

    /**
     * Display a single footer link.
     */
    public function show(FooterLink $footer_link): View
    {
        abort_if($footer_link->user_id !== auth()->id(), 403);

        $footer_link->load('podcastShow');

        return view('media_platform.tools.footer_links.show', compact('footer_link'));
    }

    /**
     * Show the form for editing a footer link.
     */
    public function edit(FooterLink $footer_link): View
    {
        abort_if($footer_link->user_id !== auth()->id(), 403);

        $shows = PodcastShow::where('user_id', auth()->id())
            ->orderBy('title')
            ->get();

        return view('media_platform.tools.footer_links.edit', compact('footer_link', 'shows'));
    }

    /**
     * Persist updates to a footer link.
     */
    public function update(FooterLinkRequest $request, FooterLink $footer_link): RedirectResponse
    {
        abort_if($footer_link->user_id !== auth()->id(), 403);

        $data = $request->validated();

        // Verify the selected show belongs to the authenticated user.
        $show = PodcastShow::find($data['podcast_show_id']);

        if (! $show || $show->user_id !== auth()->id()) {
            return redirect()
                ->route('footer_links.edit', $footer_link)
                ->with('error', 'The selected podcast show could not be found.');
        }

        $footer_link->update($data);

        return redirect()
            ->route('footer_links.show', $footer_link)
            ->with('success', 'Footer link updated successfully.');
    }

    /**
     * Show the delete confirmation page for a footer link.
     */
    public function deleteConfirm(FooterLink $footer_link): View
    {
        abort_if($footer_link->user_id !== auth()->id(), 403);

        $footer_link->load('podcastShow');

        return view('media_platform.tools.footer_links.delete_confirm', compact('footer_link'));
    }

    /**
     * Delete a footer link.
     */
    public function destroy(FooterLink $footer_link): RedirectResponse
    {
        abort_if($footer_link->user_id !== auth()->id(), 403);

        $footer_link->delete();

        return redirect()
            ->route('footer_links.index')
            ->with('success', 'Footer link deleted successfully.');
    }
}